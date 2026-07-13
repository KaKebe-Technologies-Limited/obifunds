<?php
// ============================================================
// ObiFunds – ipn_handler.php (ROBUST VERSION)
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/iotec_config.php';

// ── Log the incoming request ──────────────────────────────────
$raw_post = file_get_contents('php://input');
$post_data = json_decode($raw_post, true);
error_log('ioTec IPN Received: ' . print_r($post_data, true));

// ── Extract transaction data ──────────────────────────────────
$transaction_id = $post_data['transactionId'] ?? $post_data['id'] ?? $post_data['transaction_id'] ?? '';
$external_id    = $post_data['externalId'] ?? $post_data['reference'] ?? $post_data['merchantReference'] ?? '';
$status         = strtolower($post_data['status'] ?? $post_data['paymentStatus'] ?? '');

if (empty($transaction_id)) {
    error_log('❌ IPN: No transaction ID found');
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

error_log("IPN Processing: transaction_id=$transaction_id, external_id=$external_id, status=$status");

// ── Find the donation ──────────────────────────────────────────
$donation = null;

// Strategy 1: Match by ioTec transaction ID
if (!empty($transaction_id)) {
    $result = $conn->query(
        "SELECT donation_id, campaign_id, amount FROM donations
         WHERE iotec_transaction_id = '" . $conn->real_escape_string($transaction_id) . "' 
         OR transaction_reference = '" . $conn->real_escape_string($transaction_id) . "'
         LIMIT 1"
    );
    $donation = $result ? $result->fetch_assoc() : null;
    if ($donation) {
        error_log('✅ IPN: Found donation by transaction_id: ' . $donation['donation_id']);
    }
}

// Strategy 2: Match by external ID (DON-{id}-{timestamp})
if (!$donation && !empty($external_id) && preg_match('/^DON-(\d+)-/', $external_id, $m)) {
    $fb_id = (int)$m[1];
    $result = $conn->query(
        "SELECT donation_id, campaign_id, amount FROM donations
         WHERE donation_id = $fb_id AND status = 'pending' LIMIT 1"
    );
    $donation = $result ? $result->fetch_assoc() : null;
    if ($donation && !empty($transaction_id)) {
        $conn->query(
            "UPDATE donations SET iotec_transaction_id = '" . 
            $conn->real_escape_string($transaction_id) . "' 
            WHERE donation_id = $fb_id"
        );
        error_log('✅ IPN: Found donation by external_id: ' . $donation['donation_id']);
    }
}

// Strategy 3: Match by phone and amount (last resort)
if (!$donation && !empty($post_data['phoneNumber'])) {
    $phone = $conn->real_escape_string($post_data['phoneNumber']);
    $amount = (float)($post_data['amount'] ?? 0);
    $result = $conn->query(
        "SELECT donation_id, campaign_id, amount FROM donations
         WHERE donor_phone = '$phone' 
         AND amount = $amount 
         AND status = 'pending' 
         ORDER BY created_at DESC LIMIT 1"
    );
    $donation = $result ? $result->fetch_assoc() : null;
    if ($donation) {
        error_log('✅ IPN: Found donation by phone+amount: ' . $donation['donation_id']);
    }
}

// ── Process the donation ──────────────────────────────────────
if ($donation) {
    $donation_id = (int)$donation['donation_id'];
    $campaign_id = (int)$donation['campaign_id'];
    $amount = (float)$donation['amount'];

    error_log("IPN Processing donation_id=$donation_id, status=$status");

    if ($status === 'success' || $status === 'completed' || $status === 'paid') {
        // ── Update donation ──
        $conn->query(
            "UPDATE donations SET 
                status = 'completed', 
                payment_date = NOW() 
             WHERE donation_id = $donation_id"
        );
        
        // ── Update campaign ──
        $conn->query(
            "UPDATE campaigns SET 
                raised_amount = raised_amount + $amount, 
                contributor_count = contributor_count + 1 
             WHERE campaign_id = $campaign_id"
        );
        
        error_log('✅ IPN: Donation ' . $donation_id . ' COMPLETED. Campaign ' . $campaign_id . ' updated.');
        
    } elseif ($status === 'failed' || $status === 'cancelled') {
        $conn->query(
            "UPDATE donations SET status = 'failed' WHERE donation_id = $donation_id"
        );
        error_log('❌ IPN: Donation ' . $donation_id . ' FAILED.');
    } else {
        error_log('⏳ IPN: Donation ' . $donation_id . ' status: ' . $status);
    }
} else {
    error_log('⚠️ IPN: No matching donation found');
    error_log('IPN Payload: ' . print_r($post_data, true));
}

// ── Always respond with 200 OK ──────────────────────────────
http_response_code(200);
echo 'OK';
exit;