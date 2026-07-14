<?php
// ============================================================
// ObiFunds – includes/iotec_config.php
// ioTec Pay Configuration
// Docs: https://iotec.io/api-docs/pay
// ============================================================

// ── API Credentials ───────────────────────────────────────────
// Sent to your email when you registered on pay.iotec.io
define('IOTEC_CLIENT_ID',     'pay-019f314e-f9f1-70a0-b089-06f53b92df21');
define('IOTEC_CLIENT_SECRET', 'IO-87xRMPjXj99LezKzP884sJED8cnDQUynS');
define('IOTEC_GRANT_TYPE',    'client_credentials');

// ── Wallet IDs (must be UUIDs from pay.iotec.io dashboard) ───
// Go to pay.iotec.io → Wallets → click your wallet → copy the UUID from the URL
define('IOTEC_TEST_WALLET_ID', '019f314e-fa12-764a-b7a5-be6c5938974d');
define('IOTEC_LIVE_WALLET_ID', '019f37d2-82a0-721e-8d72-7fd11d81368a');  // ← Paste UUID from pay.iotec.io when ready

// ── Environment ───────────────────────────────────────────────
// true = sandbox/testing   false = live/production
$isLocalIotec = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
                strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
define('IOTEC_SANDBOX', $isLocalIotec);

// ── API Base URLs (correct endpoints from official docs) ──────
define('IOTEC_AUTH_URL', 'https://id.iotec.io/connect/token');   // OAuth token endpoint
define('IOTEC_PAY_BASE', 'https://pay.iotec.io');                 // Payment API base

// ── IPN Secret (must match what you set in pay.iotec.io portal) ─
define('IOTEC_IPN_SECRET', '2ta6cfziH7W54kgDFGhUmZRq8esTXMw9SEBvLQyb');
// Use 'ITX' for sandbox testing, 'UGX' for live
define('IOTEC_CURRENCY', IOTEC_SANDBOX ? 'ITX' : 'UGX');

// ── Callback URLs ─────────────────────────────────────────────
// These are registered in the pay.iotec.io portal:
//   pay.iotec.io → Wallets → [your wallet] → Settings → Callback URLs
//
//   Collections callback : https://obifunds.com/payment_callback.php
//   IPN / notify URL     : https://obifunds.com/ipn_handler.php
//
// The constants below are used anywhere in code that needs the full URL.
if (!defined('IOTEC_CALLBACK_URL')) {
    $isLocal = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
               strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;

    define('IOTEC_CALLBACK_URL', $isLocal
        ? BASE . '/payment_callback.php'
        : 'https://obifunds.com/payment_callback.php'
    );
    define('IOTEC_IPN_URL', $isLocal
        ? BASE . '/ipn_handler.php'
        : 'https://obifunds.com/ipn_handler.php'
    );
}
