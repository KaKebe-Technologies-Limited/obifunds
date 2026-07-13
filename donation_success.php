<?php
// ObiFunds – donation_success.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$donation_id = (int)($_GET['donation_id'] ?? 0);
if ($donation_id <= 0) { header('Location: '.BASE.'/index.php'); exit; }

$res = $conn->query(
    "SELECT d.status, d.donor_phone, d.amount, d.currency, d.transaction_reference,
            c.campaign_id, c.title AS campaign_title
     FROM donations d
     JOIN campaigns c ON d.campaign_id = c.campaign_id
     WHERE d.donation_id = $donation_id LIMIT 1"
);
$don = $res ? $res->fetch_assoc() : null;
if (!$don) { header('Location: '.BASE.'/index.php'); exit; }

$cid      = (int)$don['campaign_id'];
$status   = $don['status'];  // pending | completed | failed

// Already failed — go back to campaign
if ($status === 'failed') {
    header('Location: '.BASE.'/campaign-detail.php?id='.$cid.'&payment=failed');
    exit;
}

// ── If already completed, redirect to campaign with success ──
if ($don['status'] === 'completed') {
    $_SESSION['payment_success'] = 'Your donation was successful! Thank you!';
    header('Location: ' . BASE . '/campaign-detail.php?id=' . $cid . '&payment=success');
    exit;
}

