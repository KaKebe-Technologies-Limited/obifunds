<?php
// ============================================================
// ObiFunds – campaign-detail.php (GoFundMe Style with Images)
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$id        = (int)($_GET['id']   ?? 0);
$slug      = $conn->real_escape_string($_GET['slug'] ?? '');
$condition = $id ? "c.campaign_id = $id" : "c.slug = '$slug'";

$result = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name, u.email AS campaigner_email,
            u.avatar_url AS campaigner_avatar, u.phone AS campaigner_phone,
            ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
            DATEDIFF(c.end_date, NOW()) AS days_left
     FROM campaigns c JOIN users u ON c.campaigner_id = u.user_id
     WHERE $condition LIMIT 1"
);
if (!$result || $result->num_rows === 0) {
    http_response_code(404);
    include __DIR__ . '/includes/header.php';
    echo '<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding-top:80px;">
    <div style="text-align:center;padding:40px;">
      <i class="fas fa-search" style="font-size:3rem;color:#d1d5db;margin-bottom:16px;display:block;"></i>
      <h2 style="color:var(--green-dark);font-weight:800;margin-bottom:8px;">Campaign Not Found</h2>
      <p style="color:#9ca3af;margin-bottom:24px;">This campaign may have been removed or the link is incorrect.</p>
      <a href="<?= BASE ?>/campaign-drives.php" class="btn btn-primary">Browse Campaigns</a>
    </div></div>';
    include __DIR__ . '/includes/footer.php'; exit;
}
$c   = $result->fetch_assoc();
$cid = $c['campaign_id'];
$conn->query("UPDATE campaigns SET view_count = view_count + 1 WHERE campaign_id = $cid");

// ── Category hero images ──────────────────────────────────────
$categoryHeros = [
    'Medical'    => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&q=80',
    'Education'  => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&q=80',
    'Community'  => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=800&q=80',
    'Family'     => 'https://images.unsplash.com/photo-1511895426328-dc8714191011?w=800&q=80',
    'Business'   => 'https://images.unsplash.com/photo-1556761175-4b46a572b786?w=800&q=80',
    'Emergency'  => 'https://images.unsplash.com/photo-1584820927498-cfe5211fd8bf?w=800&q=80',
    'Marriage'   => 'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=800&q=80',
    'Funeral'    => 'https://images.unsplash.com/photo-1501436513145-30f24e19fcc8?w=800&q=80',
    'Agriculture'=> 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=800&q=80',
    'Religion'   => 'https://images.unsplash.com/photo-1438232992991-995b671e4b8a?w=800&q=80',
    'Sports'     => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=800&q=80',
    'Other'      => 'https://images.unsplash.com/photo-1531206715517-5c0ba140b2b8?w=800&q=80',
];
$heroImg = $categoryHeros[$c['category']] ?? $categoryHeros['Other'];

// ── Uploaded campaign images ─────────────────────────────────
$imgsResult = $conn->query(
    "SELECT image_id, image_url, is_cover, sort_order FROM campaign_images
     WHERE campaign_id=$cid ORDER BY is_cover DESC, sort_order ASC LIMIT 10"
);
$campaignImages = [];
while ($img = $imgsResult->fetch_assoc()) {
    $imgUrl = $img['image_url'];
    if (!empty($imgUrl) && strpos($imgUrl, 'http') !== 0) {
        $imgUrl = BASE . '/' . ltrim($imgUrl, '/');
    }
    $campaignImages[] = ['image_id' => $img['image_id'], 'image_url' => $imgUrl, 'is_cover' => $img['is_cover'], 'sort_order' => $img['sort_order']];
}
if (empty($campaignImages) && !empty($c['image_url'])) {
    $imgUrl = $c['image_url'];
    if (!empty($imgUrl) && strpos($imgUrl, 'http') !== 0) {
        $imgUrl = BASE . '/' . ltrim($imgUrl, '/');
    }
    $campaignImages[] = ['image_id' => 0, 'image_url' => $imgUrl, 'is_cover' => 1, 'sort_order' => 0];
}
if (empty($campaignImages)) {
    $campaignImages[] = ['image_id' => 0, 'image_url' => $heroImg, 'is_cover' => 1, 'sort_order' => 0];
}

// ── Donations ────────────────────────────────────────────────
$dons = $conn->query(
    "SELECT donor_name, is_anonymous, amount, mobile_money_network, payment_date
     FROM donations WHERE campaign_id=$cid AND status='completed'
     ORDER BY payment_date DESC LIMIT 20"
);
$totalDonorsAll = (int)$conn->query(
    "SELECT COUNT(*) FROM donations WHERE campaign_id=$cid AND status='completed'"
)->fetch_row()[0];

$recentDons = $conn->query(
    "SELECT donor_name, is_anonymous, amount, payment_date
     FROM donations WHERE campaign_id=$cid AND status='completed'
     ORDER BY payment_date DESC LIMIT 10"
);
$recentDonsArr = [];
while ($rd = $recentDons->fetch_assoc()) $recentDonsArr[] = $rd;

$pct       = min(100, (float)$c['pct']);
$daysLeft  = (int)$c['days_left'];
$daysStr   = $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Campaign ended');
$remaining = max(0, $c['goal_amount'] - $c['raised_amount']);
$isOwner   = isset($_SESSION['user_id']) &&
             ($_SESSION['user_id'] == $c['campaigner_id'] || ($_SESSION['role'] ?? '') === 'admin');

$protocol     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $protocol = trim($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : $protocol;
}
$canonicalUrl = BASE . '/campaign-detail.php?id=' . $cid;

// ── OG Image ──────────────────────────────────────────────────
$ogImage = $campaignImages[0]['image_url'] ?? $heroImg;
if (!empty($ogImage) && strpos($ogImage, 'http') !== 0) {
    $host = $_SERVER['HTTP_HOST'];
    $ogImage = $protocol . '://' . $host . '/' . ltrim($ogImage, '/');
}
$ogImage = str_replace('http://', 'https://', $ogImage);

$ogTitle      = htmlspecialchars($c['title'], ENT_QUOTES);
$ogDesc       = htmlspecialchars(
    ($c['currency'] . ' ' . number_format($c['raised_amount']) . ' raised · ' .
     number_format($totalDonorsAll) . ' supporters · ' .
     substr(strip_tags($c['description']), 0, 120) . '…'),
    ENT_QUOTES
);
$pageTitle    = htmlspecialchars($c['title']).' – ObiFunds';
$pageDescription = $ogDesc;

