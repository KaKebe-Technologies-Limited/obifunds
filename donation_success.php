<?php
// ObiFunds – donation_success.php (redirects to campaign page)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$donation_id = (int)($_GET['donation_id'] ?? 0);
if ($donation_id <= 0) {
    header('Location: ' . BASE . '/index.php');
    exit;
}

$res = $conn->query(
    "SELECT d.status, c.campaign_id
     FROM donations d
     JOIN campaigns c ON d.campaign_id = c.campaign_id
     WHERE d.donation_id = $donation_id LIMIT 1"
);
$don = $res ? $res->fetch_assoc() : null;
if (!$don) {
    header('Location: ' . BASE . '/index.php');
    exit;
}

$cid = (int)$don['campaign_id'];

if ($don['status'] === 'completed') {
    header('Location: ' . BASE . '/campaign-detail.php?id=' . $cid . '&payment=success');
    exit;
}

if ($don['status'] === 'failed') {
    header('Location: ' . BASE . '/campaign-detail.php?id=' . $cid . '&payment=failed');
    exit;
}

header('Location: ' . BASE . '/campaign-detail.php?id=' . $cid . '&donation_id=' . $donation_id);
exit;
