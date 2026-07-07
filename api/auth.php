<?php
// ============================================================
// ObiFunds – api/auth.php
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'login') {
    // Always output JSON
    header('Content-Type: application/json');

    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both email/phone and password.']);
        exit;
    }

    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

    if ($isEmail) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('s', $identifier);
    } else {
        $phone = preg_replace('/[^0-9]/', '', $identifier);
        $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('s', $phone);
    }

    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $password === $user['password_hash']) {
        // Regenerate session BEFORE writing session data
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['user']    = [
            'user_id'    => $user['user_id'],
            'full_name'  => $user['full_name'],
            'email'      => $user['email'],
            'phone'      => $user['phone'],
            'role'       => $user['role'],
            'avatar_url' => $user['avatar_url'] ?? ''
        ];

        $conn->query("UPDATE users SET last_login = NOW() WHERE user_id = " . (int)$user['user_id']);

        $dest          = ($user['role'] === 'admin') ? '/admin/index.php' : '/dashboard.php';
        $redirectAfter = $_SESSION['redirect_after_auth'] ?? '';
        unset($_SESSION['redirect_after_auth']);

        echo json_encode([
            'success'  => true,
            'redirect' => $redirectAfter ?: BASE . $dest
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Incorrect email/phone or password.']);
    exit;
}

if ($action === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']);
    }

    session_destroy();

    // If called via AJAX return JSON, otherwise redirect directly
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest' ||
              (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'redirect' => BASE . '/login.php?msg=logged_out']);
    } else {
        header('Location: ' . BASE . '/login.php?msg=logged_out');
    }
    exit;
}

if ($action === 'register') {
    header('Content-Type: application/json');

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $phone    = preg_replace('/[^0-9]/', '', trim($_POST['phone'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['campaigner', 'donor']) ? $_POST['role'] : 'donor';
    $country  = trim($_POST['country'] ?? 'Uganda');

    if (!$fullName || !$email || !$phone || !$password) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }
    if (strlen($phone) < 9) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number.']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone = ? LIMIT 1");
    $stmt->bind_param('ss', $email, $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'An account with that email or phone already exists.']);
        exit;
    }

    $stmt = $conn->prepare(
        "INSERT INTO users (full_name, email, phone, password_hash, role, country, is_active, is_verified, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, 0, NOW())"
    );
    $stmt->bind_param('ssssss', $fullName, $email, $phone, $password, $role, $country);
    $stmt->execute();

    if ($conn->insert_id) {
        $userId = $conn->insert_id;

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['role']    = $role;
        $_SESSION['user']    = [
            'user_id'    => $userId,
            'full_name'  => $fullName,
            'email'      => $email,
            'phone'      => $phone,
            'role'       => $role,
            'avatar_url' => ''
        ];

        $redirectAfter = $_SESSION['redirect_after_auth'] ?? '';
        unset($_SESSION['redirect_after_auth']);

        echo json_encode([
            'success'  => true,
            'message'  => 'Account created! Welcome to ObiFunds.',
            'redirect' => $redirectAfter ?: BASE . '/dashboard.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