// ── Share text ────────────────────────────────────────────────
$raised_fmt   = number_format($c['raised_amount']);
$goal_fmt     = number_format($c['goal_amount']);
$shareText    = "🙏 " . $c['title'] . "\n\n"
              . substr(strip_tags($c['description']), 0, 140) . "…\n\n"
              . "💰 " . $c['currency'] . " " . $raised_fmt . " raised of " . $c['currency'] . " " . $goal_fmt . " goal\n"
              . "👥 " . $totalDonorsAll . " givers · " . $daysStr . "\n\n"
              . "🔗 Donate here: " . $canonicalUrl
              . "\n\n#ObiFunds #MobileMoney #Uganda";
$shareTextEnc  = urlencode($shareText);
$shareTitleEnc = urlencode($c['title'] . ' – ObiFunds');
$shareUrlEnc   = urlencode($canonicalUrl);

$extraCss = <<<HTML
  <!-- Open Graph -->
  <meta property="og:type"              content="website"/>
  <meta property="og:url"               content="{$canonicalUrl}"/>
  <meta property="og:title"             content="{$ogTitle}"/>
  <meta property="og:description"       content="{$ogDesc}"/>
  <meta property="og:image"             content="{$ogImage}"/>
  <meta property="og:image:secure_url"  content="{$ogImage}"/>
  <meta property="og:image:width"       content="1200"/>
  <meta property="og:image:height"      content="630"/>
  <meta property="og:image:alt"         content="{$ogTitle}"/>
  <meta property="og:site_name"         content="ObiFunds"/>
  <meta property="og:locale"            content="en_US"/>
  <meta name="twitter:card"             content="summary_large_image"/>
  <meta name="twitter:site"             content="@ObiFunds"/>
  <meta name="twitter:title"            content="{$ogTitle}"/>
  <meta name="twitter:description"      content="{$ogDesc}"/>
  <meta name="twitter:image"            content="{$ogImage}"/>
  <link rel="canonical"                 href="{$canonicalUrl}"/>
HTML;

include __DIR__ . '/includes/header.php';
?>

<style>
/* ── Hero Section ── */
.donation-hero {
    background: var(--green-dark);
    padding: 30px 0 20px;
    color: #fff;
}
.donation-hero .campaign-title {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 12px;
    line-height: 1.2;
}
.donation-hero .campaigner-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 16px 0;
    flex-wrap: wrap;
}
.donation-hero .campaigner-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 800;
    color: #fff;
}
.donation-hero .campaigner-name {
    font-weight: 600;
}
.donation-hero .protected-badge {
    background: rgba(255,255,255,0.15);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.donation-hero .progress-stats {
    display: flex;
    align-items: flex-end;
    gap: 24px;
    margin: 16px 0;
    flex-wrap: wrap;
}
.donation-hero .progress-percent {
    font-size: 2.5rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}
.donation-hero .progress-amounts {
    font-size: 1.1rem;
    font-weight: 600;
}
.donation-hero .progress-amounts small {
    font-weight: 400;
    opacity: 0.8;
}
.donation-hero .progress-bar {
    height: 8px;
    background: rgba(255,255,255,0.2);
    border-radius: 99px;
    overflow: hidden;
    margin-top: 8px;
    max-width: 400px;
}
.donation-hero .progress-bar .fill {
    height: 100%;
    background: #fff;
    border-radius: 99px;
    transition: width 1s ease;
}
.donation-hero .action-buttons {
    display: flex;
    gap: 12px;
    margin: 16px 0;
    flex-wrap: wrap;
}
.donation-hero .btn-donate {
    background: var(--green);
    color: #fff;
    border: none;
    padding: 14px 32px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}
.donation-hero .btn-donate:hover {
    background: var(--green-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26,122,60,0.3);
}
.donation-hero .btn-share {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 14px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.donation-hero .btn-share:hover {
    background: rgba(255,255,255,0.25);
}

/* ── Campaign Images ── */
.campaign-images-section {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 24px;
    box-shadow: 0 1px 12px rgba(0,0,0,0.06);
}
.campaign-images-section .main-image {
    width: 100%;
    max-height: 500px;
    object-fit: cover;
    display: block;
}
.campaign-images-section .thumbnails {
    display: flex;
    gap: 4px;
    padding: 6px;
    background: #f8fafc;
    overflow-x: auto;
    scrollbar-width: none;
}
.campaign-images-section .thumbnails::-webkit-scrollbar {
    display: none;
}
.campaign-images-section .thumbnails .thumb {
    width: 80px;
    height: 60px;
    border-radius: 6px;
    overflow: hidden;
    border: 2px solid transparent;
    cursor: pointer;
    flex-shrink: 0;
}
.campaign-images-section .thumbnails .thumb.active {
    border-color: var(--green);
}
.campaign-images-section .thumbnails .thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ── Main Layout ── */
.donation-body {
    padding: 30px 0 80px;
    background: #f8fafc;
}
.donation-body .container {
    overflow: visible;
}
.donation-body .main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 32px;
    align-items: start;
}

/* ── Left Column ── */
.donation-body .left-col {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.donation-body .story-section {
    background: #fff;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 1px 12px rgba(0,0,0,.06);
    min-width: 0;
}
.donation-body .story-section h2 {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--green-dark);
    margin-bottom: 14px;
}
.donation-body .story-section div,
.donation-body .story-section p,
.donation-body .story-section .story-content {
    color: #334155;
    line-height: 1.75;
    font-size: 0.93rem;
    word-break: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
}

/* ── Recent Donations — right column, compact ── */
.donation-body .recent-donations {
    background: #fff;
    border-radius: 14px;
    padding: 18px 16px;
    box-shadow: 0 1px 10px rgba(0,0,0,.06);
    min-width: 0;
}
.donation-body .recent-donations .section-title {
    font-size: .86rem;
    font-weight: 800;
    color: var(--green-dark);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
}
.donation-body .recent-donations .donation-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    gap: 6px;
}
.donation-body .recent-donations .donation-item:last-child {
    border-bottom: none;
}
.donation-body .recent-donations .donor-name {
    font-weight: 700;
    color: var(--green-dark);
    font-size: .82rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}
.donation-body .recent-donations .donor-amount {
    font-weight: 800;
    color: var(--green);
    font-size: .82rem;
    white-space: nowrap;
    flex-shrink: 0;
}
.donation-body .recent-donations .donor-time {
    font-size: .7rem;
    color: #94a3b8;
}
.donation-body .recent-donations .top-donation {
    background: #fef3c7;
    padding: 1px 6px;
    border-radius: 99px;
    font-size: .6rem;
    font-weight: 700;
    color: #92400e;
    margin-left: 3px;
}

