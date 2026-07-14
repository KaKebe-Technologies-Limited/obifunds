<?php
// ============================================================
// ObiFunds – includes/config.php
// ============================================================

function getBaseUrl() {
    // Detect protocol — check proxy headers too (common on cPanel/shared hosting)
    $protocol = 'https'; // default to https on live
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = trim($_SERVER['HTTP_X_FORWARDED_PROTO']);
    } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $protocol = 'https';
    } elseif (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
              strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
        $protocol = 'http'; // only use http on actual localhost
    }

    $host = $_SERVER['HTTP_HOST'];

    // __DIR__ here is the /includes folder; project root is one level up
    $docRoot    = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $projectDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

    // Get the web path to the project root (e.g. /sedufunds/chama on local, '' on live)
    $path = str_replace($docRoot, '', $projectDir);
    $path = rtrim($path, '/');

    return $protocol . '://' . $host . $path;
}

define('BASE', getBaseUrl());
define('CSS_URL', BASE . '/css');
define('JS_URL', BASE . '/js');
define('ASSETS_URL', BASE . '/assets');
define('IMAGES_URL', BASE . '/assets/images');

require_once __DIR__ . '/env.php';
loadEnvFile(dirname(__DIR__) . '/.env');

// Database connection - auto-detects local vs live
// Use $GLOBALS so $conn is accessible everywhere after this file is included
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
    $GLOBALS['conn'] = (function() {
        $db = getDbCredentials();
        $c = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], $db['port'] ?? 3306);
        if ($c->connect_error) die("DB connection failed: " . $c->connect_error);
        $c->set_charset("utf8mb4");
        return $c;
    })();
}
$conn = $GLOBALS['conn'];

function asset($path) {
    return BASE . '/' . ltrim($path, '/');
}