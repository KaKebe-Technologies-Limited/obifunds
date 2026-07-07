<?php
// ============================================================
// ObiFunds – ipn_handler.php
// Handles ioTec server-to-server payment notifications
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/iotec_config.php';

// ── 1. Verify the IPN secret header ──────────────────────────
// ioTec sends X-IPN-Secret with every callback — reject anything without it
$incoming_secret = $_SERVER['HTTP_X_IPN_SECRET'] ?? '';
if (!hash_equals(IOTEC_IPN_SECRET, $incoming_secret)) {
    error_log('ioTec IPN: Rejected — invalid or missing X-IPN-Secret');
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

// ── 2. Parse the payload ──────────────────────────────────────
// Ensure column exists (auto-migration — safe to run on every request)
$conn->query(
    "ALTER TABLE donations ADD COLUMN IF NOT EXISTS
     iotec_transaction_id VARCHAR(100) NULL DEFAULT NULL"
);

$raw_post  = file_get_contents('php://input');
$post_data = json_decode($raw_post, true);
error_log('ioTec IPN Received: ' . $raw_post);

if (isset($post_data['id']) && isset($post_data['status'])) {
    $transaction_id = $conn->real_escape_string($post_data['id']);
    $external_id    = $conn->real_escape_string($post_data['externalId'] ?? '');
    $status         = strtolower($post_data['status']); // 'success'|'failed'|'pending'|'senttovendor'

    // Strategy 1: match by ioTec UUID
    $result = $conn->query(
        "SELECT donation_id, campaign_id, amount FROM donations
         WHERE iotec_transaction_id = '$transaction_id' LIMIT 1"
    );
    $donation = $result ? $result->fetch_assoc() : null;

    // Strategy 2: fallback — match by our own externalId (stored in transaction_reference)
    // externalId format is "DON-{donation_id}-{timestamp}" — extract donation_id from it
    if (!$donation && !empty($external_id)) {
        // Also store the ioTec UUID now that we have it
        if (preg_match('/^DON-(\d+)-/', $external_id, $m)) {
            $fb_id = (int)$m[1];
            $result2 = $conn->query(
                "SELECT donation_id, campaign_id, amount FROM donations
                 WHERE donation_id = $fb_id AND status = 'pending' LIMIT 1"
            );
            $donation = $result2 ? $result2->fetch_assoc() : null;
            if ($donation) {
                // Save the ioTec UUID for future lookups
                $conn->query(
                    "UPDATE donations SET iotec_transaction_id = '$transaction_id'
                     WHERE donation_id = $fb_id"
                );
                error_log("ioTec IPN: Matched via externalId fallback, donation_id=$fb_id");
            }
        }
    }
    
    if ($donation) {
        if ($status === 'success') {
            $conn->query(
                "UPDATE donations SET status = 'completed', payment_date = NOW()
                 WHERE donation_id = " . (int)$donation['donation_id']
            );
            $conn->query(
                "UPDATE campaigns SET
                    raised_amount     = raised_amount + " . (float)$donation['amount'] . ",
                    contributor_count = contributor_count + 1
                 WHERE campaign_id = " . (int)$donation['campaign_id']
            );
            error_log('✅ ioTec IPN: Donation ' . $donation['donation_id'] . ' completed.');

        } elseif ($status === 'failed') {
            $conn->query(
                "UPDATE donations SET status = 'failed'
                 WHERE donation_id = " . (int)$donation['donation_id']
            );
            error_log('❌ ioTec IPN: Donation ' . $donation['donation_id'] . ' failed.');

        } else {
            // Pending / SentToVendor — still processing, no DB change needed
            error_log('⏳ ioTec IPN: Donation ' . $donation['donation_id'] . ' status: ' . $status);
        }
    } else {
        error_log('⚠️ ioTec IPN: Transaction not found: ' . $transaction_id);
    }
}

// Always respond with 200 OK
http_response_code(200);
echo 'OK';
exit;
?>