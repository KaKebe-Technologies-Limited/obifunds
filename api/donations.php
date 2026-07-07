<?php
// ============================================================
// ObiFunds – api/donations.php
// Handles donation submission via ioTec Pay + donation listing
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

// Auto-add iotec_transaction_id column if missing (safe to run every request)
$conn->query(
    "ALTER TABLE donations ADD COLUMN IF NOT EXISTS
     iotec_transaction_id VARCHAR(100) NULL DEFAULT NULL"
);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── INITIATE DONATION via ioTec ───────────────────────────────
if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $campaign_id  = (int)($_POST['campaign_id'] ?? 0);
    $amount       = (float)($_POST['amount'] ?? 0);
    $donor_name   = $conn->real_escape_string(trim($_POST['donor_name']  ?? ''));
    $donor_email  = $conn->real_escape_string(trim($_POST['donor_email'] ?? ''));
    $donor_phone  = $conn->real_escape_string(trim($_POST['donor_phone'] ?? ''));
    $is_anonymous = !empty($_POST['is_anonymous']) ? 1 : 0;
    $network      = $conn->real_escape_string(trim($_POST['mobile_money_network'] ?? 'MTN Mobile Money'));

    if ($campaign_id <= 0 || $amount < 500 || empty($donor_phone)) {
        echo json_encode(['success' => false, 'message' => 'Campaign, phone and amount (min UGX 500) are required.']);
        exit;
    }
    if (!$is_anonymous && empty($donor_name)) {
        echo json_encode(['success' => false, 'message' => 'Enter your name or choose "Remain anonymous".']);
        exit;
    }

    $camp = $conn->query(
        "SELECT campaign_id, status, currency FROM campaigns WHERE campaign_id = $campaign_id LIMIT 1"
    );
    if (!$camp || $camp->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Campaign not found.']);
        exit;
    }
    $campRow = $camp->fetch_assoc();
    if ($campRow['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'This campaign is not currently accepting donations.']);
        exit;
    }

    $currency      = $campRow['currency'] ?: 'UGX';
    $display_name  = $is_anonymous ? 'Anonymous' : $donor_name;
    $donor_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL';
    $ref           = 'DON-' . time() . '-' . uniqid();

    $conn->query(
        "INSERT INTO donations
            (campaign_id, donor_id, donor_name, donor_email, donor_phone,
             is_anonymous, amount, currency, mobile_money_network,
             status, transaction_reference, created_at)
         VALUES
            ($campaign_id, $donor_user_id, '$display_name', '$donor_email', '$donor_phone',
             $is_anonymous, $amount, '$currency', '$network',
             'pending', '$ref', NOW())"
    );
    $donation_id = $conn->insert_id;

    if (!$donation_id) {
        echo json_encode(['success' => false, 'message' => 'Failed to record donation. Please try again.']);
        exit;
    }

    require_once __DIR__ . '/../includes/iotec_functions.php';

    $description = 'Donation to campaign #' . $campaign_id;
    $result      = initiateIotecPayment($donation_id, $amount, $donor_phone, $display_name, $description);

    if ($result['success']) {
        if (!empty($result['request_id'])) {
            $rid = $conn->real_escape_string($result['request_id']);
            $conn->query(
                "UPDATE donations SET iotec_transaction_id = '$rid' WHERE donation_id = $donation_id"
            );
        }
        echo json_encode([
            'success'     => true,
            'pending'     => true,
            'donation_id' => $donation_id,
            'message'     => 'A payment prompt has been sent to ' . $donor_phone . '. Please enter your PIN to complete.',
        ]);
    } else {
        $conn->query("UPDATE donations SET status = 'failed' WHERE donation_id = $donation_id");
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Payment initiation failed. Please try again.'
        ]);
    }
    exit;
}