/* ── Right Column (Donation Widget) ── */
.donation-body .right-col {
    min-width: 0;
    align-self: start;
}
.donation-widget {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 32px rgba(0,0,0,0.08);
    position: sticky;
    top: 90px;
    min-width: 0;
    overflow: visible;
}
.donation-widget .widget-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--green-dark);
    margin-bottom: 16px;
}

/* Quick amount pills - smaller */
.donation-widget .quick-pills {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.donation-widget .quick-pills button {
    padding: 6px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 20px;
    background: #fff;
    font-weight: 600;
    font-size: 0.75rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}
.donation-widget .quick-pills button:hover {
    border-color: var(--green);
    color: var(--green);
}
.donation-widget .quick-pills button.selected {
    border-color: var(--green);
    background: var(--green);
    color: #fff;
}

/* Big custom amount input */
.donation-widget .big-amount-input {
    width: 100%;
    padding: 18px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 12px;
    box-sizing: border-box;
    text-align: center;
}
.donation-widget .big-amount-input:focus {
    border-color: var(--green);
    outline: none;
}
.donation-widget .big-amount-input::placeholder {
    font-size: 1rem;
    font-weight: 400;
    color: #94a3b8;
}

.donation-widget .donate-btn {
    width: 100%;
    padding: 16px;
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 14px rgba(26,122,60,0.35);
}
.donation-widget .donate-btn:hover {
    background: var(--green-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(26,122,60,0.4);
}
.donation-widget .donate-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
.donation-widget .trust-badges {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 16px;
    font-size: 0.7rem;
    color: #94a3b8;
}
.donation-widget .trust-badges i {
    color: var(--green);
}

/* ── Donation Modal ── */
.donation-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 5000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.donation-modal-overlay.open {
    display: flex;
}
.donation-modal {
    background: #fff;
    border-radius: 24px;
    max-width: 460px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 28px;
    animation: modalSlideUp 0.3s ease;
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes successPop {
    0%   { transform: scale(0.3); opacity: 0; }
    70%  { transform: scale(1.1); }
    100% { transform: scale(1);   opacity: 1; }
}
.donation-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.modal-header-left h2 {
    font-size: 1.15rem;
    font-weight: 900;
    color: var(--green-dark);
    margin: 0;
}
.modal-header-left p {
    font-size: 0.78rem;
    color: #94a3b8;
    margin: 0;
}
.donation-modal .modal-close {
    background: #f8fafc;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 1.1rem;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}
.donation-modal .modal-close:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.donation-modal .modal-step-label {
    font-size: 0.72rem;
    font-weight: 800;
    color: var(--green);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 2px;
}
.donation-modal .modal-step-title {
    font-size: 1rem;
    font-weight: 800;
    color: var(--green-dark);
    margin-bottom: 16px;
}
.donation-modal .mq-pill-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}
.donation-modal .mq-btn {
    padding: 11px 8px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    font-weight: 700;
    font-size: 0.88rem;
    color: #475569;
    cursor: pointer;
    transition: all 0.18s;
}
.donation-modal .mq-btn:hover {
    border-color: var(--green);
    color: var(--green);
}
.donation-modal .mq-btn.selected {
    border-color: var(--green);
    background: var(--green);
    color: #fff;
}
.donation-modal .modal-amount-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f0fdf4;
    color: var(--green-dark);
    font-size: 0.9rem;
    font-weight: 800;
    padding: 8px 16px;
    border-radius: 99px;
    margin-bottom: 16px;
    border: 1.5px solid rgba(26,122,60,0.2);
}
.donation-modal .modal-amount-chip button {
    background: none;
    border: none;
    color: var(--green);
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: underline;
}
.donation-modal .pay-method-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 16px;
}
.donation-modal .pay-method-tab {
    padding: 12px 10px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    font-weight: 700;
    font-size: 0.84rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}
.donation-modal .pay-method-tab:hover {
    border-color: var(--green);
    color: var(--green);
}
.donation-modal .pay-method-tab.active {
    border-color: var(--green);
    background: #f0fdf4;
    color: var(--green-dark);
}
.donation-modal .form-group {
    margin-bottom: 14px;
}
.donation-modal .form-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 4px;
}
.donation-modal .form-group label span {
    color: var(--green);
}
.donation-modal .form-group input,
.donation-modal .form-group select {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9rem;
    box-sizing: border-box;
    transition: border-color 0.15s;
}
.donation-modal .form-group input:focus,
.donation-modal .form-group select:focus {
    border-color: var(--green);
    outline: none;
}
.donation-modal .field-error {
    color: #ef4444;
    font-size: 0.74rem;
    margin-top: 4px;
    display: none;
}
.donation-modal .field-error.show {
    display: block;
}
.donation-modal .anonymous-toggle {
    display: flex;
    align-items: center;
    gap: 9px;
    background: #f8fafc;
    border-radius: 10px;
    padding: 10px 12px;
    font-size: 0.84rem;
    color: #475569;
    margin: 12px 0;
    cursor: pointer;
}
.donation-modal .anonymous-toggle input {
    width: 16px;
    height: 16px;
    accent-color: var(--green);
}
.donation-modal .modal-summary {
    background: #f0fdf4;
    border: 1.5px solid rgba(26,122,60,0.15);
    padding: 14px 16px;
    border-radius: 12px;
    margin: 14px 0;
}
.donation-modal .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 3px 0;
    font-size: 0.84rem;
    color: #64748b;
}
.donation-modal .summary-row strong {
    color: var(--green-dark);
}
.donation-modal .summary-total {
    border-top: 1px solid rgba(26,122,60,0.2);
    padding-top: 8px;
    margin-top: 6px;
    font-weight: 800;
    color: var(--green-dark);
}
.donation-modal .modal-submit-btn {
    width: 100%;
    padding: 15px;
    background: var(--green);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.25s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(26,122,60,0.35);
}
.donation-modal .modal-submit-btn:hover:not(:disabled) {
    background: var(--green-dark);
    transform: translateY(-1px);
}
.donation-modal .modal-submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.donation-modal .modal-submit-btn.btn-back {
    background: #f1f5f9;
    color: #64748b;
    box-shadow: none;
    margin-top: 8px;
}
.donation-modal .modal-submit-btn.btn-back:hover {
    background: #e2e8f0;
}

