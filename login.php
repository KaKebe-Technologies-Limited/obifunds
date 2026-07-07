<?php
// ObiFunds – login.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE . (($_SESSION['role']==='admin') ? '/admin/index.php' : '/dashboard.php'));
    exit;
}
$msg     = $_GET['msg'] ?? '';
$errMsg  = match($msg) { 'session_expired'=>'Your session expired. Please sign in again.', 'unauthorized'=>'Sign in to access that page.', default=>'' };
$succMsg = ($msg==='logged_out') ? 'You\'ve been signed out successfully.' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sign In – ObiFunds</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css"/>
  <style>
    .obi-auth-page { display:flex; min-height:100vh; }
    .obi-auth-left {
      flex:1; background:linear-gradient(155deg,var(--green-dark) 0%,var(--green) 60%,#2d9e5e 100%);
      display:flex; flex-direction:column; justify-content:center; padding:60px 56px;
      position:relative; overflow:hidden;
    }
    .obi-auth-left::before { content:''; position:absolute; top:-80px; right:-80px; width:340px; height:340px; border-radius:50%; background:rgba(245,197,24,.08); }
    .obi-auth-left::after  { content:''; position:absolute; bottom:-60px; left:-40px; width:260px; height:260px; border-radius:50%; background:rgba(255,255,255,.05); }
    .obi-auth-panel { position:relative; z-index:2; }
    .obi-auth-brand { display:flex; align-items:center; gap:12px; margin-bottom:48px; text-decoration:none; }
    .obi-auth-brand-icon { width:48px; height:48px; background:var(--yellow); border-radius:14px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:.95rem; color:var(--green-dark); }
    .obi-auth-brand-name { font-weight:900; font-size:1.3rem; color:#fff; }
    .obi-auth-tagline { font-size:clamp(1.5rem,3vw,2.2rem); font-weight:900; color:#fff; line-height:1.2; letter-spacing:-.02em; margin-bottom:16px; }
    .obi-auth-tagline span { color:var(--yellow); }
    .obi-auth-desc { font-size:.92rem; color:rgba(255,255,255,.7); line-height:1.7; max-width:340px; margin-bottom:36px; }
    .obi-auth-trust { display:flex; flex-direction:column; gap:10px; }
    .obi-auth-trust-item { display:flex; align-items:center; gap:10px; font-size:.84rem; color:rgba(255,255,255,.8); font-weight:600; }
    .obi-auth-trust-item i { color:var(--yellow); width:18px; }
    .obi-auth-right { width:480px; flex-shrink:0; background:var(--gray-50); display:flex; align-items:center; justify-content:center; padding:40px 40px; overflow-y:auto; }
    .obi-auth-form-wrap { width:100%; max-width:400px; }
    .obi-auth-card { background:#fff; border-radius:24px; padding:36px; box-shadow:0 8px 40px rgba(26,122,60,.1); border:1px solid var(--gray-200); }
    .obi-auth-heading { font-weight:900; color:var(--green-dark); font-size:1.5rem; letter-spacing:-.02em; margin-bottom:4px; }
    .obi-auth-sub { font-size:.88rem; color:var(--gray-400); margin-bottom:28px; }
    .obi-input-group { position:relative; margin-bottom:16px; }
    .obi-input-group label { display:block; font-size:.8rem; font-weight:700; color:var(--gray-600); margin-bottom:5px; }
    .obi-input-group input { width:100%; padding:12px 44px 12px 14px; border:1.5px solid var(--gray-200); border-radius:12px; font-size:.9rem; color:var(--gray-800); background:#fff; outline:none; transition:border-color .15s; font-family:inherit; }
    .obi-input-group input:focus { border-color:var(--green); box-shadow:0 0 0 3px rgba(26,122,60,.1); }
    .obi-input-icon { position:absolute; right:13px; top:33px; color:var(--gray-400); cursor:pointer; font-size:.85rem; background:none; border:none; padding:4px; }
    .obi-submit-btn { width:100%; padding:14px; border-radius:14px; background:var(--green); color:#fff; font-weight:800; font-size:.96rem; border:none; cursor:pointer; transition:all .2s; margin-top:6px; font-family:inherit; letter-spacing:-.01em; }
    .obi-submit-btn:hover { background:var(--green-dark); transform:translateY(-1px); box-shadow:0 6px 20px rgba(26,122,60,.3); }
    .obi-submit-btn:disabled { opacity:.65; cursor:not-allowed; transform:none; }
    @media(max-width:900px){ .obi-auth-left{display:none;} .obi-auth-right{width:100%;} }
    @media(max-width:480px){ .obi-auth-right{padding:20px 14px;} .obi-auth-card{padding:24px 18px;border-radius:18px;} }
  </style>
</head>
<body>
<div class="obi-auth-page">
  <div class="obi-auth-left">
    <div class="obi-auth-panel">
      <a href="<?= BASE ?>/index.php" class="obi-auth-brand">
        <div class="obi-auth-brand-icon">OB</div>
        <span class="obi-auth-brand-name">ObiFunds</span>
      </a>
      <h2 class="obi-auth-tagline">Raise money for<br><span>what truly matters.</span></h2>
      <p class="obi-auth-desc">Join thousands of people across Africa using ObiFunds to fund education, health, community and emergency causes.</p>
      <div class="obi-auth-trust">
        <div class="obi-auth-trust-item"><i class="fas fa-check-circle"></i> Free to launch a drive</div>
        <div class="obi-auth-trust-item"><i class="fas fa-bolt"></i> Same-day mobile money payout</div>
        <div class="obi-auth-trust-item"><i class="fas fa-shield-alt"></i> Bank-grade security</div>
        <div class="obi-auth-trust-item"><i class="fas fa-globe-africa"></i> MTN · Airtel · Orange Money</div>
      </div>
    </div>
  </div>
  <div class="obi-auth-right">
    <div class="obi-auth-form-wrap">
      <!-- Mobile logo -->
      <div style="text-align:center;margin-bottom:24px;display:none;" id="mobileLogo">
        <a href="<?= BASE ?>/index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
          <div style="width:44px;height:44px;background:var(--green);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--yellow);font-weight:900;font-size:.9rem;">OB</div>
          <span style="font-weight:900;color:var(--green-dark);font-size:1.15rem;">ObiFunds</span>
        </a>
      </div>
      <div class="obi-auth-card">
        <h1 class="obi-auth-heading">Welcome back</h1>
        <p class="obi-auth-sub">Sign in to your ObiFunds account</p>
        <?php if ($errMsg): ?><div style="background:#fee2e2;color:#991b1b;padding:11px 14px;border-radius:10px;font-size:.85rem;margin-bottom:16px;"><?= htmlspecialchars($errMsg) ?></div><?php endif; ?>
        <?php if ($succMsg): ?><div style="background:var(--green-light);color:var(--green-dark);padding:11px 14px;border-radius:10px;font-size:.85rem;margin-bottom:16px;"><?= htmlspecialchars($succMsg) ?></div><?php endif; ?>
        <div id="loginError" style="display:none;background:#fee2e2;color:#991b1b;padding:11px 14px;border-radius:10px;font-size:.85rem;margin-bottom:16px;"></div>
        <form id="loginForm" novalidate>
          <div class="obi-input-group">
            <label>Email or Phone Number</label>
            <input type="text" id="identifier" name="identifier" placeholder="you@email.com" required autofocus />
          </div>
          <div class="obi-input-group">
            <label>Password</label>
            <input type="password" id="loginPassword" name="password" placeholder="••••••••" required />
            <button type="button" class="obi-input-icon" onclick="togglePw(this,'loginPassword')"><i class="fas fa-eye"></i></button>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;font-size:.82rem;">
            <label style="display:flex;align-items:center;gap:7px;color:var(--gray-600);cursor:pointer;"><input type="checkbox" name="remember" style="accent-color:var(--green);"/> Remember me</label>
            <a href="#" style="color:var(--green);font-weight:700;">Forgot password?</a>
          </div>
          <button type="submit" id="loginBtn" class="obi-submit-btn">Sign In</button>
        </form>
        <p style="text-align:center;font-size:.88rem;color:var(--gray-400);margin-top:20px;">
          New to ObiFunds? <a href="<?= BASE ?>/signup.php" style="color:var(--green);font-weight:700;">Create an account</a>
        </p>
      </div>
    </div>
  </div>
</div>
<script>
if (window.innerWidth <= 900) document.getElementById('mobileLogo').style.display = 'block';
function togglePw(btn, id) {
  var inp = document.getElementById(id);
  var show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.innerHTML = show ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
}
document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  var btn = document.getElementById('loginBtn');
  var err = document.getElementById('loginError');
  err.style.display = 'none';
  btn.disabled = true; btn.textContent = 'Signing in…';
  var fd = new FormData(this);
  fd.append('action','login');
  try {
    var res  = await fetch('<?= BASE ?>/api/auth.php?action=login', {method:'POST',body:fd});
    var data = await res.json();
    if (data.success) { window.location.href = data.redirect; }
    else { err.textContent = data.message||'Sign in failed.'; err.style.display='block'; btn.disabled=false; btn.textContent='Sign In'; }
  } catch(ex) { err.textContent='Network error.'; err.style.display='block'; btn.disabled=false; btn.textContent='Sign In'; }
});
</script>
</body>
</html>
