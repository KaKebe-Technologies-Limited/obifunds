<?php
// ============================================================
// ObiFunds – payment_callback.php
// ioTec redirects the user here after payment attempt.
// We check the actual DB status (set by IPN) rather than
// trusting URL parameters alone.
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$donation_id    = (int)($_GET['donation_id'] ?? 0);
$url_status     = strtolower($_GET['status'] ?? '');
$transaction_id = $conn->real_escape_string($_GET['transactionId'] ?? '');

// ── Look up the donation in the DB ────────────────────────────
$donation = null;
if ($donation_id > 0) {
    $res      = $conn->query(
        "SELECT donation_id, campaign_id, status FROM donations
         WHERE donation_id = $donation_id LIMIT 1"
    );
    $donation = $res ? $res->fetch_assoc() : null;
}

if (!$donation) {
    // Nothing we can do — send to home
    header('Location: ' . BASE . '/index.php');
    exit;
}

$db_status   = $donation['status'];
$campaign_id = (int)$donation['campaign_id'];

// If ioTec passed a transaction ID and the IPN hasn't fired yet,
// store it now so the IPN can match later
if (!empty($transaction_id) && ($db_status === 'pending')) {
    $conn->query(
        "UPDATE donations SET iotec_transaction_id = '$transaction_id'
         WHERE donation_id = $donation_id"
    );
}

// ── Route based on status ─────────────────────────────────────
if ($db_status === 'completed' || $url_status === 'completed' || $url_status === 'success') {
    header('Location: ' . BASE . '/campaign-detail.php?id=' . $campaign_id . '&payment=success');
    exit;
}

if ($url_status === 'failed' || $url_status === 'cancelled' || $db_status === 'failed') {
    header('Location: ' . BASE . '/campaign-detail.php?id=' . $campaign_id . '&payment=failed');
    exit;
}

// Pending / unknown — show processing page
header('Location: ' . BASE . '/campaign-detail.php?id=' . $campaign_id . '&donation_id=' . $donation_id);
exit;