/* ── MOBILE ── */
@media (max-width: 1023px) {
    .donation-body .main-grid { grid-template-columns: 1fr; gap: 20px; }
    .donation-widget { position: static; }
}
@media (max-width: 767px) {
    .donation-hero .campaign-title { font-size: 1.25rem; }
    .donation-hero .progress-stats { gap: 10px; }
    .donation-hero .progress-percent { font-size: 1.7rem; }
    .donation-body { padding: 18px 0 60px; }
    .donation-body .story-section,
    .donation-body .recent-donations { padding: 18px 16px; }
    .donation-modal { padding: 18px 16px 24px; }
    .donation-hero .action-buttons { flex-direction: column; }
    .donation-hero .btn-donate,
    .donation-hero .btn-share { width: 100%; }
    .donation-modal .mq-pill-grid { grid-template-columns: repeat(3,1fr); }
}
@media (max-width: 400px) {
    .donation-modal .mq-pill-grid { grid-template-columns: repeat(2,1fr); }
}
/* ── FIX: Prevent footer from overlapping donation widget ── */
.donation-body {
    padding-bottom: 100px !important;
}

footer, .footer, .site-footer, #footer {
    position: relative !important;
    clear: both !important;
    z-index: 2 !important;
    margin-top: 0 !important;
}

.donation-widget {
    position: sticky;
    top: 90px;
}

@media (max-width: 767px) {
    .donation-body { padding-bottom: 80px; }
    .donation-widget { position: static; }
}
</style>

<div class="donation-hero">
    <div class="container">
        <!-- Campaign Title -->
        <h1 class="campaign-title"><?= htmlspecialchars($c['title']) ?></h1>
        
        <!-- Campaigner Info -->
        <div class="campaigner-info">
            <div class="campaigner-avatar">
                <?= strtoupper(substr($c['campaigner_name'], 0, 1)) ?>
            </div>
            <span class="campaigner-name"><?= htmlspecialchars($c['campaigner_name']) ?></span>
            <span class="protected-badge"><i class="fas fa-shield-alt"></i> Donation protected</span>
        </div>

        <!-- Progress -->
        <div class="progress-stats">
            <div class="progress-percent"><?= round($pct) ?>%</div>
            <div class="progress-amounts">
                <?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> raised of <?= $c['currency'] ?> <?= number_format($c['goal_amount']) ?>
                <br><small><?= number_format($totalDonorsAll) ?> donations</small>
            </div>
        </div>
        <div class="progress-bar" style="max-width:400px;">
            <div class="fill" style="width:<?= $pct ?>%"></div>
        </div>

        <!-- Actions -->
        <div class="action-buttons">
            <button class="btn-donate" id="openDonateModalBtn" onclick="scrollToDonate()">
                <i class="fas fa-hand-holding-heart"></i> Donate Now
            </button>
            <button class="btn-share" onclick="toggleSharePanel()">
                <i class="fas fa-share-nodes"></i> Share
            </button>
        </div>

        <!-- Share panel -->
        <div id="sharePanel" style="display:none;margin-top:14px;">
          <div style="background:rgba(255,255,255,.12);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2);border-radius:14px;padding:16px;">
            <p style="font-size:.78rem;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;">Share this drive</p>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
              <a href="https://wa.me/?text=<?= $shareTextEnc ?>" target="_blank" rel="noopener"
                 style="display:inline-flex;align-items:center;gap:7px;background:#25D366;color:#fff;padding:9px 16px;border-radius:99px;font-size:.82rem;font-weight:700;text-decoration:none;">
                <i class="fab fa-whatsapp" style="font-size:1rem;"></i> WhatsApp
              </a>
              <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrlEnc ?>&quote=<?= $shareTextEnc ?>" target="_blank" rel="noopener"
                 style="display:inline-flex;align-items:center;gap:7px;background:#1877F2;color:#fff;padding:9px 16px;border-radius:99px;font-size:.82rem;font-weight:700;text-decoration:none;">
                <i class="fab fa-facebook-f" style="font-size:.9rem;"></i> Facebook
              </a>
              <a href="https://twitter.com/intent/tweet?text=<?= $shareTextEnc ?>&url=<?= $shareUrlEnc ?>" target="_blank" rel="noopener"
                 style="display:inline-flex;align-items:center;gap:7px;background:#000;color:#fff;padding:9px 16px;border-radius:99px;font-size:.82rem;font-weight:700;text-decoration:none;">
                <i class="fab fa-x-twitter" style="font-size:.9rem;"></i> X / Twitter
              </a>
              <a href="https://t.me/share/url?url=<?= $shareUrlEnc ?>&text=<?= $shareTextEnc ?>" target="_blank" rel="noopener"
                 style="display:inline-flex;align-items:center;gap:7px;background:#0088cc;color:#fff;padding:9px 16px;border-radius:99px;font-size:.82rem;font-weight:700;text-decoration:none;">
                <i class="fab fa-telegram-plane" style="font-size:.9rem;"></i> Telegram
              </a>
              <button onclick="copyShareLink(this)"
                 style="display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;padding:9px 16px;border-radius:99px;font-size:.82rem;font-weight:700;cursor:pointer;">
                <i class="fas fa-link"></i> Copy Link
              </button>
            </div>
            <div style="margin-top:10px;display:flex;align-items:center;gap:8px;">
              <input id="shareLinkInput" type="text" value="<?= htmlspecialchars($canonicalUrl) ?>" readonly
                style="flex:1;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:8px;color:#fff;padding:8px 12px;font-size:.78rem;outline:none;min-width:0;"/>
            </div>
          </div>
        </div>
    </div>
</div>

