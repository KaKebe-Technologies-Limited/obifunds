<?php
// ============================================================
// ObiFunds – includes/notifications.php
// ============================================================

// Load PHPMailer directly — avoids broken vendor/autoload.php
// (Pesapal's Guzzle/Symfony dependencies were removed but autoload
//  still references them, causing a fatal error)
$phpmailerSrc = __DIR__ . '/../vendor/phpmailer/phpmailer/src/';
if (is_dir($phpmailerSrc)) {
    require_once $phpmailerSrc . 'Exception.php';
    require_once $phpmailerSrc . 'PHPMailer.php';
    require_once $phpmailerSrc . 'SMTP.php';
    define('PHPMAILER_LOADED', true);
} else {
    define('PHPMAILER_LOADED', false);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── SMTP Configuration ──────────────────────────────────────
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'ot.sedrick@gmail.com');
define('SMTP_PASS', 'igemnyvfuejonian'); // Google App Password
define('SMTP_PORT', 587);
define('ADMIN_EMAIL', 'ot.sedrick@gmail.com');    // ← WHERE NOTIFICATIONS GO

// ── Send Email via SMTP ────────────────────────────────────
function sendCampaignCreationEmail($campaign_data) {
    if (!defined('PHPMAILER_LOADED') || !PHPMAILER_LOADED) {
        error_log('PHPMailer not available — skipping email notification');
        return false;
    }
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USER, 'ObiFunds Notifications');
        // Show the campaigner as Reply-To so you know who sent it
        if (!empty($campaign_data['campaigner_email'])) {
            $mail->addReplyTo(
                $campaign_data['campaigner_email'],
                $campaign_data['campaigner_name'] ?? 'Campaigner'
            );
        }
        $mail->addAddress(ADMIN_EMAIL, 'ObiFunds Admin');
        $mail->addCustomHeader('X-Sender-Name',  $campaign_data['campaigner_name']  ?? '');
        $mail->addCustomHeader('X-Sender-Email', $campaign_data['campaigner_email'] ?? '');

        // Content
        $mail->isHTML(true);
        $mail->Subject = '🚀 New Campaign by ' . ($campaign_data['campaigner_name'] ?? 'Unknown') . ' — ' . $campaign_data['title'];
        $mail->Body    = buildEmailBody($campaign_data);
        $mail->AltBody = strip_tags($mail->Body);

        $mail->send();
        error_log('✅ Campaign notification sent to ' . ADMIN_EMAIL);
        return true;
    } catch (Exception $e) {
        error_log('❌ Email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

// ── Build Email HTML ────────────────────────────────────────
function buildEmailBody($campaign_data) {
    $title = htmlspecialchars($campaign_data['title']);
    $name  = htmlspecialchars($campaign_data['campaigner_name']);
    $email = htmlspecialchars($campaign_data['campaigner_email']);
    $phone = htmlspecialchars($campaign_data['campaigner_phone']);
    $cat   = htmlspecialchars($campaign_data['category']);
    $goal  = number_format($campaign_data['goal_amount']);
    $curr  = htmlspecialchars($campaign_data['currency']);
    $country = htmlspecialchars($campaign_data['country']);
    $link  = BASE . '/admin/index.php?tab=campaigns&view=' . $campaign_data['campaign_id'];

    return "
    <html>
    <head><style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background:var(--green-dark); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; border-radius: 0 0 10px 10px; }
        .detail { margin: 15px 0; padding: 15px; background: white; border-radius: 8px; border-left: 4px solid var(--green); }
        .label { font-weight: bold; color:var(--green-dark); }
        .btn { display: inline-block; padding: 12px 24px; background:var(--green); color: white; text-decoration: none; border-radius: 8px; }
        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 12px; }
    </style></head>
    <body>
        <div class='container'>
            <div class='header'><h2>🚀 New Campaign Created!</h2></div>
            <div class='content'>
                <p><strong>Hi Admin,</strong></p>
                <p>A new campaign has been created on ObiFunds.</p>
                <div class='detail'>
                    <p><span class='label'>📌 Title:</span> $title</p>
                    <p><span class='label'>👤 Campaigner:</span> $name</p>
                    <p><span class='label'>📧 Email:</span> $email</p>
                    <p><span class='label'>📱 Phone:</span> $phone</p>
                    <p><span class='label'>📂 Category:</span> $cat</p>
                    <p><span class='label'>💰 Goal:</span> $curr $goal</p>
                    <p><span class='label'>🌍 Country:</span> $country</p>
                </div>
                <p style='text-align: center;'><a href='$link' class='btn'>🔍 View Campaign</a></p>
            </div>
            <div class='footer'><p>&copy; 2026 ObiFunds. All rights reserved.</p></div>
        </div>
    </body>
    </html>
    ";
}

// ── In-App Notification ─────────────────────────────────────
function saveInAppNotification($conn, $campaign_data) {
    $title   = $conn->real_escape_string('New Campaign: ' . $campaign_data['title']);
    $message = $conn->real_escape_string($campaign_data['campaigner_name'] . ' created "' . $campaign_data['title'] . '"');
    $link    = $conn->real_escape_string('/admin/index.php?tab=campaigns&view=' . $campaign_data['campaign_id']);
    // admin_notifications table may not exist — wrap in try/catch
    try {
        $conn->query(
            "INSERT INTO admin_notifications (type, title, message, link, is_read, created_at)
             VALUES ('new_campaign', '$title', '$message', '$link', 0, NOW())"
        );
    } catch (\Throwable $e) {
        error_log('admin_notifications insert: ' . $e->getMessage());
    }
}

// ── Main Notification Function ─────────────────────────────
function notifyNewCampaign($conn, $campaign_id, $campaign_data) {
    sendCampaignCreationEmail($campaign_data);
    saveInAppNotification($conn, $campaign_data);
}
?>