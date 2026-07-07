<?php
// ObiFunds – donation_success.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$donation_id = (int)($_GET['donation_id'] ?? 0);
$url_status  = strtolower($_GET['status'] ?? 'pending');

if ($donation_id <= 0) { header('Location: '.BASE.'/index.php'); exit; }

$res = $conn->query(
    "SELECT d.*, c.title AS campaign_title, c.campaign_id
     FROM donations d JOIN campaigns c ON d.campaign_id=c.campaign_id
     WHERE d.donation_id=$donation_id LIMIT 1"
);
$donation = $res ? $res->fetch_assoc() : null;
if (!$donation) { header('Location: '.BASE.'/index.php'); exit; }

$isPending = ($url_status==='pending' || $donation['status']==='pending');
$isSuccess = ($donation['status']==='completed' || $url_status==='success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= $isPending?'Processing Payment':'Thank You' ?> – ObiFunds</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css"/>
</head>
<body style="font-family:'Plus Jakarta Sans',sans-serif;background:var(--gray-50);min-height:100vh;">
<?php include __DIR__ . '/includes/header.php'; ?>

<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:100px 16px 40px;">
  <div style="background:#fff;border-radius:24px;padding:40px 36px;width:100%;max-width:500px;text-align:center;box-shadow:var(--shadow-lg);border:1px solid var(--gray-200);">

    <?php if ($isSuccess): ?>
      <!-- SUCCESS -->
      <div style="width:72px;height:72px;background:var(--green-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:2rem;">🎉</div>
      <h1 style="font-weight:900;color:var(--green-dark);font-size:1.5rem;margin-bottom:8px;letter-spacing:-.02em;">Thank You!</h1>
      <p style="color:var(--gray-500);font-size:.92rem;margin-bottom:24px;line-height:1.6;">Your contribution has been received and confirmed. You're making a real difference.</p>

    <?php else: ?>
      <!-- PENDING — auto-polls every 5s -->
      <div id="pendingIcon" style="width:72px;height:72px;background:var(--yellow-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:2rem;">📱</div>
      <h1 id="pendingTitle" style="font-weight:900;color:var(--green-dark);font-size:1.4rem;margin-bottom:8px;letter-spacing:-.02em;">Check Your Phone!</h1>
      <p id="pendingMsg" style="color:var(--gray-500);font-size:.9rem;line-height:1.65;margin-bottom:16px;">
        A mobile money prompt has been sent to<br>
        <strong style="color:var(--green-dark);"><?= htmlspecialchars($donation['donor_phone']) ?></strong>.<br>
        Enter your PIN to complete the donation.
      </p>
      <div style="background:var(--yellow-light);border:1px solid #fde68a;border-radius:10px;padding:10px 14px;font-size:.8rem;color:#92400e;margin-bottom:16px;">
        ⏱ The prompt expires in <strong>2 minutes</strong>. Checking automatically… <span id="pollCount" style="font-weight:700;"></span>
      </div>
      <div style="height:5px;background:var(--gray-200);border-radius:99px;overflow:hidden;margin-bottom:24px;">
        <div id="pollBar" style="height:100%;background:var(--green);border-radius:99px;width:0%;transition:width 4.8s linear;"></div>
      </div>
    <?php endif; ?>

    <!-- Donation summary -->
    <div style="background:var(--gray-50);border-radius:14px;padding:16px;margin-bottom:24px;text-align:left;border:1px solid var(--gray-200);">
      <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--gray-100);">
        <span style="font-size:.82rem;color:var(--gray-500);">Drive</span>
        <span style="font-size:.82rem;font-weight:700;color:var(--green-dark);text-align:right;max-width:60%;"><?= htmlspecialchars($donation['campaign_title']) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--gray-100);">
        <span style="font-size:.82rem;color:var(--gray-500);">Amount</span>
        <span style="font-size:.82rem;font-weight:800;color:var(--green);"><?= htmlspecialchars($donation['currency']??'UGX') ?> <?= number_format($donation['amount']) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--gray-100);">
        <span style="font-size:.82rem;color:var(--gray-500);">Donor</span>
        <span style="font-size:.82rem;font-weight:600;"><?= $donation['is_anonymous']?'Anonymous':htmlspecialchars($donation['donor_name']) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:6px 0;">
        <span style="font-size:.82rem;color:var(--gray-500);">Reference</span>
        <code style="font-size:.74rem;background:var(--gray-100);padding:2px 8px;border-radius:6px;"><?= htmlspecialchars($donation['transaction_reference']) ?></code>
      </div>
    </div>

    <?php if ($isSuccess): ?>
      <a href="<?= BASE ?>/campaign-detail.php?id=<?= (int)$donation['campaign_id'] ?>"
         style="display:flex;align-items:center;justify-content:center;gap:8px;background:var(--green);color:#fff;padding:14px;border-radius:12px;font-weight:800;text-decoration:none;font-size:.92rem;margin-bottom:10px;">
        <i class="fas fa-arrow-left"></i> Back to Drive
      </a>
      <a href="<?= BASE ?>/campaign-drives.php" style="display:block;font-size:.84rem;color:var(--gray-400);text-decoration:none;margin-top:6px;">Browse more drives →</a>
    <?php else: ?>
      <button id="checkNowBtn" onclick="checkNow()"
        style="width:100%;padding:14px;background:var(--green);color:#fff;border:none;border-radius:12px;font-weight:800;font-size:.92rem;cursor:pointer;font-family:inherit;margin-bottom:10px;">
        <i class="fas fa-check-circle"></i> I've Paid — Confirm Now
      </button>
      <a href="<?= BASE ?>/campaign-detail.php?id=<?= (int)$donation['campaign_id'] ?>"
         style="display:block;font-size:.84rem;color:var(--gray-400);text-decoration:none;">← Back to drive</a>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php if ($isPending): ?>
