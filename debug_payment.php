<?php
// ObiFunds – debug_payment.php
// TEMPORARY DEBUG PAGE — DELETE AFTER FIXING
require_once __DIR__ . '/includes/config.php';

$donation_id = (int)($_GET['id'] ?? 0);

if ($donation_id > 0) {
    $res = $conn->query("SELECT * FROM donations WHERE donation_id = $donation_id LIMIT 1");
    $don = $res ? $res->fetch_assoc() : null;
    header('Content-Type: application/json');
    echo json_encode($don ?: ['error' => 'not found'], JSON_PRETTY_PRINT);
    exit;
}

// Show last 5 donations
$res = $conn->query("SELECT donation_id, status, iotec_transaction_id, transaction_reference, created_at, donor_phone, amount FROM donations ORDER BY donation_id DESC LIMIT 5");
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
header('Content-Type: application/json');
echo json_encode($rows, JSON_PRETTY_PRINT);