<div class="donation-body">
    <div class="container">
        <div class="main-grid">
            <!-- LEFT: Images + Story only -->
            <div class="left-col">
                <!-- Campaign Images -->
                <?php if (!empty($campaignImages)): ?>
                <div class="campaign-images-section">
                    <img src="<?= htmlspecialchars($campaignImages[0]['image_url']) ?>" alt="<?= htmlspecialchars($c['title']) ?>" class="main-image" id="mainCampaignImage" />
                    <?php if (count($campaignImages) > 1): ?>
                    <div class="thumbnails" id="imageThumbnails">
                        <?php foreach ($campaignImages as $i => $img): ?>
                        <div class="thumb <?= $i === 0 ? 'active' : '' ?>" onclick="switchMainImage(this, '<?= htmlspecialchars($img['image_url']) ?>')">
                            <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="Campaign image <?= $i+1 ?>" />
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Story -->
                <div class="story-section">
                    <h2>The Story</h2>
                    <div class="story-content"><?= nl2br(htmlspecialchars($c['description'])) ?></div>
                </div>
            </div>

            <!-- RIGHT: Donation Widget + Recent Donations -->
            <div class="right-col">
                <!-- Donation widget -->
                <div class="donation-widget" id="donationWidgetAnchor">
                    <div class="widget-title">Support This Drive</div>

                    <div id="widgetError" style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;font-size:0.85rem;display:none;margin-bottom:12px;"></div>

                    <!-- Quick amount pills -->
                    <div class="quick-pills">
                        <button class="q-btn" data-amount="5000">5K</button>
                        <button class="q-btn" data-amount="10000">10K</button>
                        <button class="q-btn" data-amount="20000">20K</button>
                        <button class="q-btn" data-amount="50000">50K</button>
                    </div>

                    <!-- Amount input -->
                    <input type="number" id="customAmountInput" class="big-amount-input" placeholder="Enter amount (UGX)" min="500" />

                    <button class="donate-btn" id="openDonateModalBtn2">
                        <i class="fas fa-hand-holding-heart"></i> Donate Now
                    </button>

                    <div class="trust-badges">
                        <span><i class="fas fa-lock"></i> Secured</span>
                        <span><i class="fas fa-shield-alt"></i> Verified</span>
                        <span><i class="fas fa-check-circle"></i> Protected</span>
                    </div>
                </div>

                <!-- Recent Donations — below widget -->
                <div class="recent-donations" style="margin-top:16px;">
                    <div class="section-title">
                        <i class="fas fa-users" style="color:var(--green);margin-right:6px;"></i>
                        <?= number_format($totalDonorsAll) ?> <?= $totalDonorsAll === 1 ? 'giver' : 'givers' ?>
                    </div>
                    <?php if (!empty($recentDonsArr)): ?>
                        <?php foreach ($recentDonsArr as $rd): ?>
                        <div class="donation-item">
                            <div style="display:flex;align-items:center;gap:9px;">
                                <!-- Avatar initial -->
                                <?php
                                $dname   = $rd['is_anonymous'] ? 'Anonymous' : $rd['donor_name'];
                                $initial = strtoupper(substr($dname,0,1));
                                $colours = ['#1a7a3c','#145f2e','#d97706','#2563eb','#7c3aed','#dc2626'];
                                $col     = $colours[ord($initial) % count($colours)];
                                ?>
                                <div style="width:34px;height:34px;border-radius:50%;background:<?= $col ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.78rem;flex-shrink:0;">
                                    <?= $initial ?>
                                </div>
                                <div>
                                    <div class="donor-name">
                                        <?= $rd['is_anonymous'] ? 'Anonymous' : htmlspecialchars($rd['donor_name']) ?>
                                        <?php if ($rd['amount'] >= 500000): ?>
                                        <span class="top-donation">🏆 Top</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="donor-time">
                                        <?php
                                        $diff = time() - strtotime($rd['payment_date']);
                                        if      ($diff < 60)    echo 'Just now';
                                        elseif  ($diff < 3600)  echo floor($diff/60) . 'm ago';
                                        elseif  ($diff < 86400) echo floor($diff/3600) . 'h ago';
                                        else                    echo floor($diff/86400) . 'd ago';
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="donor-amount">
                                +<?= $c['currency'] ?> <?= number_format($rd['amount']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#94a3b8;font-size:.86rem;text-align:center;padding:20px 0;">
                            <i class="fas fa-heart" style="display:block;font-size:1.5rem;margin-bottom:8px;color:#e2e8f0;"></i>
                            Be the first to give!
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── DONATION MODAL ─────────────────────────────────────────── -->
<div class="donation-modal-overlay" id="donationModal">
  <div class="donation-modal">

    <!-- Header -->
    <div class="modal-header">
      <div class="modal-header-left">
        <h2><i class="fas fa-hand-holding-heart" style="color:var(--green);margin-right:8px;"></i>Make a Donation</h2>
        <p>Fast · Secure · Mobile Money</p>
      </div>
      <button class="modal-close" onclick="closeDonationModal()" aria-label="Close">&times;</button>
    </div>

    <!-- ── STEP 1: Choose Amount ── -->
    <div id="modalStep1">
      <div class="modal-step-label">Step 1 of 2</div>
      <div class="modal-step-title">How much would you like to give?</div>

      <div class="mq-pill-grid">
        <button class="mq-btn" data-amount="5000">UGX 5K</button>
        <button class="mq-btn" data-amount="10000">UGX 10K</button>
        <button class="mq-btn" data-amount="20000">UGX 20K</button>
        <button class="mq-btn" data-amount="50000">UGX 50K</button>
        <button class="mq-btn" data-amount="100000">UGX 100K</button>
        <button class="mq-btn" data-amount="custom">Other</button>
      </div>

      <input type="number" id="modalCustomAmount" class="big-amount-input"
             placeholder="Enter custom amount" min="1000" style="display:none;"/>
      <div id="modalAmountError" style="color:#ef4444;font-size:.8rem;display:none;margin-bottom:8px;">
        Minimum donation is UGX 1,000.
      </div>

      <button class="modal-submit-btn" id="modalAmountNextBtn">
        Continue to Payment <i class="fas fa-arrow-right"></i>
      </button>
    </div>

    <!-- ── STEP 2: Payment Details ── -->
    <div id="modalStep2" style="display:none;">
      <div class="modal-step-label">Step 2 of 2</div>
      <div class="modal-step-title">Payment Details</div>

      <!-- Amount chip with edit link -->
      <div class="modal-amount-chip" id="modalAmountChip">
        <i class="fas fa-tag"></i>
        <span id="chipAmountText">UGX 0</span>
        <button onclick="goBackToStep1()">Change</button>
      </div>

      <!-- Payment method tabs -->
      <div class="pay-method-tabs">
        <button class="pay-method-tab active" id="methodMobile" data-method="mobile">
          <i class="fas fa-mobile-screen-button"></i> Mobile Money
        </button>
        <button class="pay-method-tab" id="methodCard" data-method="card">
          <i class="fas fa-credit-card"></i> Card
          <span class="tab-badge">Soon</span>
        </button>
      </div>

      <!-- Mobile Money Fields -->
      <div id="mobileFields">
        <div class="form-group">
          <label>Phone Number <span>*</span></label>
          <input type="tel" id="modalPhone" placeholder="e.g. 256712345678" autocomplete="tel"/>
          <div class="field-error" id="modalPhoneError">Enter a valid phone number.</div>
        </div>
        <div class="form-group">
          <label>Network <span>*</span></label>
          <select id="modalNetwork">
            <option>MTN Mobile Money</option>
            <option>Airtel Money</option>
            <option>Orange Money</option>
            <option>Safaricom M-PESA</option>
          </select>
        </div>
      </div>

      <!-- Card — coming soon placeholder -->
      <div id="cardFields" style="display:none;">
        <div class="card-coming-soon">
          <i class="fas fa-credit-card"></i>
          <h4>Card Payments Coming Soon</h4>
          <p>We're working on Visa &amp; Mastercard support.<br/>Please use Mobile Money for now.</p>
        </div>
      </div>

      <!-- Donor Info -->
      <div class="form-group">
        <label>Your Name <span>*</span></label>
        <input type="text" id="modalName" placeholder="Full name" autocomplete="name"/>
        <div class="field-error" id="modalNameError">Please enter your name.</div>
      </div>
      <div class="form-group">
        <label>Email <em style="font-weight:400;color:#94a3b8;">(optional)</em></label>
        <input type="email" id="modalEmail" placeholder="you@email.com" autocomplete="email"/>
      </div>

      <label class="anonymous-toggle">
        <input type="checkbox" id="modalAnonymous"/>
        <span>Remain anonymous — your name won't appear publicly</span>
      </label>

      <!-- Summary -->
      <div class="modal-summary">
        <div class="summary-row"><span>Donation amount</span><strong id="summaryAmount">UGX 0</strong></div>
        <div class="summary-row"><span>Method</span><strong id="summaryMethod">Mobile Money</strong></div>
        <div class="summary-row"><span>Donor</span><strong id="summaryDonor">—</strong></div>
        <div class="summary-row summary-total"><span>Total</span><strong id="summaryTotal">UGX 0</strong></div>
      </div>

      <div id="widgetError" style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:10px;font-size:.84rem;display:none;margin-bottom:10px;border-left:3px solid #ef4444;"></div>

      <button class="modal-submit-btn" id="modalSubmitBtn">
        <i class="fas fa-heart"></i> Donate <span id="submitAmountText">UGX 0</span>
      </button>
      <button class="modal-submit-btn btn-back" id="modalBackBtn">
        <i class="fas fa-arrow-left"></i> Back
      </button>
    </div>

    <!-- ── Loading State ── -->
    <div id="modalLoading" style="display:none;text-align:center;padding:48px 0;">
      <div style="width:56px;height:56px;background:var(--green-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <i class="fas fa-spinner fa-spin" style="font-size:1.6rem;color:var(--green);"></i>
      </div>
      <p style="font-weight:800;color:var(--green-dark);margin-bottom:6px;">Sending payment prompt…</p>
      <p style="font-size:.84rem;color:#94a3b8;">Check your phone for the mobile money request</p>
    </div>

    <!-- ── Success State ── -->
    <div id="modalSuccess" style="display:none;text-align:center;padding:32px 8px;">
      <!-- Animated checkmark -->
      <div style="width:80px;height:80px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(26,122,60,.3);animation:successPop .4s cubic-bezier(.34,1.56,.64,1) both;">
        <i class="fas fa-check" style="font-size:2.2rem;color:#fff;"></i>
      </div>
      <h3 style="color:var(--green-dark);font-weight:900;font-size:1.35rem;margin-bottom:6px;letter-spacing:-.02em;">Payment Sent! 🎉</h3>
      <p style="color:#64748b;font-size:.9rem;margin-bottom:6px;">
        Your donation of <strong id="successAmount" style="color:var(--green-dark);">UGX 0</strong> is being processed.
      </p>
      <p style="color:#94a3b8;font-size:.82rem;margin-bottom:24px;">Check your phone for the mobile money prompt and enter your PIN to complete.</p>

      <!-- Countdown bar -->
      <div style="background:var(--gray-100);border-radius:99px;height:5px;overflow:hidden;margin:0 auto 16px;max-width:220px;">
        <div id="successBar" style="height:100%;background:var(--green);border-radius:99px;width:100%;transition:width 5s linear;"></div>
      </div>
      <p style="font-size:.78rem;color:#94a3b8;margin-bottom:20px;" id="successCountdown">Payment prompt sent — enter your PIN on your phone.</p>

      <button class="modal-submit-btn" id="modalDoneBtn" style="max-width:260px;margin:0 auto;">
        <i class="fas fa-check"></i> Done — Back to Drive
      </button>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// ── Image Switcher ────────────────────────────────────────────
function switchMainImage(el, url) {
    var img = document.getElementById('mainCampaignImage');
    if (img) img.src = url;
    document.querySelectorAll('.thumbnails .thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

// ── Widget Quick Amount Pills ─────────────────────────────────
document.querySelectorAll('.q-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.q-btn').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');
        var inp = document.getElementById('customAmountInput');
        if (inp) inp.value = this.dataset.amount;
    });
});
var widgetInput = document.getElementById('customAmountInput');
if (widgetInput) {
    widgetInput.addEventListener('input', function() {
        document.querySelectorAll('.q-btn').forEach(b => b.classList.remove('selected'));
    });
}