<script>
var donationId = <?= $donation_id ?>;
var campaignId = <?= (int)$donation['campaign_id'] ?>;
var pollCount  = 0, maxPolls = 24, pollTimer = null;
var bar        = document.getElementById('pollBar');
var countEl    = document.getElementById('pollCount');

function animBar(){ bar.style.transition='none'; bar.style.width='0%'; setTimeout(function(){ bar.style.transition='width 4.8s linear'; bar.style.width='100%'; },50); }

function showSuccess(){
  // Replace the entire card with a clean success view
  var card = document.querySelector('[style*="border-radius:24px"]');
  card.innerHTML =
    '<div style="text-align:center;padding:16px 0;">' +
      '<div style="width:80px;height:80px;background:linear-gradient(135deg,#1a7a3c,#145f2e);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 8px 24px rgba(26,122,60,.3);">' +
        '<span style="font-size:2.2rem;">✓</span>' +
      '</div>' +
      '<h1 style="font-weight:900;color:#145f2e;font-size:1.5rem;margin-bottom:8px;">Payment Confirmed! 🎉</h1>' +
      '<p style="color:#607068;font-size:.92rem;margin-bottom:24px;line-height:1.6;">Your contribution has been received. Thank you for making a difference!</p>' +
      '<div style="background:#e8f5ee;border-radius:10px;padding:10px 14px;font-size:.82rem;color:#145f2e;margin-bottom:24px;">Returning to the drive in <strong id="countdownSecs">3</strong>s…</div>' +
      '<a href="<?= BASE ?>/campaign-detail.php?id='+campaignId+'" id="backBtn" ' +
        'style="display:flex;align-items:center;justify-content:center;gap:8px;background:#1a7a3c;color:#fff;padding:14px;border-radius:12px;font-weight:800;text-decoration:none;font-size:.92rem;">' +
        '← Back to Drive</a>' +
    '</div>';

  // Auto-redirect after 3s
  var secs = 3;
  var cd = document.getElementById('countdownSecs');
  var t = setInterval(function(){
    secs--;
    if(cd) cd.textContent = secs;
    if(secs <= 0){
      clearInterval(t);
      window.location.href = '<?= BASE ?>/campaign-detail.php?id='+campaignId;
    }
  }, 1000);
}

async function checkNow(){
  var btn=document.getElementById('checkNowBtn');
  if(btn){btn.disabled=true;btn.textContent='Checking…';}
  try{
    var r=await fetch('<?= BASE ?>/api/donations.php?action=check_status&donation_id='+donationId);
    var d=await r.json();
    if(d.status==='completed'){clearInterval(pollTimer);showSuccess();}
    else if(d.status==='failed'){clearInterval(pollTimer);window.location.href='<?= BASE ?>/campaign-detail.php?id='+campaignId+'&payment=failed';}
    else{if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-check-circle"></i> I\'ve Paid — Confirm Now';}}
  }catch(e){if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-check-circle"></i> I\'ve Paid — Confirm Now';}}
}

function autoPoll(){
  pollCount++;
  if(countEl) countEl.textContent='('+pollCount+'/'+maxPolls+')';
  animBar();
  fetch('<?= BASE ?>/api/donations.php?action=check_status&donation_id='+donationId)
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.status==='completed'){clearInterval(pollTimer);showSuccess();}
      else if(d.status==='failed'){clearInterval(pollTimer);window.location.href='<?= BASE ?>/campaign-detail.php?id='+campaignId+'&payment=failed';}
      else if(pollCount>=maxPolls){
        clearInterval(pollTimer);
        if(countEl) countEl.textContent='';
        document.getElementById('pendingMsg').innerHTML='Payment is taking longer than usual. If you completed it, your contribution will be confirmed shortly.<br><strong>Ref: <?= htmlspecialchars($donation['transaction_reference']) ?></strong>';
        var b=document.getElementById('checkNowBtn');
        if(b){b.disabled=false;b.innerHTML='<i class="fas fa-sync"></i> Check Again';}
        bar.parentElement.style.display='none';
      }
    }).catch(function(){});
}
animBar();
pollTimer=setInterval(autoPoll,5000);
</script>
<?php endif; ?>
</body>
</html>
