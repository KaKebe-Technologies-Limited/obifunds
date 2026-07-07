<?php
// ============================================================
// KakebeFunds – fix-pending-donations.php
// One-time script: recover donations that were paid but stuck
// as 'pending' because iotec_transaction_id wasn't saved yet.
// DELETE THIS FILE after running it.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/iotec_functions.php';

$fixed   = 0;
$failed  = 0;
$skipped = 0;
$log     = [];

// Get all pending donations from the last 7 days
$res = $conn->query(
    "SELECT donation_id, campaign_id, amount, transaction_reference, iotec_transaction_id
     FROM donations
     WHERE status = 'pending'
       AND created_at >= NOW() - INTERVAL 7 DAY
     ORDER BY donation_id DESC"
);

while ($don = $res->fetch_assoc()) {
    $id  = (int)$don['donation_id'];
    $ref = $don['transaction_reference'];
    $tid = $don['iotec_transaction_id'];

    // Try UUID lookup first
    if (!empty($tid)) {
        $check = checkIotecStatus($tid);
    } else {
        // Fallback: look up by externalId
        // externalId was set as "DON-{donation_id}-{timestamp}" in initiateIotecPayment
        $extId = 'DON-' . $id . '-';  // partial match won't work — try ref directly
        $check = checkIotecStatusByExternalId($ref);

        // Save UUID if found
        if ($check['success'] && !empty($check['id'])) {
            $uuid = $conn->real_escape_string($check['id']);
            $conn->query("UPDATE donations SET iotec_transaction_id='$uuid' WHERE donation_id=$id");
            $log[] = "  Saved UUID $uuid for donation #$id";
        }
    }

    $status = strtolower($check['status'] ?? '');

    if ($status === 'success') {
        // Mark completed and update campaign
        $r1 = $conn->query(
            "UPDATE donations SET status='completed', payment_date=NOW()
             WHERE donation_id=$id AND status='pending'"
        );
        if ($conn->affected_rows > 0) {
            $conn->query(
                "UPDATE campaigns
                 SET raised_amount=raised_amount+" . (float)$don['amount'] . ",
                     contributor_count=contributor_count+1
                 WHERE campaign_id=" . (int)$don['campaign_id']
            );
            $log[]   = "✅ Donation #$id FIXED → completed (UGX " . number_format($don['amount']) . ")";
            $fixed++;
        }
    } elseif ($status === 'failed') {
        $conn->query("UPDATE donations SET status='failed' WHERE donation_id=$id");
        $log[]  = "❌ Donation #$id → marked failed";
        $failed++;
    } else {
        $log[]  = "⏳ Donation #$id → still $status (skipped)";
        $skipped++;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fix Pending Donations</title>
    <style>
        body { font-family: monospace; padding: 30px; background: #f9fafb; }
        h2   { font-family: sans-serif; }
        pre  { background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb; line-height: 1.7; }
        .summary { font-family: sans-serif; margin-bottom: 20px; }
        .ok  { color: #065f46; }
        .err { color: #dc2626; }
    </style>
</head>
<body>
    <h2>Fix Pending Donations — Results</h2>
    <div class="summary">
        <span class="ok">✅ Fixed: <?= $fixed ?></span> &nbsp;|&nbsp;
        <span class="err">❌ Failed: <?= $failed ?></span> &nbsp;|&nbsp;
        ⏳ Still pending: <?= $skipped ?>
    </div>
    <pre><?= htmlspecialchars(implode("\n", $log) ?: 'No pending donations found in the last 7 days.') ?></pre>
    <p style="font-family:sans-serif;color:#dc2626;margin-top:20px;">
        ⚠️ <strong>Delete this file after running it.</strong>
    </p>
</body>
</html>
