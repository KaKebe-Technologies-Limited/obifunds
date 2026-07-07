<?php
// ============================================================
// KakebeFunds – get-wallet.php
// Lists your ioTec wallets so you can find the correct UUID
// ⚠️ Delete after use
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/iotec_functions.php';

$token = getIotecAccessToken();

echo "<style>body{font-family:monospace;padding:30px;background:#f9fafb;} pre{background:#fff;padding:16px;border-radius:8px;border:1px solid #e5e7eb;overflow:auto;}</style>";

if (!$token) {
    echo "<h2 style='color:red;font-family:sans-serif;'>❌ Failed to get access token</h2>";
    echo "<p>Check your IOTEC_CLIENT_ID and IOTEC_CLIENT_SECRET in iotec_config.php</p>";
    exit;
}

echo "<h2 style='font-family:sans-serif;'>✅ Access Token obtained</h2>";
echo "<p>Token preview: <strong>" . substr($token, 0, 60) . "…</strong></p><br>";

// Try to list wallets
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => IOTEC_PAY_BASE . '/api/wallets',
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Response:</strong> $httpCode</p>";

$data = json_decode($response, true);

// Extract wallet IDs from common response shapes
$wallets = $data['data'] ?? $data['wallets'] ?? (isset($data[0]) ? $data : []);

if (!empty($wallets)) {
    echo "<h3 style='font-family:sans-serif;color:#065f46;'>✅ Wallets Found</h3>";
    foreach ($wallets as $i => $w) {
        $id   = $w['id']   ?? $w['walletId'] ?? 'N/A';
        $name = $w['name'] ?? $w['alias']    ?? 'Unnamed';
        $bal  = $w['balance'] ?? 'N/A';
        echo "<div style='background:#d1fae5;padding:12px 16px;border-radius:8px;margin-bottom:10px;font-family:sans-serif;'>";
        echo "<strong>Wallet " . ($i+1) . ":</strong> $name<br>";
        echo "<strong>UUID:</strong> <code style='font-size:1rem;'>$id</code><br>";
        echo "<strong>Balance:</strong> $bal";
        echo "</div>";
    }
    echo "<p style='font-family:sans-serif;margin-top:16px;'>👆 Copy the UUID above and paste it into <code>IOTEC_TEST_WALLET_ID</code> or <code>IOTEC_LIVE_WALLET_ID</code> in <strong>includes/iotec_config.php</strong></p>";
} else {
    echo "<h3 style='font-family:sans-serif;color:#dc2626;'>⚠️ No wallets found or unexpected response format</h3>";
    echo "<p>Raw response:</p><pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT) ?: $response) . "</pre>";
}
?>
