<?php
// ============================================================
// ObiFunds – test-iotec.php
// Sandbox test page for ioTec Pay integration
// ⚠️  DELETE or RESTRICT THIS FILE before going live
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/iotec_config.php';

if (!IOTEC_SANDBOX) {
    die('<div style="font-family:sans-serif;padding:40px;color:red;"><h2>⛔ Test page is disabled in production mode.</h2><p>Set IOTEC_SANDBOX to true or delete this file.</p></div>');
}

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test  = $_POST['test']   ?? '';
    $phone = trim($_POST['phone']  ?? '0111777771');
    $amt   = (float)($_POST['amount'] ?? 1000);

    // ── TEST 1: Get Access Token ──────────────────────────────
    if ($test === 'token' || $test === 'all') {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => IOTEC_AUTH_URL,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'client_id'     => IOTEC_CLIENT_ID,
                'client_secret' => IOTEC_CLIENT_SECRET,
                'grant_type'    => IOTEC_GRANT_TYPE,
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode($raw, true);
        $token   = $decoded['access_token'] ?? null;

        $results['token'] = [
            'label'         => '1 · Get OAuth2 Access Token',
            'endpoint'      => IOTEC_AUTH_URL,
            'http_code'     => $httpCode,
            'curl_error'    => $curlErr,
            'success'       => ($httpCode === 200 && !empty($token)),
            'token_preview' => $token ? substr($token, 0, 60) . '…' : null,
            'expires_in'    => $decoded['expires_in'] ?? null,
            'raw'           => $raw,
        ];
    }

    // ── TEST 2: Initiate Collection ───────────────────────────
    if ($test === 'payment' || $test === 'all') {
        require_once __DIR__ . '/includes/iotec_functions.php';

        $fakeDonId = 'TEST-' . time();
        $result    = initiateIotecPayment($fakeDonId, $amt, $phone, 'Test Donor', 'Sandbox test donation');

        $results['payment'] = [
            'label'      => '2 · Initiate Mobile Money Collection',
            'endpoint'   => IOTEC_PAY_BASE . '/api/collections/collect',
            'success'    => $result['success'],
            'request_id' => $result['request_id'] ?? null,
            'status'     => $result['status'] ?? null,
            'message'    => $result['message'] ?? null,
            'raw'        => json_encode($result['raw'] ?? $result, JSON_PRETTY_PRINT),
            'phone_used' => normalisePhone($phone),
        ];
    }

    // ── TEST 3: Check Collection Status ──────────────────────
    if ($test === 'status') {
        require_once __DIR__ . '/includes/iotec_functions.php';
        $rid = trim($_POST['request_id'] ?? '');

        if (empty($rid)) {
            $results['status'] = ['label' => '3 · Check Status', 'success' => false, 'message' => 'No request ID provided.', 'raw' => ''];
        } else {
            $result = checkIotecStatus($rid);
            $results['status'] = [
                'label'      => '3 · Check Collection Status',
                'endpoint'   => IOTEC_PAY_BASE . '/api/collections/status/' . $rid,
                'success'    => $result['success'],
                'http_code'  => $result['http_code'],
                'status'     => $result['status'],
                'message'    => $result['message'] ?? null,
                'raw'        => json_encode($result['raw'], JSON_PRETTY_PRINT),
            ];
        }
    }

    // ── TEST 4: Simulate IPN Callback ────────────────────────
    if ($test === 'ipn' || $test === 'all') {
        $fakePayload = json_encode([
            'id'         => 'sandbox-' . uniqid(),
            'status'     => 'Success',
            'amount'     => $amt,
            'currency'   => IOTEC_CURRENCY,
            'externalId' => 'DON-TEST-' . time(),
            'payer'      => normalisePhone($phone),
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => BASE . '/ipn_handler.php',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fakePayload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        $results['ipn'] = [
            'label'        => '4 · Simulate IPN Notification',
            'endpoint'     => BASE . '/ipn_handler.php',
            'http_code'    => $httpCode,
            'curl_error'   => $curlErr,
            'success'      => ($httpCode === 200),
            'raw'          => $raw,
            'payload_sent' => $fakePayload,
        ];
    }
}

// ── Render a result card ──────────────────────────────────────
function renderCard(array $r): void {
    $ok   = $r['success'] ?? false;
    $col  = $ok ? '#065f46' : '#991b1b';
    $bg   = $ok ? '#d1fae5' : '#fee2e2';
    $icon = $ok ? '✅' : '❌';
    echo "<div style='background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:20px;'>";
    echo "<h3 style='margin:0 0 10px;font-family:sans-serif;color:#1f2937;font-size:1rem;'>{$r['label']}</h3>";
    echo "<span style='background:{$bg};color:{$col};padding:3px 12px;border-radius:99px;font-size:.8rem;font-weight:700;font-family:sans-serif;'>{$icon} " . ($ok ? 'SUCCESS' : 'FAILED') . "</span>";
    if (!empty($r['endpoint']))      echo "<p style='margin:8px 0 2px;font-size:.8rem;color:#6b7280;font-family:monospace;'>→ {$r['endpoint']}</p>";
    if (!empty($r['http_code']))     { $cc = ($r['http_code'] >= 200 && $r['http_code'] < 300) ? '#065f46' : '#dc2626'; echo "<p style='font-size:.85rem;margin:4px 0;font-family:sans-serif;'>HTTP: <strong style='color:{$cc}'>{$r['http_code']}</strong></p>"; }
    if (!empty($r['curl_error']))    echo "<p style='color:#dc2626;font-size:.85rem;font-family:sans-serif;'>cURL error: {$r['curl_error']}</p>";
    if (!empty($r['token_preview'])) echo "<p style='font-size:.82rem;font-family:sans-serif;margin:4px 0;'>Token: <code style='background:#f3f4f6;padding:2px 6px;border-radius:4px;'>{$r['token_preview']}</code></p>";
    if (!empty($r['expires_in']))    echo "<p style='font-size:.82rem;font-family:sans-serif;margin:4px 0;'>Expires in: {$r['expires_in']}s</p>";
    if (!empty($r['request_id']))    echo "<p style='font-size:.82rem;font-family:sans-serif;margin:4px 0;'>Request ID: <code style='background:#f3f4f6;padding:2px 6px;border-radius:4px;'>{$r['request_id']}</code> <em style='color:#6b7280;font-size:.75rem;'>(use this in Status check)</em></p>";
    if (!empty($r['status']))        echo "<p style='font-size:.82rem;font-family:sans-serif;margin:4px 0;'>Status: <strong>{$r['status']}</strong></p>";
    if (!empty($r['phone_used']))    echo "<p style='font-size:.82rem;font-family:sans-serif;margin:4px 0;'>Normalised phone: <code>{$r['phone_used']}</code></p>";
    if (!empty($r['message']))       echo "<p style='font-size:.82rem;color:#92400e;font-family:sans-serif;margin:4px 0;'>Message: {$r['message']}</p>";
    if (!empty($r['payload_sent'])) {
        $pretty = json_encode(json_decode($r['payload_sent']), JSON_PRETTY_PRINT) ?: $r['payload_sent'];
        echo "<details style='margin-top:10px;'><summary style='cursor:pointer;font-size:.8rem;color:#6b7280;font-family:sans-serif;'>Payload Sent</summary><pre style='background:#f9fafb;padding:10px;border-radius:8px;font-size:.76rem;overflow:auto;margin-top:6px;'>" . htmlspecialchars($pretty) . "</pre></details>";
    }
    if (!empty($r['raw'])) {
        $pretty = json_encode(json_decode($r['raw']), JSON_PRETTY_PRINT) ?: $r['raw'];
        echo "<details style='margin-top:8px;'><summary style='cursor:pointer;font-size:.8rem;color:#6b7280;font-family:sans-serif;'>Raw Response</summary><pre style='background:#f9fafb;padding:10px;border-radius:8px;font-size:.76rem;overflow:auto;margin-top:6px;'>" . htmlspecialchars($pretty) . "</pre></details>";
    }
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ioTec Sandbox Test – ObiFunds</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',sans-serif;background:#f3f4f6;color:#1f2937}
        .wrap{max-width:900px;margin:0 auto;padding:40px 20px}
        h1{font-size:1.5rem;font-weight:800;margin-bottom:4px}
        .badge{display:inline-block;background:#fef3c7;color:#92400e;padding:3px 12px;border-radius:99px;font-size:.78rem;font-weight:700;margin-bottom:20px}
        .warn{background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 16px;font-size:.84rem;color:#92400e;margin-bottom:20px}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:24px;margin-bottom:20px}
        .card h2{font-size:.95rem;font-weight:700;margin-bottom:12px;color:#374151}
        .card p.sub{font-size:.82rem;color:#6b7280;margin-bottom:14px;line-height:1.5}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        label{display:block;font-size:.8rem;font-weight:600;color:#4b5563;margin-bottom:4px}
        input[type=text],input[type=number]{width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:.88rem;outline:none}
        input:focus{border-color:var(--green-dark)}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:99px;font-weight:700;font-size:.85rem;cursor:pointer;border:none;transition:all .2s;margin-top:14px}
        .btn-navy{background:var(--green-dark);color:#fff}.btn-navy:hover{background:var(--green-dark)}
        .btn-green{background:#065f46;color:#fff}.btn-green:hover{background:#047857}
        .btn-red{background:#dc2626;color:#fff}.btn-red:hover{background:#b91c1c}
        .btn-amber{background:#d97706;color:#fff}.btn-amber:hover{background:#b45309}
        .info-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
        .tile{background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:12px}
        .tile .tl{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;margin-bottom:3px}
        .tile .tv{font-size:.82rem;font-weight:700;color:#1f2937;word-break:break-all}
        hr{border:none;border-top:1px solid #e5e7eb;margin:24px 0}
        .phone-hint{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:.8rem;color:#1e40af;margin-bottom:14px}
        @media(max-width:600px){.grid,.info-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="wrap">

    <h1>⚙️ ioTec Pay — Sandbox Test</h1>
    <span class="badge">🧪 SANDBOX MODE — No real money moves</span>

    <div class="warn">
        ⚠️ <strong>Delete or password-protect this file before going live.</strong>
        It exposes your API credentials and sandbox configuration.
    </div>

    <!-- Config overview -->
    <div class="card">
        <h2>📋 Current Configuration</h2>
        <div class="info-grid">
            <div class="tile"><div class="tl">Mode</div><div class="tv" style="color:<?= IOTEC_SANDBOX?'#065f46':'#dc2626'?>"><?= IOTEC_SANDBOX?'🟢 SANDBOX':'🔴 LIVE'?></div></div>
            <div class="tile"><div class="tl">Auth URL</div><div class="tv"><?= IOTEC_AUTH_URL ?></div></div>
            <div class="tile"><div class="tl">Pay Base URL</div><div class="tv"><?= IOTEC_PAY_BASE ?></div></div>
            <div class="tile"><div class="tl">Client ID</div><div class="tv"><?= substr(IOTEC_CLIENT_ID,0,24) ?>…</div></div>
            <div class="tile"><div class="tl">Wallet ID (test)</div><div class="tv"><?= IOTEC_TEST_WALLET_ID ?></div></div>
            <div class="tile"><div class="tl">Currency</div><div class="tv"><?= IOTEC_CURRENCY ?></div></div>
            <div class="tile"><div class="tl">Callback URL</div><div class="tv"><?= IOTEC_CALLBACK_URL ?></div></div>
            <div class="tile"><div class="tl">IPN URL</div><div class="tv"><?= IOTEC_IPN_URL ?></div></div>
            <div class="tile"><div class="tl">Live Wallet ID</div><div class="tv" style="color:<?= IOTEC_LIVE_WALLET_ID?'#065f46':'#dc2626'?>"><?= IOTEC_LIVE_WALLET_ID ?: '⚠️ Not set'?></div></div>
        </div>
    </div>

    <!-- Sandbox phone numbers reference -->
    <div class="card">
        <h2>📱 Sandbox Test Phone Numbers</h2>
        <p class="sub">Use these numbers to simulate specific payment outcomes:</p>
        <table style="width:100%;border-collapse:collapse;font-size:.83rem;">
            <thead><tr style="border-bottom:2px solid #e5e7eb;">
                <th style="text-align:left;padding:8px 10px;color:#6b7280;font-size:.72rem;text-transform:uppercase;">Phone</th>
                <th style="text-align:left;padding:8px 10px;color:#6b7280;font-size:.72rem;text-transform:uppercase;">Result</th>
                <th style="text-align:left;padding:8px 10px;color:#6b7280;font-size:.72rem;text-transform:uppercase;">Description</th>
            </tr></thead>
            <tbody>
                <tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:8px 10px;"><code>0111777771</code></td><td style="padding:8px 10px;color:#065f46;font-weight:700;">✅ Success</td><td style="padding:8px 10px;color:#6b7280;">Transaction succeeds</td></tr>
                <tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:8px 10px;"><code>0111777991</code></td><td style="padding:8px 10px;color:#dc2626;font-weight:700;">❌ Failed</td><td style="padding:8px 10px;color:#6b7280;">Transaction fails</td></tr>
                <tr style="border-bottom:1px solid #f3f4f6;"><td style="padding:8px 10px;"><code>0111777781</code></td><td style="padding:8px 10px;color:#d97706;font-weight:700;">⏳ Pending</td><td style="padding:8px 10px;color:#6b7280;">Stays pending</td></tr>
                <tr><td style="padding:8px 10px;"><code>0111777791</code></td><td style="padding:8px 10px;color:#1d4ed8;font-weight:700;">📡 SentToVendor</td><td style="padding:8px 10px;color:#6b7280;">Being processed by MTN/Airtel</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Test 1: Token -->
    <div class="card">
        <h2>🔐 Test 1 — Get Access Token</h2>
        <p class="sub">Calls <code><?= IOTEC_AUTH_URL ?></code> with your credentials. Expect HTTP 200 and an access token.</p>
        <form method="POST">
            <input type="hidden" name="test" value="token" />
            <button class="btn btn-navy" type="submit">Run Token Test</button>
        </form>
    </div>

    <!-- Test 2: Collect -->
    <div class="card">
        <h2>💳 Test 2 — Initiate Collection</h2>
        <p class="sub">Sends a mobile money collection request to <code><?= IOTEC_PAY_BASE ?>/api/collections/collect</code>. Use a sandbox phone number from the table above.</p>
        <div class="phone-hint">💡 Use <strong>0111777771</strong> for a successful payment simulation.</div>
        <form method="POST">
            <input type="hidden" name="test" value="payment" />
            <div class="grid">
                <div><label>Phone Number</label><input type="text" name="phone" value="0111777771" /></div>
                <div><label>Amount (<?= IOTEC_CURRENCY ?>)</label><input type="number" name="amount" value="1000" min="500" /></div>
            </div>
            <button class="btn btn-navy" type="submit">Run Collection Test</button>
        </form>
    </div>

    <!-- Test 3: Status check -->
    <div class="card">
        <h2>🔍 Test 3 — Check Collection Status</h2>
        <p class="sub">Paste the <code>request_id</code> from Test 2 to check its current status via <code>/api/collections/status/{id}</code>.</p>
        <form method="POST">
            <input type="hidden" name="test" value="status" />
            <div><label>Request ID (UUID from Test 2)</label><input type="text" name="request_id" placeholder="e.g. 3fa85f64-5717-4562-b3fc-2c963f66afa6" /></div>
            <button class="btn btn-amber" type="submit">Check Status</button>
        </form>
    </div>

    <!-- Test 4: IPN -->
    <div class="card">
        <h2>📡 Test 4 — Simulate IPN Callback</h2>
        <p class="sub">POSTs a fake <code>Success</code> IPN payload to your <code>ipn_handler.php</code>. Should return HTTP 200. The transaction won't match a real donation but confirms the endpoint is reachable.</p>
        <form method="POST">
            <input type="hidden" name="test" value="ipn" />
            <div class="grid">
                <div><label>Phone</label><input type="text" name="phone" value="0111777771" /></div>
                <div><label>Amount</label><input type="number" name="amount" value="1000" /></div>
            </div>
            <button class="btn btn-green" type="submit">Simulate IPN</button>
        </form>
    </div>

    <!-- Run All -->
    <div class="card">
        <h2>🚀 Run All Tests (Token + Collection + IPN)</h2>
        <form method="POST">
            <input type="hidden" name="test" value="all" />
            <div class="grid">
                <div><label>Phone</label><input type="text" name="phone" value="0111777771" /></div>
                <div><label>Amount</label><input type="number" name="amount" value="1000" min="500" /></div>
            </div>
            <button class="btn btn-red" type="submit">▶ Run All Tests</button>
        </form>
    </div>

    <!-- Results -->
    <?php if (!empty($results)): ?>
    <hr />
    <h2 style="font-size:1.05rem;font-weight:800;margin-bottom:16px;">📊 Test Results</h2>
    <?php foreach ($results as $r): renderCard($r); endforeach; ?>
    <?php endif; ?>

    <p style="font-size:.75rem;color:#9ca3af;text-align:center;margin-top:32px;">
        ObiFunds · ioTec Sandbox Tester · <?= date('Y-m-d H:i:s') ?>
    </p>
</div>
</body>
</html>
