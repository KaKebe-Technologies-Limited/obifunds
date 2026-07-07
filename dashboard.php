<?php
// ObiFunds – dashboard.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) { header('Location: '.BASE.'/login.php?msg=unauthorized'); exit; }

$uid  = (int)$_SESSION['user_id'];
$user = $_SESSION['user'];

$myCampaigns = $conn->query(
    "SELECT c.*, ROUND((c.raised_amount/c.goal_amount)*100,1) AS pct,
            DATEDIFF(c.end_date,NOW()) AS days_left
     FROM campaigns c WHERE c.campaigner_id=$uid ORDER BY c.created_at DESC"
);
$totalRaised    = (float)$conn->query("SELECT COALESCE(SUM(raised_amount),0) FROM campaigns WHERE campaigner_id=$uid")->fetch_row()[0];
$activeCnt      = (int)$conn->query("SELECT COUNT(*) FROM campaigns WHERE campaigner_id=$uid AND status='active'")->fetch_row()[0];
$totalContribs  = (int)$conn->query("SELECT COALESCE(SUM(contributor_count),0) FROM campaigns WHERE campaigner_id=$uid")->fetch_row()[0];
$pendingWd      = (float)$conn->query("SELECT COALESCE(SUM(w.gross_amount),0) FROM withdrawals w JOIN campaigns c ON w.campaign_id=c.campaign_id WHERE c.campaigner_id=$uid AND w.status IN ('pending','approved')")->fetch_row()[0];
$withdrawnTotal = (float)$conn->query("SELECT COALESCE(SUM(w.gross_amount),0) FROM withdrawals w JOIN campaigns c ON w.campaign_id=c.campaign_id WHERE c.campaigner_id=$uid AND w.status IN ('pending','approved','completed')")->fetch_row()[0];
$availableBalance = max(0, $totalRaised - $withdrawnTotal);
$unreadCount    = (int)$conn->query("SELECT COUNT(*) FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_row()[0];
$recentDonations = $conn->query(
    "SELECT d.donor_name,d.is_anonymous,d.amount,d.payment_date,c.title AS campaign_title,c.currency
     FROM donations d JOIN campaigns c ON d.campaign_id=c.campaign_id
     WHERE c.campaigner_id=$uid AND d.status='completed' ORDER BY d.payment_date DESC LIMIT 6"
);
$firstName = explode(' ', $user['full_name'])[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Dashboard – ObiFunds</title>
  <meta name="robots" content="noindex,nofollow"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css"/>
  <style>
    body{font-family:'Plus Jakarta Sans',sans-serif;}
    .obi-dash{display:flex;min-height:100vh;}
    .obi-sidebar{width:250px;flex-shrink:0;background:var(--green-dark);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;padding:20px 14px;}
    .obi-sidebar-brand{display:flex;align-items:center;gap:10px;padding:10px 10px 28px;text-decoration:none;}
    .obi-sidebar-brand-icon{width:38px;height:38px;background:var(--yellow);border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.82rem;color:var(--green-dark);}
    .obi-sidebar-brand-name{font-weight:900;font-size:1.05rem;color:#fff;}
    .obi-sidebar-nav{flex:1;}
    .obi-nav-link{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:10px;font-size:.86rem;font-weight:600;color:rgba(255,255,255,.65);transition:all .15s;margin-bottom:2px;text-decoration:none;}
    .obi-nav-link:hover{background:rgba(255,255,255,.1);color:#fff;}
    .obi-nav-link.active{background:rgba(245,197,24,.18);color:var(--yellow);}
    .obi-nav-link i{width:17px;text-align:center;font-size:.88rem;}
    .obi-nav-section{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.3);padding:14px 12px 6px;margin-top:6px;}
    .obi-sidebar-footer{border-top:1px solid rgba(255,255,255,.1);padding-top:12px;margin-top:12px;}
    .obi-main{flex:1;background:var(--gray-50);min-width:0;}
    .obi-topbar{background:#fff;border-bottom:1px solid var(--gray-200);padding:14px 28px;display:flex;align-items:center;justify-content:space-between;gap:12px;position:sticky;top:0;z-index:100;}
    .obi-topbar-mobile{display:none;width:36px;height:36px;border-radius:10px;background:var(--green-light);color:var(--green);font-size:1rem;align-items:center;justify-content:center;cursor:pointer;border:none;}
    .obi-content{padding:28px;}
    .obi-stat-card{background:#fff;border-radius:16px;border:1px solid var(--gray-200);padding:20px 22px;transition:all .2s;}
    .obi-stat-card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);}
    .obi-stat-lbl{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--gray-400);margin-bottom:6px;}
    .obi-stat-val{font-size:1.75rem;font-weight:900;letter-spacing:-.03em;margin-bottom:4px;}
    .obi-stat-sub{font-size:.74rem;color:var(--gray-400);}
    .obi-card{background:#fff;border-radius:16px;border:1px solid var(--gray-200);padding:22px;margin-bottom:22px;}
    .obi-card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;}
    .obi-card-title{font-size:.95rem;font-weight:800;color:var(--green-dark);}
    .obi-action-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
    .obi-action-tile{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:12px;padding:16px;text-align:center;text-decoration:none;transition:all .18s;display:block;}
    .obi-action-tile:hover{border-color:var(--green);background:var(--green-light);transform:translateY(-2px);}
    .obi-action-tile i{font-size:1.5rem;color:var(--green);display:block;margin-bottom:7px;}
    .obi-action-tile span{font-size:.76rem;font-weight:700;color:var(--gray-600);}
    .obi-mobile-topbar{display:none;background:#fff;border-bottom:1px solid var(--gray-200);padding:12px 16px;position:fixed;top:0;left:0;right:0;z-index:600;align-items:center;justify-content:space-between;}
    @media(max-width:1023px){
      .obi-sidebar{display:none;position:fixed;left:0;top:0;bottom:0;z-index:700;transform:translateX(-100%);transition:transform .3s;}
      .obi-sidebar.open{display:flex;transform:translateX(0);}
      .obi-topbar-mobile{display:flex;}
      .obi-mobile-topbar{display:flex;}
      .obi-main{padding-top:60px;}
      .obi-topbar{display:none;}
      .obi-content{padding:18px 14px;}
    }
  </style>
</head>
<body>
<!-- Mobile top bar -->
<div class="obi-mobile-topbar" id="mobileTopBar">
  <div style="display:flex;align-items:center;gap:9px;">
    <div style="width:34px;height:34px;background:var(--green);border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--yellow);font-weight:900;font-size:.78rem;">OB</div>
    <span style="font-weight:900;color:var(--green-dark);font-size:.95rem;">ObiFunds</span>
  </div>
  <button id="mobileSidebarBtn" style="background:none;border:none;font-size:1.3rem;color:var(--green);cursor:pointer;"><i class="fas fa-bars"></i></button>
</div>
<div id="sidebarOverlay" onclick="closeSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:690;"></div>

<div class="obi-dash">
  <!-- SIDEBAR -->
  <aside class="obi-sidebar" id="mainSidebar">
    <a href="<?= BASE ?>/index.php" class="obi-sidebar-brand">
      <div class="obi-sidebar-brand-icon">OB</div>
      <span class="obi-sidebar-brand-name">ObiFunds</span>
    </a>
    <nav class="obi-sidebar-nav">
      <div class="obi-nav-section">Overview</div>
      <a href="<?= BASE ?>/dashboard.php" class="obi-nav-link active"><i class="fas fa-th-large"></i>Dashboard</a>
      <div class="obi-nav-section">Drives</div>
      <a href="<?= BASE ?>/create-campaign.php" class="obi-nav-link"><i class="fas fa-rocket"></i>Launch a Drive</a>
      <a href="<?= BASE ?>/campaign-drives.php" class="obi-nav-link"><i class="fas fa-fire"></i>Browse Drives</a>
      <div class="obi-nav-section">Money</div>
      <a href="<?= BASE ?>/withdraw.php" class="obi-nav-link"><i class="fas fa-wallet"></i>Withdraw</a>
      <a href="<?= BASE ?>/donate.php" class="obi-nav-link"><i class="fas fa-heart"></i>Give Now</a>
      <div class="obi-nav-section">Account</div>
      <a href="<?= BASE ?>/profile.php" class="obi-nav-link"><i class="fas fa-user-cog"></i>Profile</a>
      <?php if ($_SESSION['role']==='admin'): ?>
      <a href="<?= BASE ?>/admin/index.php" class="obi-nav-link" style="color:#f5c518;"><i class="fas fa-shield-alt"></i>Admin Panel</a>
      <?php endif; ?>
    </nav>
    <div class="obi-sidebar-footer">
      <a href="<?= BASE ?>/logout.php" class="obi-nav-link" style="color:#fca5a5;"><i class="fas fa-sign-out-alt"></i>Sign Out</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="obi-main">
    <!-- Top bar -->
    <div class="obi-topbar">
      <div>
        <p style="font-size:1rem;font-weight:800;color:var(--green-dark);">Hey, <?= htmlspecialchars($firstName) ?> 👋</p>
        <p style="font-size:.78rem;color:var(--gray-400);">Here's your ObiFunds overview</p>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <?php if ($unreadCount > 0): ?>
          <span style="background:var(--green);color:#fff;font-size:.7rem;font-weight:700;padding:4px 10px;border-radius:99px;"><?= $unreadCount ?> new</span>
        <?php endif; ?>
        <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-sm"><i class="fas fa-rocket"></i> New Drive</a>
      </div>
    </div>

    <div class="obi-content">
      <!-- STAT CARDS -->
      <div class="grid-4" style="margin-bottom:24px;">
        <div class="obi-stat-card">
          <p class="obi-stat-lbl">Total Raised</p>
          <p class="obi-stat-val" style="color:var(--green);">UGX <?= number_format($totalRaised) ?></p>
          <p class="obi-stat-sub">All your drives</p>
        </div>
        <div class="obi-stat-card">
          <p class="obi-stat-lbl">Active Drives</p>
          <p class="obi-stat-val" style="color:var(--yellow-dark);"><?= $activeCnt ?></p>
          <p class="obi-stat-sub"><?= $myCampaigns?$myCampaigns->num_rows:0 ?> total drives</p>
        </div>
        <div class="obi-stat-card">
          <p class="obi-stat-lbl">Total Givers</p>
          <p class="obi-stat-val" style="color:var(--green-dark);"><?= number_format($totalContribs) ?></p>
          <p class="obi-stat-sub">Across all your drives</p>
        </div>
        <div class="obi-stat-card">
          <p class="obi-stat-lbl">Available Balance</p>
          <p class="obi-stat-val" style="color:<?= $availableBalance>0?'var(--green)':'var(--gray-400)' ?>;">UGX <?= number_format($availableBalance) ?></p>
          <p class="obi-stat-sub" style="color:<?= $pendingWd>0?'#d97706':'var(--gray-400)' ?>;">
            <?= $pendingWd>0 ? 'UGX '.number_format($pendingWd).' pending' : 'Ready to withdraw' ?>
          </p>
        </div>
      </div>

      <!-- MY DRIVES -->
      <div class="obi-card">
        <div class="obi-card-head">
          <h2 class="obi-card-title"><i class="fas fa-rocket" style="margin-right:8px;color:var(--green);"></i>Your Drives</h2>
          <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Drive</a>
        </div>
        <?php if ($myCampaigns && $myCampaigns->num_rows > 0): ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Drive</th><th>Goal</th><th>Raised</th><th>Givers</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php $myCampaigns->data_seek(0); while ($c = $myCampaigns->fetch_assoc()): $pct=min(100,(float)$c['pct']); ?>
              <tr>
                <td>
                  <p style="font-weight:700;color:var(--green-dark);font-size:.88rem;"><?= htmlspecialchars($c['title']) ?></p>
                  <p style="font-size:.72rem;color:var(--gray-400);">Created <?= date('M j Y',strtotime($c['created_at'])) ?><?= $c['days_left']>0?' · '.$c['days_left'].' days left':'' ?></p>
                </td>
                <td style="font-weight:600;font-size:.86rem;"><?= $c['currency'] ?> <?= number_format($c['goal_amount']) ?></td>
                <td>
                  <p style="font-size:.82rem;font-weight:700;color:var(--green);margin-bottom:4px;"><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> <span style="color:var(--gray-400);font-weight:400;">(<?= $pct ?>%)</span></p>
                  <div class="progress-wrap" style="min-width:90px;"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
                </td>
                <td style="font-size:.86rem;"><?= $c['contributor_count'] ?></td>
                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                <td>
                  <div style="display:flex;gap:12px;">
                    <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>" style="color:var(--green);font-size:.88rem;" title="View"><i class="fas fa-eye"></i></a>
                    <?php if(in_array($c['status'],['draft','active','paused'])): ?>
                    <a href="<?= BASE ?>/edit-campaign.php?id=<?= $c['campaign_id'] ?>" style="color:var(--yellow-dark);font-size:.88rem;" title="Edit"><i class="fas fa-edit"></i></a>
                    <?php endif; ?>
                    <?php if($c['status']==='active'&&$c['raised_amount']>0): ?>
                    <a href="<?= BASE ?>/withdraw.php?campaign_id=<?= $c['campaign_id'] ?>" style="color:var(--green);font-size:.88rem;" title="Withdraw"><i class="fas fa-wallet"></i></a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:48px 0;color:var(--gray-400);">
          <i class="fas fa-rocket" style="font-size:2.5rem;margin-bottom:14px;display:block;color:var(--gray-200);"></i>
          <p style="font-weight:700;color:var(--gray-600);margin-bottom:12px;">No drives yet. Time to launch!</p>
          <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary"><i class="fas fa-rocket"></i> Launch Your First Drive</a>
        </div>
        <?php endif; ?>
      </div>

      <!-- BOTTOM GRID -->
      <div class="grid-2">
        <!-- Quick actions -->
        <div class="obi-card" style="margin-bottom:0;">
          <h2 class="obi-card-title" style="margin-bottom:16px;"><i class="fas fa-bolt" style="margin-right:8px;color:var(--yellow-dark);"></i>Quick Actions</h2>
          <div class="obi-action-grid">
            <a href="<?= BASE ?>/create-campaign.php" class="obi-action-tile"><i class="fas fa-rocket"></i><span>New Drive</span></a>
            <a href="<?= BASE ?>/campaign-drives.php" class="obi-action-tile"><i class="fas fa-fire"></i><span>Browse Drives</span></a>
            <a href="<?= BASE ?>/donate.php" class="obi-action-tile"><i class="fas fa-heart"></i><span>Give Now</span></a>
            <a href="<?= BASE ?>/withdraw.php" class="obi-action-tile"><i class="fas fa-wallet"></i><span>Withdraw</span></a>
          </div>
        </div>

        <!-- Recent contributions -->
        <div class="obi-card" style="margin-bottom:0;">
          <h2 class="obi-card-title" style="margin-bottom:16px;"><i class="fas fa-users" style="margin-right:8px;color:var(--green);"></i>Recent Givers</h2>
          <?php if ($recentDonations && $recentDonations->num_rows > 0): ?>
            <?php while ($d = $recentDonations->fetch_assoc()): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--gray-100);">
              <div>
                <p style="font-weight:700;color:var(--green-dark);font-size:.86rem;"><?= $d['is_anonymous']?'Anonymous':htmlspecialchars($d['donor_name']) ?></p>
                <p style="font-size:.72rem;color:var(--gray-400);"><?= htmlspecialchars($d['campaign_title']) ?></p>
              </div>
              <div style="text-align:right;">
                <p style="font-weight:800;color:var(--green);font-size:.88rem;">+ <?= $d['currency'] ?> <?= number_format($d['amount']) ?></p>
                <p style="font-size:.7rem;color:var(--gray-400);"><?= date('M j',strtotime($d['payment_date'])) ?></p>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p style="color:var(--gray-400);font-size:.86rem;text-align:center;padding:24px 0;">No contributions yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="<?= BASE ?>/js/main.js"></script>
<script>
function closeSidebar(){
  document.getElementById('mainSidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').style.display='none';
}
document.getElementById('mobileSidebarBtn').onclick=function(){
  document.getElementById('mainSidebar').classList.add('open');
  document.getElementById('sidebarOverlay').style.display='block';
};
</script>
</body>
</html>