// ── Widget scroll + open ──────────────────────────────────────
function scrollToDonate() {
    var widget = document.getElementById('donationWidgetAnchor');
    if (!widget) { openDonationModal(); return; }
    var offset = widget.getBoundingClientRect().top + window.scrollY - 100;
    window.scrollTo({ top: offset, behavior: 'smooth' });
    setTimeout(function() {
        var amt = document.getElementById('customAmountInput').value;
        if (amt && parseInt(amt) >= 500) window._preSelectedAmount = parseInt(amt);
        openDonationModal();
    }, window.innerWidth <= 1023 ? 600 : 100);
}

// ── Open Modal from widget buttons ───────────────────────────
['openDonateModalBtn2'].forEach(function(id) {
    var btn = document.getElementById(id);
    if (!btn) return;
    btn.addEventListener('click', function() {
        var val = document.getElementById('customAmountInput').value;
        if (val && parseInt(val) >= 500) window._preSelectedAmount = parseInt(val);
        openDonationModal();
    });
});

// ── Open / Close / Reset ──────────────────────────────────────
function openDonationModal() {
    resetModal();

    if (window._preSelectedAmount) {
        var amt = window._preSelectedAmount;
        window._donationAmount = amt;
        window._preSelectedAmount = null;

        var matched = false;
        document.querySelectorAll('.mq-btn').forEach(function(b) {
            if (parseInt(b.dataset.amount) === amt) { b.classList.add('selected'); matched = true; }
        });
        document.getElementById('modalCustomAmount').value = amt;
        if (!matched) document.getElementById('modalCustomAmount').style.display = 'block';

        updateSummary(amt);
        document.getElementById('modalStep1').style.display = 'none';
        document.getElementById('modalStep2').style.display  = 'block';
    }

    document.getElementById('donationModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDonationModal() {
    document.getElementById('donationModal').classList.remove('open');
    document.body.style.overflow = '';
}

function resetModal() {
    document.getElementById('modalStep1').style.display  = 'block';
    document.getElementById('modalStep2').style.display  = 'none';
    document.getElementById('modalLoading').style.display = 'none';
    document.getElementById('modalSuccess').style.display = 'none';
    document.getElementById('modalAmountError').style.display = 'none';
    document.getElementById('widgetError').style.display = 'none';
    document.querySelectorAll('.mq-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('modalCustomAmount').style.display = 'none';
    document.getElementById('modalCustomAmount').value = '';
    document.getElementById('methodMobile').classList.add('active');
    document.getElementById('methodCard').classList.remove('active');
    document.getElementById('mobileFields').style.display = 'block';
    document.getElementById('cardFields').style.display   = 'none';
    document.getElementById('summaryMethod').textContent  = 'Mobile Money';
    var sb = document.getElementById('modalSubmitBtn');
    if (sb) sb.disabled = false;
}

// ── Helper: update summary panel ─────────────────────────────
function updateSummary(amount) {
    var fmt = 'UGX ' + parseInt(amount).toLocaleString();
    document.getElementById('summaryAmount').textContent     = fmt;
    document.getElementById('summaryTotal').textContent      = fmt;
    document.getElementById('submitAmountText').textContent  = fmt;
    document.getElementById('chipAmountText').textContent    = fmt;
    document.getElementById('successAmount').textContent     = fmt;
}

// ── Go back to Step 1 ─────────────────────────────────────────
function goBackToStep1() {
    document.getElementById('modalStep2').style.display = 'none';
    document.getElementById('modalStep1').style.display = 'block';
    document.getElementById('widgetError').style.display = 'none';
}

// ── Step 1: Modal Quick Pills ─────────────────────────────────
document.querySelectorAll('.mq-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.mq-btn').forEach(b => b.classList.remove('selected'));
        this.classList.add('selected');
        if (this.dataset.amount === 'custom') {
            document.getElementById('modalCustomAmount').style.display = 'block';
            document.getElementById('modalCustomAmount').focus();
        } else {
            document.getElementById('modalCustomAmount').style.display = 'none';
            document.getElementById('modalCustomAmount').value = this.dataset.amount;
        }
        document.getElementById('modalAmountError').style.display = 'none';
    });
});

