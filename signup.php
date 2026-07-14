<?php
// ObiFunds – signup.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
if (!empty($_SESSION['user_id'])) { header('Location: '.BASE.'/dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Create Account – ObiFunds</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css"/>
  <style>
    body { font-family:'Plus Jakarta Sans',sans-serif; }
    .obi-signup { display:flex; min-height:100vh; }
    /* Left – image panel */
    .obi-signup-left { flex:1; position:relative; display:flex; flex-direction:column; justify-content:flex-end; padding:48px; overflow:hidden; min-height:100vh; }
    .obi-signup-bg { position:absolute; inset:0; background-image:url('https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=1200&q=80'); background-size:cover; background-position:center; }
    .obi-signup-overlay { position:absolute; inset:0; background:linear-gradient(160deg,rgba(20,95,46,.85) 0%,rgba(20,95,46,.6) 50%,rgba(0,0,0,.65) 100%); }
    .obi-signup-content { position:relative; z-index:2; }
    .obi-signup-logo { display:inline-flex; align-items:center; gap:10px; position:absolute; top:48px; left:48px; text-decoration:none; }
    .obi-signup-logo-icon { width:44px; height:44px; background:var(--yellow); border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--green-dark); font-weight:900; font-size:.9rem; }
    .obi-signup-tagline { font-size:clamp(1.6rem,3.5vw,2.4rem); font-weight:900; color:#fff; line-height:1.2; margin-bottom:16px; letter-spacing:-.02em; }
    .obi-signup-tagline span { color:var(--yellow); }
    .obi-signup-sub { font-size:.92rem; color:rgba(255,255,255,.75); line-height:1.7; max-width:380px; margin-bottom:28px; }
    .obi-signup-stats { display:flex; gap:16px; flex-wrap:wrap; }
    .obi-signup-stat { background:rgba(255,255,255,.12); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,.2); border-radius:14px; padding:14px 18px; text-align:center; min-width:90px; }
    .obi-signup-stat-val { font-size:1.3rem; font-weight:900; color:var(--yellow); display:block; }
    .obi-signup-stat-lbl { font-size:.7rem; color:rgba(255,255,255,.7); margin-top:2px; }
    .obi-signup-pills { display:flex; flex-wrap:wrap; gap:8px; margin-top:20px; }
    .obi-signup-pill { display:inline-flex; align-items:center; gap:5px; background:rgba(255,255,255,.13); backdrop-filter:blur(6px); border:1px solid rgba(255,255,255,.18); border-radius:99px; padding:5px 12px; font-size:.74rem; color:rgba(255,255,255,.88); font-weight:600; }
    .obi-signup-pill i { color:var(--yellow); font-size:.7rem; }
    /* Right – form */
    .obi-signup-right { width:520px; flex-shrink:0; background:var(--gray-50); display:flex; align-items:flex-start; justify-content:center; padding:40px; overflow-y:auto; min-height:100vh; }
    .obi-signup-form-wrap { width:100%; max-width:440px; padding:8px 0 48px; }
    .obi-signup-card { background:rgba(255,255,255,.97); border-radius:24px; box-shadow:0 16px 48px rgba(0,0,0,.1); padding:32px; border:1px solid var(--gray-200); }
    .obi-signup-heading { font-size:1.4rem; font-weight:900; color:var(--green-dark); margin-bottom:4px; letter-spacing:-.02em; }
    .obi-signup-subheading { font-size:.86rem; color:var(--gray-400); margin-bottom:24px; }
    .obi-input-wrap { position:relative; }
    .obi-input-left-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--gray-400); font-size:.82rem; pointer-events:none; }
    .obi-input-wrap input, .obi-input-wrap select { width:100%; padding:11px 40px 11px 38px; border:1.5px solid var(--gray-200); border-radius:10px; font-size:.88rem; color:var(--gray-800); outline:none; transition:border-color .15s; font-family:inherit; background:#fff; }
    .obi-input-wrap input:focus, .obi-input-wrap select:focus { border-color:var(--green); box-shadow:0 0 0 3px rgba(26,122,60,.1); }
    .obi-pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--gray-400); font-size:.8rem; padding:4px; }
    .pw-bar { display:flex; gap:4px; margin-top:5px; }
    .pw-seg { flex:1; height:4px; border-radius:99px; background:var(--gray-200); transition:background .3s; }
    .pw-lbl { font-size:.7rem; margin-top:3px; font-weight:700; color:var(--gray-400); }
    .role-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:18px; }
    .role-card { border:2px solid var(--gray-200); border-radius:14px; padding:16px 12px; cursor:pointer; text-align:center; transition:all .2s; background:#fff; position:relative; overflow:hidden; }
    .role-card:hover { border-color:var(--green); background:var(--green-light); transform:translateY(-2px); }
    .role-card.selected { border-color:var(--green); background:var(--green-light); box-shadow:0 4px 18px rgba(26,122,60,.18); }
    .role-card.selected::after { content:'\f00c'; font-family:'Font Awesome 6 Free'; font-weight:900; position:absolute; top:7px; right:9px; font-size:.68rem; color:var(--green); }
    .role-card input[type="radio"] { display:none; }
    .role-emoji { font-size:1.8rem; margin-bottom:7px; display:block; }
    .role-title { font-weight:800; color:var(--green-dark); font-size:.83rem; margin-bottom:2px; }
    .role-desc  { font-size:.7rem; color:var(--gray-400); }
    .obi-error-box { display:none; background:#fee2e2; color:#991b1b; padding:11px 14px; border-radius:10px; font-size:.84rem; margin-bottom:16px; border-left:4px solid #ef4444; }
    .obi-submit-btn-signup { width:100%; padding:14px; background:var(--green); color:#fff; border:none; border-radius:14px; font-weight:800; font-size:.96rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:9px; transition:all .22s; font-family:inherit; }
    .obi-submit-btn-signup:hover:not(:disabled) { background:var(--green-dark); transform:translateY(-1px); box-shadow:0 8px 24px rgba(26,122,60,.3); }
    .obi-submit-btn-signup:disabled { opacity:.65; cursor:not-allowed; transform:none; }
    .obi-spinner { width:18px; height:18px; border:2.5px solid rgba(255,255,255,.35); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; display:none; }
    @keyframes spin { to{transform:rotate(360deg);} }
    @media(max-width:900px){ .obi-signup-left{display:none;} .obi-signup-right{width:100%;} }
    @media(max-width:480px){ .obi-signup-right{padding:16px 12px;} .obi-signup-card{padding:22px 16px;border-radius:18px;} }
  </style>
</head>
<body>
<div class="obi-signup">
  <!-- LEFT -->
  <div class="obi-signup-left">
    <div class="obi-signup-bg"></div>
    <div class="obi-signup-overlay"></div>
    <a href="<?= BASE ?>/index.php" class="obi-signup-logo">
      <div class="obi-signup-logo-icon">OB</div>
      <span style="font-weight:900;color:#fff;font-size:1.15rem;">ObiFunds</span>
    </a>
    <div class="obi-signup-content">
      <h2 class="obi-signup-tagline">Pool resources.<br>Create <span>real change.</span></h2>
      <p class="obi-signup-sub">Africa's go-to platform for community fundraising. Launch a drive or support a cause — powered by mobile money.</p>
      <div class="obi-signup-stats">
        <div class="obi-signup-stat"><span class="obi-signup-stat-val">12+</span><span class="obi-signup-stat-lbl">Countries</span></div>
        <div class="obi-signup-stat"><span class="obi-signup-stat-val">UGX 2B+</span><span class="obi-signup-stat-lbl">Total Raised</span></div>
        <div class="obi-signup-stat"><span class="obi-signup-stat-val">50K+</span><span class="obi-signup-stat-lbl">Givers</span></div>
      </div>
      <div class="obi-signup-pills">
        <span class="obi-signup-pill"><i class="fas fa-check-circle"></i> Free to start</span>
        <span class="obi-signup-pill"><i class="fas fa-bolt"></i> Same-day payout</span>
        <span class="obi-signup-pill"><i class="fas fa-mobile-alt"></i> MTN &amp; Airtel Money</span>
        <span class="obi-signup-pill"><i class="fas fa-chart-line"></i> Live tracking</span>
      </div>
    </div>
  </div>
  <!-- RIGHT -->
  <div class="obi-signup-right">
    <div class="obi-signup-form-wrap">
      <div style="text-align:center;margin-bottom:20px;display:none;" id="mobileLogo">
        <a href="<?= BASE ?>/index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
          <div style="width:42px;height:42px;background:var(--green);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--yellow);font-weight:900;font-size:.88rem;">OB</div>
          <span style="font-weight:900;color:var(--green-dark);font-size:1.1rem;">ObiFunds</span>
        </a>
      </div>
      <div class="obi-signup-card">
        <h1 class="obi-signup-heading">Create your account</h1>
        <p class="obi-signup-subheading">Join thousands making a difference across Africa 🌍</p>
        <div id="signupError" class="obi-error-box"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i><span id="signupErrorMsg"></span></div>
        <form id="signupForm" novalidate>
          <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label" style="font-size:.8rem;">Full Name <span style="color:var(--red-err)">*</span></label>
            <div class="obi-input-wrap">
              <i class="fas fa-user obi-input-left-icon"></i>
              <input type="text" name="full_name" placeholder="Your full name" required autocomplete="name"/>
            </div>
          </div>
          <div class="grid-2" style="gap:10px;margin-bottom:0;">
            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label" style="font-size:.8rem;">Email <span style="color:var(--red-err)">*</span></label>
              <div class="obi-input-wrap">
                <i class="fas fa-envelope obi-input-left-icon"></i>
                <input type="email" name="email" placeholder="you@email.com" required/>
              </div>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label" style="font-size:.8rem;">Phone <span style="color:var(--red-err)">*</span></label>
              <div class="obi-input-wrap">
                <i class="fas fa-phone obi-input-left-icon"></i>
                <input type="tel" name="phone" id="phone" placeholder="256712345678" required/>
              </div>
            </div>
          </div>
          <div class="grid-2" style="gap:10px;margin-bottom:0;">
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label" style="font-size:.8rem;">Password <span style="color:var(--red-err)">*</span></label>
              <div class="obi-input-wrap">
                <i class="fas fa-lock obi-input-left-icon"></i>
                <input type="password" id="password" name="password" placeholder="••••••••" required/>
                <button type="button" class="obi-pw-toggle" onclick="togglePw('password',this)"><i class="fas fa-eye"></i></button>
              </div>
              <div class="pw-bar" id="pwBar"><div class="pw-seg" id="s1"></div><div class="pw-seg" id="s2"></div><div class="pw-seg" id="s3"></div><div class="pw-seg" id="s4"></div></div>
              <div class="pw-lbl" id="pwLbl"></div>
            </div>
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label" style="font-size:.8rem;">Confirm Password <span style="color:var(--red-err)">*</span></label>
              <div class="obi-input-wrap">
                <i class="fas fa-lock obi-input-left-icon"></i>
                <input type="password" id="confirmPw" name="confirm_password" placeholder="••••••••" required/>
                <button type="button" class="obi-pw-toggle" onclick="togglePw('confirmPw',this)"><i class="fas fa-eye"></i></button>
              </div>
            </div>
          </div>
          <div class="form-group" style="margin-top:12px;margin-bottom:14px;">
            <label class="form-label" style="font-size:.8rem;">Country</label>
            <div class="obi-input-wrap">
              <i class="fas fa-globe-africa obi-input-left-icon"></i>
              <select name="country">
                <option>Uganda</option><option>Kenya</option><option>Rwanda</option>
                <option>Tanzania</option><option>Nigeria</option><option>Ghana</option><option>Zambia</option>
              </select>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label" style="font-size:.8rem;">I want to… <span style="color:var(--red-err)">*</span></label>
            <div class="role-grid">
              <label class="role-card" id="roleCreator" onclick="selectRole('creator')" tabindex="0">
                <input type="radio" name="role" value="campaigner"/>
                <span class="role-emoji">🚀</span>
                <p class="role-title">Run Drives</p>
                <p class="role-desc">Raise money for causes</p>
              </label>
              <label class="role-card" id="roleDonor" onclick="selectRole('donor')" tabindex="0">
                <input type="radio" name="role" value="donor"/>
                <span class="role-emoji">❤️</span>
                <p class="role-title">Give &amp; Support</p>
                <p class="role-desc">Back campaigns</p>
              </label>
            </div>
          </div>
          <div style="display:flex;align-items:flex-start;gap:9px;margin-bottom:18px;">
            <input type="checkbox" id="agreeTerms" required style="margin-top:2px;width:15px;height:15px;cursor:pointer;accent-color:var(--green);flex-shrink:0;"/>
            <label for="agreeTerms" style="font-size:.8rem;color:var(--gray-500);line-height:1.5;">
              I agree to the <a href="#" style="color:var(--green);font-weight:700;">Terms of Use</a> and <a href="#" style="color:var(--green);font-weight:700;">Privacy Policy</a>
            </label>
          </div>
          <button type="submit" id="signupBtn" class="obi-submit-btn-signup">
            <div class="obi-spinner" id="btnSpinner"></div>
            <i class="fas fa-user-plus" id="btnIcon"></i>
            <span id="btnText">Create Account</span>
          </button>
        </form>
        <p style="text-align:center;font-size:.86rem;color:var(--gray-400);margin-top:18px;">
          Already have an account? <a href="<?= BASE ?>/login.php" style="color:var(--green);font-weight:700;">Sign In</a>
        </p>
      </div>
    </div>
  </div>
</div>
<script>
if (window.innerWidth<=900) document.getElementById('mobileLogo').style.display='block';
function togglePw(id,btn){ var i=document.getElementById(id); var s=i.type==='password'; i.type=s?'text':'password'; btn.innerHTML=s?'<i class="fas fa-eye-slash"></i>':'<i class="fas fa-eye"></i>'; }
function selectRole(r){ ['Creator','Donor'].forEach(function(x){ document.getElementById('role'+x).classList.toggle('selected',x.toLowerCase()===r||(r==='creator'&&x==='Creator')||(r==='donor'&&x==='Donor')); }); document.getElementById('roleCreator').classList.toggle('selected',r==='creator'); document.getElementById('roleDonor').classList.toggle('selected',r==='donor'); document.getElementById('roleCreator').querySelector('input').checked=(r==='creator'); document.getElementById('roleDonor').querySelector('input').checked=(r==='donor'); }
var sc=['','#dc2626','#f59e0b','#3b82f6','#16a34a'],sl=['','Weak','Fair','Good','Strong'];
document.getElementById('password').addEventListener('input',function(){ var v=this.value,s=0; if(v.length>=8)s++; if(/[A-Z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++; [1,2,3,4].forEach(function(i){ document.getElementById('s'+i).style.background=i<=s?sc[s]:'var(--gray-200)'; }); var l=document.getElementById('pwLbl'); l.textContent=v.length?sl[s]:''; l.style.color=sc[s]||'var(--gray-400)'; });
function showError(m){ var b=document.getElementById('signupError'); document.getElementById('signupErrorMsg').textContent=m; b.style.display='block'; b.scrollIntoView({behavior:'smooth',block:'nearest'}); }
document.getElementById('signupForm').addEventListener('submit',async function(e){ e.preventDefault();
  var pw=document.getElementById('password').value, cpw=document.getElementById('confirmPw').value;
  if(pw.length<6){showError('Password must be at least 6 characters.');return;}
  if(pw!==cpw){showError('Passwords do not match.');return;}
  if(!document.querySelector('input[name="role"]:checked')){showError('Choose how you want to use ObiFunds.');return;}
  if(!document.getElementById('agreeTerms').checked){showError('Please agree to the Terms to continue.');return;}
  var btn=document.getElementById('signupBtn'); btn.disabled=true;
  document.getElementById('btnSpinner').style.display='block';
  document.getElementById('btnIcon').style.display='none';
  document.getElementById('btnText').textContent='Creating account…';
  var fd=new FormData(this); fd.append('action','register');
  try{ var res=await fetch('<?= BASE ?>/api/auth.php?action=register',{method:'POST',body:fd}); var data=await res.json();
    if(data.success){ 
      document.getElementById('btnText').textContent='Success! Redirecting…';
      document.getElementById('btnSpinner').style.display='block';
      document.getElementById('btnIcon').style.display='none';
      // Show success state
      document.getElementById('signupError').style.display='none';
      setTimeout(function(){ window.location.href=data.redirect; }, 1000);
    }
    else{ showError(data.message||'Registration failed.'); btn.disabled=false; document.getElementById('btnSpinner').style.display='none'; document.getElementById('btnIcon').style.display='inline'; document.getElementById('btnText').textContent='Create Account'; }
  } catch(err){ showError('Network error. Please try again.'); btn.disabled=false; document.getElementById('btnSpinner').style.display='none'; document.getElementById('btnIcon').style.display='inline'; document.getElementById('btnText').textContent='Create Account'; }
});
</script>
</body>
</html>
