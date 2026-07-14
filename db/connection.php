<?php
// ============================================================
// ObiFunds – db/connection.php
// ============================================================

// Reuse existing connection if config.php already created it
if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
    $conn = $GLOBALS['conn'];
    return $conn;
}

require_once dirname(__DIR__) . '/includes/env.php';
loadEnvFile(dirname(__DIR__) . '/.env');

$db = getDbCredentials();
$conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port'] ?? 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$GLOBALS['conn'] = $conn;

return $conn;