// ── Step 1: Continue button ───────────────────────────────────
document.getElementById('modalAmountNextBtn').addEventListener('click', function() {
    var sel    = document.querySelector('.mq-btn.selected');
    var custom = document.getElementById('modalCustomAmount').value;
    var amount = (sel && sel.dataset.amount !== 'custom') ? sel.dataset.amount : custom;

    if (!amount || parseInt(amount) < 500) {
        document.getElementById('modalAmountError').style.display = 'block';
        return;
    }
    document.getElementById('modalAmountError').style.display = 'none';
    window._donationAmount = parseInt(amount);
    updateSummary(window._donationAmount);
    document.getElementById('modalStep1').style.display = 'none';
    document.getElementById('modalStep2').style.display = 'block';
});

// ── Step 2: Back button ───────────────────────────────────────
document.getElementById('modalBackBtn').addEventListener('click', goBackToStep1);

// ── Payment Method Tabs ───────────────────────────────────────
document.getElementById('methodMobile').addEventListener('click', function() {
    document.querySelectorAll('.pay-method-tab').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('mobileFields').style.display = 'block';
    document.getElementById('cardFields').style.display   = 'none';
    document.getElementById('summaryMethod').textContent  = 'Mobile Money';
});
document.getElementById('methodCard').addEventListener('click', function() {
    document.querySelectorAll('.pay-method-tab').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('mobileFields').style.display = 'none';
    document.getElementById('cardFields').style.display   = 'block';
    document.getElementById('summaryMethod').textContent  = 'Card';
});

// ── Live-update donor name in summary ─────────────────────────
document.getElementById('modalName').addEventListener('input', function() {
    var anon = document.getElementById('modalAnonymous').checked;
    document.getElementById('summaryDonor').textContent = anon ? 'Anonymous' : (this.value.trim() || '—');
});
document.getElementById('modalAnonymous').addEventListener('change', function() {
    document.getElementById('summaryDonor').textContent = this.checked ? 'Anonymous'
        : (document.getElementById('modalName').value.trim() || '—');
});

// ── Step 2: Submit ────────────────────────────────────────────
document.getElementById('modalSubmitBtn').addEventListener('click', async function() {
    var amount  = window._donationAmount;
    var name    = document.getElementById('modalName').value.trim();
    var phone   = document.getElementById('modalPhone').value.trim();
    var network = document.getElementById('modalNetwork').value;
    var email   = document.getElementById('modalEmail').value.trim();
    var anon    = document.getElementById('modalAnonymous').checked;

    var ok = true;
    if (!anon && !name) {
        document.getElementById('modalNameError').classList.add('show'); ok = false;
    } else { document.getElementById('modalNameError').classList.remove('show'); }

    if (!phone || phone.replace(/\D/g,'').length < 9) {
        document.getElementById('modalPhoneError').classList.add('show'); ok = false;
    } else { document.getElementById('modalPhoneError').classList.remove('show'); }

    if (!ok) return;

    this.disabled = true;
    document.getElementById('widgetError').style.display = 'none';
    document.getElementById('modalStep2').style.display  = 'none';
    document.getElementById('modalLoading').style.display = 'block';

    var fd = new FormData();
    fd.append('action',                'submit');
    fd.append('campaign_id',           '<?= $cid ?>');
    fd.append('amount',                amount);
    fd.append('donor_name',            anon ? 'Anonymous' : name);
    fd.append('donor_phone',           phone);
    fd.append('donor_email',           email);
    fd.append('mobile_money_network',  network);
    fd.append('is_anonymous',          anon ? '1' : '0');

    try {
        var res  = await fetch('<?= BASE ?>/api/donations.php', { method:'POST', body:fd });
        var text = await res.text();
        var data;
        try { data = JSON.parse(text); }
        catch(pe) {
            document.getElementById('modalLoading').style.display  = 'none';
            document.getElementById('modalStep2').style.display    = 'block';
            document.getElementById('widgetError').textContent     = 'Server error — please try again.';
            document.getElementById('widgetError').style.display   = 'block';
            console.error('Non-JSON from donations API:', text);
            document.getElementById('modalSubmitBtn').disabled = false;
            return;
        }

        document.getElementById('modalLoading').style.display = 'none';

        if (data.success && data.pending) {
            // Show success state, poll in background, reload givers when confirmed
            document.getElementById('successAmount').textContent = 'UGX ' + parseInt(amount).toLocaleString();
            document.getElementById('modalSuccess').style.display = 'block';
            startSuccessCountdown(data.donation_id);
        } else {
            document.getElementById('modalStep2').style.display  = 'block';
            document.getElementById('widgetError').textContent   = data.message || 'Payment failed. Please try again.';
            document.getElementById('widgetError').style.display = 'block';
            document.getElementById('modalSubmitBtn').disabled = false;
        }
    } catch (ex) {
        document.getElementById('modalLoading').style.display  = 'none';
        document.getElementById('modalStep2').style.display    = 'block';
        document.getElementById('widgetError').textContent     = 'Could not reach the server. Check your connection and try again.';
        document.getElementById('widgetError').style.display   = 'block';
        document.getElementById('modalSubmitBtn').disabled = false;
    }
});