// ── Check Donation Status ──────────────────────────────────────
if ($action === 'check_status' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $donation_id = (int)($_GET['donation_id'] ?? 0);
    
    if ($donation_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid donation ID']);
        exit;
    }
    
    $sql = "SELECT status FROM donations WHERE donation_id = $donation_id";
    $result = mysqli_query($conn, $sql);
    $donation = mysqli_fetch_assoc($result);
    
    if ($donation) {
        echo json_encode(['status' => $donation['status']]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
    exit;
}

// ── CHECK donation status (front-end polling) ─────────────────
if ($action === 'check_status' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $donation_id = (int)($_GET['donation_id'] ?? 0);
    if ($donation_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'donation_id required.']);
        exit;
    }

    $res = $conn->query(
        "SELECT status, transaction_reference, campaign_id, amount, iotec_transaction_id
         FROM donations WHERE donation_id = $donation_id LIMIT 1"
    );
    $don = $res ? $res->fetch_assoc() : null;

    if (!$don) {
        echo json_encode(['success' => false, 'message' => 'Donation not found.']);
        exit;
    }

    if ($don['status'] === 'completed') {
        echo json_encode(['success' => true, 'status' => 'completed']);
        exit;
    }
    if ($don['status'] === 'failed') {
        echo json_encode(['success' => true, 'status' => 'failed']);
        exit;
    }

    // Still pending — ask ioTec for live status
    require_once __DIR__ . '/../includes/iotec_functions.php';

    $iotecStatus = null;

    if (!empty($don['iotec_transaction_id'])) {
        $check = checkIotecStatus($don['iotec_transaction_id']);
        if ($check['success']) {
            $iotecStatus = strtolower($check['status'] ?? '');
        }
    }

    // Fallback: look up by externalId if UUID not stored
    if ($iotecStatus === null && !empty($don['transaction_reference'])) {
        $check2 = checkIotecStatusByExternalId($don['transaction_reference']);
        if ($check2['success'] && !empty($check2['id'])) {
            $rid = $conn->real_escape_string($check2['id']);
            $conn->query("UPDATE donations SET iotec_transaction_id='$rid' WHERE donation_id=$donation_id");
            $iotecStatus = strtolower($check2['status'] ?? '');
        }
    }

    if ($iotecStatus === 'success') {
        $conn->query(
            "UPDATE donations SET status='completed', payment_date=NOW()
             WHERE donation_id=$donation_id AND status='pending'"
        );
        if ($conn->affected_rows > 0) {
            $conn->query(
                "UPDATE campaigns
                 SET raised_amount=raised_amount+" . (float)$don['amount'] . ",
                     contributor_count=contributor_count+1
                 WHERE campaign_id=" . (int)$don['campaign_id']
            );
        }
        echo json_encode(['success' => true, 'status' => 'completed']);
    } elseif ($iotecStatus === 'failed') {
        $conn->query("UPDATE donations SET status='failed' WHERE donation_id=$donation_id");
        echo json_encode(['success' => true, 'status' => 'failed']);
    } else {
        echo json_encode(['success' => true, 'status' => 'pending']);
    }
    exit;
}

// ── LIST completed donations for a campaign ───────────────────
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $campaign_id = (int)($_GET['campaign_id'] ?? 0);
    if ($campaign_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'campaign_id is required.']);
        exit;
    }
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    $result = $conn->query(
        "SELECT donor_name, is_anonymous, amount, mobile_money_network, payment_date
         FROM donations
         WHERE campaign_id = $campaign_id AND status = 'completed'
         ORDER BY payment_date DESC
         LIMIT $limit OFFSET $offset"
    );
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        if ($r['is_anonymous']) $r['donor_name'] = 'Anonymous';
        $rows[] = $r;
    }
    echo json_encode(['success' => true, 'donations' => $rows]);
    exit;
}

// ── Admin list all donations ──────────────────────────────────
if ($action === 'admin_list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 25;
    $offset = ($page - 1) * $limit;

    $result = $conn->query(
        "SELECT d.*, c.title AS campaign_title
         FROM donations d
         JOIN campaigns c ON d.campaign_id = c.campaign_id
         ORDER BY d.created_at DESC
         LIMIT $limit OFFSET $offset"
    );
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $total = $conn->query("SELECT COUNT(*) FROM donations")->fetch_row()[0];
    echo json_encode(['success' => true, 'donations' => $rows, 'total' => (int)$total]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