// ── If failed, redirect to campaign with error ──
if ($don['status'] === 'failed') {
    $_SESSION['payment_error'] = 'Your donation was not completed. Please try again.';
    header('Location: ' . BASE . '/campaign-detail.php?id=' . $cid . '&payment=failed');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Payment – ObiFunds</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0fdf4;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
    .card{background:#fff;border-radius:24px;padding:40px 32px;width:100%;max-width:420px;text-align:center;box-shadow:0 8px 40px rgba(26,122,60,.13);border:1px solid #dde8e2;}
    .icon{width:90px;height:90px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 22px;font-size:2.4rem;}
    h1{font-size:1.45rem;font-weight:900;margin-bottom:10px;letter-spacing:-.02em;}
    p{font-size:.9rem;line-height:1.7;color:#607068;margin-bottom:16px;}
    .bar-wrap{height:6px;background:#e8f5ee;border-radius:99px;overflow:hidden;margin-bottom:20px;}
    .bar{height:100%;background:#1a7a3c;border-radius:99px;width:0%;transition:width 1.8s linear;}
    .btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px;border-radius:12px;font-weight:800;font-size:.95rem;text-decoration:none;border:none;cursor:pointer;font-family:inherit;margin-bottom:10px;}
    .btn-green{background:#1a7a3c;color:#fff;}
    .btn-ghost{background:none;border:2px solid #dde8e2;color:#607068;font-size:.85rem;}
    .notice{background:#e8f5ee;border:1px solid #b6e3c8;border-radius:10px;padding:10px 14px;font-size:.82rem;color:#145f2e;font-weight:600;margin-bottom:20px;}
    .ref{font-size:.73rem;color:#94a3b8;margin-top:12px;}
    .ref code{background:#f0f4f2;padding:2px 8px;border-radius:6px;}
  </style>
</head>
<body>
<div class="card" id="card">

<?php if ($status === 'completed'): ?>
  <!-- ─── ALREADY CONFIRMED ─────────────────────────── -->
  <div class="icon" style="background:linear-gradient(135deg,#1a7a3c,#145f2e);">
    <i class="fas fa-check" style="color:#fff;"></i>
  </div>
  <h1 style="color:#145f2e;">Payment Confirmed! 🎉</h1>
  <p>Your contribution of <strong style="color:#1a7a3c;"><?= $don['currency']??'UGX' ?> <?= number_format($don['amount']) ?></strong><br>has been received. Thank you!</p>
  <div class="notice">Returning to the drive in <strong id="secs">3</strong>s…</div>
  <a href="<?= BASE ?>/campaign-detail.php?id=<?= $cid ?>" class="btn btn-green">
    <i class="fas fa-arrow-left"></i> Back to Drive
  </a>
  <script>
    var s=3;
    var t=setInterval(function(){
      s--;
      var el=document.getElementById('secs');
      if(el) el.textContent=s;
      if(s<=0){ clearInterval(t); window.location.replace('<?= BASE ?>/campaign-detail.php?id=<?= $cid ?>'); }
    },1000);
  </script>

<?php else: ?>
  <!-- ─── PENDING ───────────────────────────────────── -->
  <div class="icon" style="background:#fef9e0;" id="ico">📱</div>
  <h1 style="color:#145f2e;" id="ttl">Check Your Phone</h1>
  <p id="msg">
    A payment prompt was sent to<br>
    <strong style="color:#1a7a3c;"><?= htmlspecialchars($don['donor_phone']) ?></strong>.<br>
    Enter your PIN to confirm.
  </p>
  <div class="bar-wrap"><div class="bar" id="bar"></div></div>
  <button class="btn btn-green" id="btn" onclick="checkNow()">
    <i class="fas fa-check-circle"></i> I've Paid — Confirm Now
  </button>
  <a href="<?= BASE ?>/campaign-detail.php?id=<?= $cid ?>" class="btn btn-ghost">← Back to drive</a>
  <p class="ref">Ref: <code><?= htmlspecialchars($don['transaction_reference']) ?></code></p>

  <script>
  var DID  = <?= $donation_id ?>;
  var CID  = <?= $cid ?>;
  var BASE = '<?= BASE ?>';
  var busy = false;
  var n    = 0;
  var tmr  = null;

  function anim(){
    var b=document.getElementById('bar');
    if(!b) return;
    b.style.transition='none'; b.style.width='0%';
    setTimeout(function(){ b.style.transition='width 1.8s linear'; b.style.width='100%'; },30);
  }

  function win(){
    clearInterval(tmr); busy=false;
    // Update UI
    var ico=document.getElementById('ico');
    var ttl=document.getElementById('ttl');
    var msg=document.getElementById('msg');
    var btn=document.getElementById('btn');
    var bw =document.querySelector('.bar-wrap');
    if(ico){ ico.style.background='linear-gradient(135deg,#1a7a3c,#145f2e)'; ico.innerHTML='<i class="fas fa-check" style="color:#fff;font-size:2rem;"></i>'; }
    if(ttl){ ttl.textContent='Payment Confirmed! 🎉'; }
    if(msg){ msg.innerHTML='Your contribution has been received.<br><strong style="color:#1a7a3c;">Thank you!</strong>'; }
    if(btn){ btn.style.display='none'; }
    if(bw) { bw.style.display='none'; }
    // Redirect in 2 seconds
    setTimeout(function(){ window.location.replace(BASE+'/campaign-detail.php?id='+CID); }, 2000);
  }

  function checkNow(){
    if(busy) return;
    busy=true;
    var btn=document.getElementById('btn');
    if(btn){ btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Checking…'; }
    anim();

    fetch(BASE+'/api/donations.php?action=check_status&donation_id='+DID)
      .then(function(r){ return r.json(); })
      .then(function(d){
        if(d.status==='completed'){
          win();
        } else if(d.status==='failed'){
          window.location.replace(BASE+'/campaign-detail.php?id='+CID+'&payment=failed');
        } else {
          busy=false;
          n++;
          if(btn){ btn.disabled=false; btn.innerHTML='<i class="fas fa-check-circle"></i> I\'ve Paid — Confirm Now'; }
          if(n>=40){
            clearInterval(tmr);
            var msg=document.getElementById('msg');
            if(msg) msg.innerHTML='Still processing. If you completed payment your contribution will appear shortly.';
          }
        }
      })
      .catch(function(){ busy=false; if(btn){ btn.disabled=false; btn.innerHTML='<i class="fas fa-check-circle"></i> I\'ve Paid — Confirm Now'; } });
  }

  // Start polling every 2 seconds after 1s delay
  anim();
  setTimeout(function(){ checkNow(); tmr=setInterval(checkNow,2000); }, 1000);
  </script>
<?php endif; ?>

</div>
</body>
</html>
