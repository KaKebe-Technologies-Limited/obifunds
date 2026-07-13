<?php
// ============================================================
// test-ipn.php - Simulate ioTec IPN for testing
// ============================================================

require_once 'includes/config.php';

$donation_id = (int)($_GET['donation_id'] ?? 0);

if ($donation_id <= 0) {
    die('❌ Please provide donation_id parameter: test-ipn.php?donation_id=1');
}

// Get donation details
$sql = "SELECT * FROM donations WHERE donation_id = $donation_id";
$result = mysqli_query($conn, $sql);
$donation = mysqli_fetch_assoc($result);

if (!$donation) {
    die('❌ Donation not found');
}

// Simulate IPN payload
$payload = [
    'id' => 'TEST-' . time(),
    'transactionId' => $donation['transaction_reference'],
    'externalId' => $donation['transaction_reference'],
    'status' => 'completed',
    'amount' => $donation['amount'],
    'phoneNumber' => $donation['donor_phone']
];

echo "🔵 Simulating IPN for donation #$donation_id<br>";
echo "<pre>";
print_r($payload);
echo "</pre>";

// Send to ipn_handler
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://obifunds.com/ipn_handler.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-IPN-Secret: ' . (defined('IOTEC_IPN_SECRET') ? IOTEC_IPN_SECRET : '')
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📡 Response Code: $httpCode<br>";
echo "📝 Response: $response<br>";

if ($httpCode === 200) {
    echo "✅ IPN simulation successful! Check your database.";
} else {
    echo "❌ IPN simulation failed. Check your ipn_handler.php";
}
?>