// ── Success countdown + refresh givers ───────────────────────
function startSuccessCountdown(donationId) {
    var bar       = document.getElementById('successBar');
    var countdown = document.getElementById('successCountdown');
    var secs      = 5;
    var pollTimer = null;

    // Shrink bar over 5s
    if (bar) {
        bar.style.transition = 'none';
        bar.style.width      = '100%';
        setTimeout(function() {
            bar.style.transition = 'width 5s linear';
            bar.style.width      = '0%';
        }, 30);
    }

    // Poll every 3s to check if payment confirmed, refresh givers when it is
    function poll() {
        fetch('<?= BASE ?>/api/donations.php?action=check_status&donation_id=' + donationId)
            .then(function(r){ return r.text(); })
            .then(function(txt){
                var d;
                try { d = JSON.parse(txt); } catch(e){ return; }
                if (d.status === 'completed') {
                    clearInterval(pollTimer);
                    refreshGivers();
                }
            }).catch(function(){});
    }
    pollTimer = setInterval(poll, 3000);

    // Countdown text (just informational, won't redirect)
    if (countdown) countdown.textContent = 'Payment prompt sent to your phone. Enter your PIN.';

    // Auto-close modal after 5s and reload page to refresh givers list
    setTimeout(function() {
        clearInterval(pollTimer);
        closeDonationModal();
        refreshGivers();
    }, 5000);
}

// ── Refresh givers list without full page reload ──────────────
function refreshGivers() {
    fetch('<?= BASE ?>/api/donations.php?action=list&campaign_id=<?= $cid ?>&page=1')
        .then(function(r){ return r.text(); })
        .then(function(txt){
            var d;
            try { d = JSON.parse(txt); } catch(e){ return; }
            if (!d.success || !d.donations) return;
            var container = document.querySelector('.recent-donations');
            if (!container) return;

            var title = container.querySelector('.section-title');
            var currency = '<?= $c['currency'] ?>';

            // Rebuild the list
            var html = '';
            var colours = ['#1a7a3c','#145f2e','#d97706','#2563eb','#7c3aed','#dc2626'];
            d.donations.forEach(function(rd) {
                var name = rd.is_anonymous ? 'Anonymous' : (rd.donor_name || 'Anonymous');
                var initial = name.charAt(0).toUpperCase();
                var col = colours[initial.charCodeAt(0) % colours.length];
                var diff = Math.floor((Date.now()/1000) - (new Date(rd.payment_date).getTime()/1000));
                var timeStr = diff < 60 ? 'Just now' : diff < 3600 ? Math.floor(diff/60)+'m ago' : diff < 86400 ? Math.floor(diff/3600)+'h ago' : Math.floor(diff/86400)+'d ago';
                var topBadge = rd.amount >= 500000 ? '<span style="background:#fef3c7;padding:1px 6px;border-radius:99px;font-size:.6rem;font-weight:700;color:#92400e;margin-left:3px;">🏆 Top</span>' : '';
                html += '<div class="donation-item">' +
                    '<div style="display:flex;align-items:center;gap:9px;">' +
                        '<div style="width:34px;height:34px;border-radius:50%;background:'+col+';display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.78rem;flex-shrink:0;">'+initial+'</div>' +
                        '<div>' +
                            '<div class="donor-name">'+name+topBadge+'</div>' +
                            '<div class="donor-time">'+timeStr+'</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="donor-amount">+'+currency+' '+parseInt(rd.amount).toLocaleString()+'</div>' +
                '</div>';
            });

            // Update count in title
            if (title) {
                var newCount = d.donations.length;
                title.innerHTML = '<i class="fas fa-users" style="color:var(--green);margin-right:6px;"></i>' + newCount + ' ' + (newCount === 1 ? 'giver' : 'givers');
            }

            // Replace items (keep title, replace rest)
            var existing = container.querySelectorAll('.donation-item');
            existing.forEach(function(el){ el.remove(); });
            var emptyMsg = container.querySelector('p');
            if (emptyMsg) emptyMsg.remove();

            if (html) {
                container.insertAdjacentHTML('beforeend', html);
            } else {
                container.insertAdjacentHTML('beforeend',
                    '<p style="color:#94a3b8;font-size:.86rem;text-align:center;padding:20px 0;">Be the first to give!</p>');
            }
        }).catch(function(){});
}

// ── Done button — close modal and refresh givers ──────────────
document.getElementById('modalDoneBtn').addEventListener('click', function() {
    closeDonationModal();
    refreshGivers();
});

// ── Close on overlay click ────────────────────────────────────
document.getElementById('donationModal').addEventListener('click', function(e) {
    if (e.target === this) closeDonationModal();
});

// ── Share ──────────────────────────────────────────────────────
function toggleSharePanel() {
    var panel = document.getElementById('sharePanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function copyShareLink(btn) {
    var url = '<?= $canonicalUrl ?>';
    navigator.clipboard.writeText(url).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.style.background = 'rgba(16,185,129,.4)';
        setTimeout(function() { btn.innerHTML = orig; btn.style.background = ''; }, 2200);
    }).catch(function() {
        var inp = document.getElementById('shareLinkInput');
        if (inp) { inp.select(); document.execCommand('copy'); }
    });
}

function shareCampaign() {
    if (navigator.share) {
        navigator.share({
            title: '<?= addslashes(htmlspecialchars($c['title'])) ?>',
            text: '<?= addslashes(substr(strip_tags($c['description']),0,100)) ?>…',
            url: '<?= $canonicalUrl ?>'
        }).catch(function(){});
    } else {
        toggleSharePanel();
    }
}
</script>

</body>
</html>