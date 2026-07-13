<?php
// ObiFunds – donation_success.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$donation_id = (int)($_GET['donation_id'] ?? 0);
$url_status  = strtolower($_GET['status'] ?? 'pending');

if ($donation_id <= 0) { header('Location: '.BASE.'/index.php'); exit; }

$res = $conn->query(
    "SELECT d.*, c.title AS campaign_title, c.campaign_id
     FROM donations d JOIN campaigns c ON d.campaign_id = c.campaign_id
     WHERE d.donation_id = $donation_id LIMIT 1"
);
$donation = $res ? $res->fetch_assoc() : null;
if (!$donation) { header('Location: '.BASE.'/index.php'); exit; }

$campaignId = (int)$donation['campaign_id'];

// If already completed in DB — go straight to success view
if ($donation['status'] === 'completed') {
    $url_status = 'success';
}
// If already failed — go back to campaign
if ($donation['status'] === 'failed') {
    header('Location: '.BASE.'/campaign-detail.php?id='.$campaignId.'&payment=failed');
    exit;
}

$isSuccess = ($url_status === 'success');
$isPending = !$isSuccess;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= $isSuccess ? 'Payment Confirmed' : 'Processing Payment' ?> – ObiFunds</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css"/>
  <style>
    body { font-family:'Plus Jakarta Sans',sans-serif; background:#f0fdf4; min-height:100vh; margin:0; }
    .obi-pay-wrap {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 20px 16px;
    }
    .obi-pay-card {
      background: #fff;
      border-radius: 24px;
      padding: 40px 32px;
      width: 100%; max-width: 440px;
      text-align: center;
      box-shadow: 0 8px 40px rgba(26,122,60,.12);
      border: 1px solid #dde8e2;
    }
    .obi-icon-wrap {
      width: 88px; height: 88px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 22px; font-size: 2.4rem;
    }
    .obi-pay-card h1 { font-size: 1.5rem; font-weight: 900; margin: 0 0 10px; letter-spacing: -.02em; }
    .obi-pay-card p  { font-size: .9rem; line-height: 1.7; color: #607068; margin: 0 0 20px; }
    .obi-progress { height: 6px; background: #e8f5ee; border-radius: 99px; overflow: hidden; margin: 0 0 20px; }
    .obi-progress-bar { height: 100%; background: #1a7a3c; border-radius: 99px; width: 0%; transition: width 2s linear; }
    .obi-btn {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      width: 100%; padding: 14px; border-radius: 12px;
      font-weight: 800; font-size: .95rem; text-decoration: none;
      cursor: pointer; border: none; font-family: inherit;
      margin-bottom: 10px; box-sizing: border-box;
    }
    .obi-btn-green { background: #1a7a3c; color: #fff; }
    .obi-btn-outline { background: none; border: 2px solid #dde8e2; color: #607068; font-size: .85rem; }
    .obi-ref { font-size: .74rem; color: #94a3b8; margin-top: 14px; }
    .obi-ref code { background: #f0f4f2; padding: 2px 8px; border-radius: 6px; }
  </style>
</head>
<body>

<div class="obi-pay-wrap">
  <div class="obi-pay-card" id="payCard">

    <?php if ($isSuccess): ?>
    <!-- ── ALREADY CONFIRMED ON LOAD ─────────────────────── -->
    <div class="obi-icon-wrap" style="background:linear-gradient(135deg,#1a7a3c,#145f2e);">
      <i class="fas fa-check" style="color:#fff;"></i>
    </div>
    <h1 style="color:#145f2e;">Payment Confirmed! 🎉</h1>
    <p>Your contribution of <strong style="color:#1a7a3c;"><?= $donation['currency']??'UGX' ?> <?= number_format($donation['amount']) ?></strong> has been received.<br>Thank you for making a difference!</p>
    <p style="background:#e8f5ee;border-radius:10px;padding:10px;font-size:.82rem;color:#145f2e;font-weight:700;">
      Returning to the drive in <span id="secs">3</span>s…
    </p>
    <a href="<?= BASE ?>/campaign-detail.php?id=<?= $campaignId ?>" class="obi-btn obi-btn-green">
      <i class="fas fa-arrow-left"></i> Back to Drive
    </a>
    <script>
      var s=3, t=setInterval(function(){
        s--; var el=document.getElementById('secs'); if(el) el.textContent=s;
        if(s<=0){ clearInterval(t); window.location.replace('<?= BASE ?>/campaign-detail.php?id=<?= $campaignId ?>'); }
      },1000);
    </script>

    <?php else: ?>
    <!-- ── PENDING — polls every 2s ──────────────────────── -->
    <div class="obi-icon-wrap" style="background:#fef9e0;" id="statusIcon">
      <span style="font-size:2.2rem;">📱</span>
    </div>
    <h1 style="color:#145f2e;" id="statusTitle">Check Your Phone</h1>
    <p id="statusMsg">
      A payment prompt has been sent to<br>
      <strong style="color:#1a7a3c;"><?= htmlspecialchars($donation['donor_phone']) ?></strong>.<br>
      Enter your PIN to complete the donation.
    </p>
    <div class="obi-progress"><div class="obi-progress-bar" id="pbar"></div></div>
    <button class="obi-btn obi-btn-green" id="confirmBtn" onclick="poll()">
      <i class="fas fa-check-circle"></i> I've Paid — Confirm Now
    </button>
    <a href="<?= BASE ?>/campaign-detail.php?id=<?= $campaignId ?>" class="obi-btn obi-btn-outline">
      ← Back to drive
    </a>
    <p class="obi-ref">Ref: <code><?= htmlspecialchars($donation['transaction_reference']) ?></code></p>

    <script>
    var donationId = <?= $donation_id ?>;
    var campaignId = <?= $campaignId ?>;
    var baseUrl    = '<?= BASE ?>';
    var polling    = false;
    var pollCount  = 0;
    var timer      = null;

    function animBar(){
      var b = document.getElementById('pbar');
      if(!b) return;
      b.style.transition = 'none'; b.style.width = '0%';
      setTimeout(function(){ b.style.transition = 'width 1.8s linear'; b.style.width = '100%'; }, 30);
    }

    function onConfirmed(){
      // Stop all polling
      clearInterval(timer); polling = false;

      // Show success inline — no DOM manipulation tricks
      document.getElementById('statusIcon').innerHTML = '<i class="fas fa-check" style="color:#fff;font-size:2.2rem;"></i>';
      document.getElementById('statusIcon').style.background = 'linear-gradient(135deg,#1a7a3c,#145f2e)';
      document.getElementById('statusTitle').textContent = 'Payment Confirmed! 🎉';
      document.getElementById('statusMsg').innerHTML =
        'Your contribution has been received.<br><strong style="color:#1a7a3c;">Thank you!</strong>';
      document.getElementById('pbar').parentElement.style.display = 'none';
      document.getElementById('confirmBtn').style.display = 'none';

      // Redirect after 2 seconds
      setTimeout(function(){
        window.location.replace(baseUrl + '/campaign-detail.php?id=' + campaignId);
      }, 2000);
    }

    function poll(){
      if(polling) return;
      polling = true;
      var btn = document.getElementById('confirmBtn');
      if(btn){ btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking…'; }
      animBar();

      fetch(baseUrl + '/api/donations.php?action=check_status&donation_id=' + donationId)
        .then(function(r){ return r.json(); })
        .then(function(d){
          polling = false;
          pollCount++;

          if(d.status === 'completed'){
            onConfirmed();
          } else if(d.status === 'failed'){
            window.location.replace(baseUrl + '/campaign-detail.php?id=' + campaignId + '&payment=failed');
          } else {
            // Still pending — re-enable button
            if(btn){ btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle"></i> I\'ve Paid — Confirm Now'; }
            if(pollCount >= 30){
              clearInterval(timer);
              document.getElementById('statusMsg').innerHTML =
                'Payment is taking longer than usual.<br>If you\'ve paid, your contribution will appear shortly.';
            }
          }
        })
        .catch(function(){
          polling = false;
          if(btn){ btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle"></i> I\'ve Paid — Confirm Now'; }
        });
    }

    // Auto-poll every 2 seconds
    animBar();
    timer = setInterval(poll, 2000);
    // Also fire immediately after 1 second
    setTimeout(poll, 1000);
    </script>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
