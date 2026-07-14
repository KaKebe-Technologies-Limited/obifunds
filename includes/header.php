<?php
// ObiFunds – includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn  = isset($_SESSION['user_id']);
$userRole    = $_SESSION['role'] ?? 'guest';
$userName    = $_SESSION['user']['full_name'] ?? '';
$userAvatar  = $_SESSION['user']['avatar_url'] ?? '';
$logoutUrl   = BASE . '/logout.php';

// Canonical domain
$canonDomain = 'https://obifunds.com';
?>
<!DOCTYPE html>
<html lang="en" prefix="og: https://ogp.me/ns#">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <meta http-equiv="X-UA-Compatible" content="IE=edge"/>

  <!-- ── Primary Meta ─────────────────────────────────────── -->
  <title><?= $pageTitle ?? 'ObiFunds – Raise Money for What Matters' ?></title>
  <?php if (!empty($pageDescription)): ?>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>"/>
  <?php else: ?>
  <meta name="description" content="ObiFunds is Africa's mobile-money crowdfunding platform. Launch a fundraising drive in 60 seconds and collect via MTN MoMo or Airtel Money — same-day payouts."/>
  <?php endif; ?>
  <meta name="keywords" content="crowdfunding Uganda, mobile money fundraising, MTN MoMo, Airtel Money, ObiFunds, fundraise Africa, online donations Uganda"/>
  <meta name="author" content="ObiFunds"/>
  <meta name="robots" content="index,follow"/>
  <meta name="theme-color" content="#1a7a3c"/>

  <!-- ── Favicon – inline SVG as data URI (no file needed) ── -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#1a7a3c"/><text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="28" fill="#f5c518">OB</text></svg>') ?>"/>
  <link rel="apple-touch-icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#1a7a3c"/><text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="28" fill="#f5c518">OB</text></svg>') ?>"/>
  <link rel="shortcut icon" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="#1a7a3c"/><text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="28" fill="#f5c518">OB</text></svg>') ?>"/>

  <!-- ── Default Open Graph (overridden per-page via $extraCss) ── -->
  <?php if (empty($extraCss)): ?>
  <meta property="og:type"         content="website"/>
  <meta property="og:site_name"    content="ObiFunds"/>
  <meta property="og:title"        content="<?= htmlspecialchars($pageTitle ?? 'ObiFunds – Raise Money for What Matters') ?>"/>
  <meta property="og:description"  content="Africa's mobile-money crowdfunding platform. Launch a drive in 60 seconds. Free to start — same-day payout via MTN &amp; Airtel Money."/>
  <meta property="og:image"        content="<?= $canonDomain ?>/img/og-default.php"/>
  <meta property="og:url"          content="<?= $canonDomain . '/' . ltrim($_SERVER['REQUEST_URI'] ?? '', '/') ?>"/>
  <meta name="twitter:card"        content="summary_large_image"/>
  <meta name="twitter:site"        content="@ObiFunds"/>
  <meta name="twitter:title"       content="<?= htmlspecialchars($pageTitle ?? 'ObiFunds') ?>"/>
  <meta name="twitter:image"       content="<?= $canonDomain ?>/img/og-default.php"/>
  <?php endif; ?>

  <!-- ── Extra per-page meta/OG (injected by page) ── -->
  <?php if (!empty($extraCss)) echo $extraCss; ?>

  <!-- ── Fonts ─────────────────────────────────────────────── -->
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css"/>

  <!-- Structured Data -->
  <script type="application/ld+json">
  {"@context":"https://schema.org","@type":"Organization","name":"ObiFunds","url":"https://obifunds.com","logo":"https://obifunds.com/img/logo.png","description":"Africa's mobile-money crowdfunding platform","sameAs":["https://twitter.com/ObiFunds","https://facebook.com/ObiFunds"]}
  </script>
</head>
<body>

<nav class="navbar" id="mainNav">
  <div class="container">
    <a href="<?= BASE ?>/index.php" class="navbar-brand">
      <div class="navbar-logo">OB</div>
      <span class="navbar-name">Obi<span>Funds</span></span>
    </a>
    <div class="navbar-links">
      <a href="<?= BASE ?>/campaign-drives.php" <?= $currentPage==='campaign-drives.php'?'style="color:var(--green);font-weight:700;"':'' ?>>Drives</a>
      <a href="<?= BASE ?>/donate.php" <?= $currentPage==='donate.php'?'style="color:var(--green);font-weight:700;"':'' ?>>Give Now</a>
      <a href="<?= BASE ?>/index.php#how-it-works">How It Works</a>
      <?php if ($isLoggedIn): ?>
        <div class="user-menu">
          <div class="user-avatar" id="userMenuTrigger" title="<?= htmlspecialchars($userName) ?>">
            <?php if ($userAvatar): ?>
              <img src="<?= htmlspecialchars($userAvatar) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" alt="Avatar"/>
            <?php else: ?>
              <?= strtoupper(substr($userName,0,2)) ?>
            <?php endif; ?>
          </div>
          <div class="user-dropdown" id="userDropdown">
            <a href="<?= BASE ?>/dashboard.php"><i class="fas fa-th-large"></i> My Dashboard</a>
            <?php if ($userRole==='admin'): ?>
            <a href="<?= BASE ?>/admin/index.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
            <?php endif; ?>
            <a href="<?= BASE ?>/profile.php"><i class="fas fa-user-cog"></i> Profile</a>
            <a href="<?= BASE ?>/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE ?>/login.php">Sign In</a>
        <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-sm nav-cta">Start a Drive</a>
      <?php endif; ?>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<div class="mobile-menu-overlay" id="menuOverlay"></div>
<div class="mobile-menu" id="mobileMenu">
  <button class="mobile-menu-close" id="menuClose"><i class="fas fa-times"></i></button>
  <a href="<?= BASE ?>/index.php" class="mobile-menu-brand">
    <div class="navbar-logo" style="width:36px;height:36px;font-size:.8rem;">OB</div>
    <span style="font-weight:900;color:var(--green);font-size:1.05rem;">ObiFunds</span>
  </a>
  <a href="<?= BASE ?>/campaign-drives.php">Drives</a>
  <a href="<?= BASE ?>/donate.php">Give Now</a>
  <a href="<?= BASE ?>/index.php#how-it-works">How It Works</a>
  <?php if ($isLoggedIn): ?>
    <a href="<?= BASE ?>/dashboard.php"><i class="fas fa-th-large"></i> My Dashboard</a>
    <?php if ($userRole==='admin'): ?>
    <a href="<?= BASE ?>/admin/index.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
    <?php endif; ?>
    <a href="<?= $logoutUrl ?>" style="color:#dc2626;"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  <?php else: ?>
    <a href="<?= BASE ?>/login.php">Sign In</a>
    <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary mobile-menu-cta">Start a Drive</a>
  <?php endif; ?>
</div>
<script>
window.addEventListener('scroll',function(){
  document.getElementById('mainNav').classList.toggle('scrolled',window.scrollY>20);
});
</script>
