<?php
// ObiFunds – donate.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$pageTitle       = 'Give Now – ObiFunds';
$pageDescription = 'Make a difference today. Donate to health, education and community drives via MTN or Airtel Money.';

$result = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name,
            ROUND((c.raised_amount/c.goal_amount)*100,1) AS pct,
            DATEDIFF(c.end_date,NOW()) AS days_left
     FROM campaigns c JOIN users u ON c.campaigner_id=u.user_id
     WHERE c.status='active' ORDER BY c.raised_amount DESC"
);
$campaigns = [];
while ($r = $result->fetch_assoc()) $campaigns[] = $r;

include __DIR__ . '/includes/header.php';
?>

<div style="background:linear-gradient(135deg,var(--green-dark) 0%,var(--green) 100%);padding:100px 0 52px;margin-top:68px;text-align:center;">
  <div class="container">
    <span style="display:inline-flex;align-items:center;gap:7px;background:rgba(245,197,24,.2);color:var(--yellow);font-size:.75rem;font-weight:800;padding:4px 16px;border-radius:99px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;">
      <i class="fas fa-heart"></i> Give Today
    </span>
    <h1 style="font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;color:#fff;letter-spacing:-.02em;margin-bottom:14px;">Your Contribution Creates Change</h1>
    <p style="color:rgba(255,255,255,.78);max-width:480px;margin:0 auto 28px;font-size:.95rem;line-height:1.7;">Browse drives below and give via mobile money — it takes less than 60 seconds.</p>
    <div style="max-width:520px;margin:0 auto;position:relative;">
      <i class="fas fa-search" style="position:absolute;left:18px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:.9rem;"></i>
      <input type="text" id="donateSearch" placeholder="Search a cause…" class="form-input" style="padding-left:48px;font-size:.95rem;border-radius:99px;height:52px;border:none;"/>
    </div>
  </div>
</div>

<div style="background:#fff;border-bottom:1px solid var(--gray-200);padding:12px 0;position:sticky;top:68px;z-index:200;">
  <div class="container">
    <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">
      <?php foreach(['','family','medical','education','community','business','emergency'] as $cat): ?>
        <button class="obi-cat-pill <?= $cat===''?'active':'' ?>" data-cat="<?= $cat ?>">
          <?= $cat==='' ? 'All' : ucfirst($cat) ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<section style="background:var(--gray-50);padding:36px 0 80px;">
  <div class="container">
    <div class="campaigns-grid" id="donateGrid">
      <?php foreach ($campaigns as $c): ?>
        <?php
          $pct  = min(100,(float)$c['pct']);
          $dl   = (int)$c['days_left'];
          $dlStr= $dl>0?"$dl days left":($dl===0?'Ends today':'Ended');
          $img  = $c['image_url']?:'https://picsum.photos/seed/'.($c['slug']??$c['campaign_id']).'/600/400';
        ?>
        <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>"
           class="card campaign-card obi-drive-card"
           data-title="<?= strtolower(htmlspecialchars($c['title'])) ?>"
           data-cat="<?= strtolower($c['category']??'other') ?>">
          <div style="position:relative;">
            <img class="card-img" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy"/>
            <span class="obi-card-cat"><?= htmlspecialchars($c['category']??'General') ?></span>
          </div>
          <div class="card-body">
            <p class="campaign-title"><?= htmlspecialchars($c['title']) ?></p>
            <div class="obi-raised-row">
              <span class="obi-raised-amt"><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?></span>
              <span class="obi-raised-pct"><?= $pct ?>%</span>
            </div>
            <div class="progress-wrap"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
            <div class="campaign-footer">
              <span class="contributors-count"><i class="fas fa-users" style="margin-right:4px;"></i><?= $c['contributor_count'] ?> givers</span>
              <span class="days-left"><?= $dlStr ?></span>
            </div>
            <div style="margin-top:12px;">
              <span class="btn btn-primary btn-sm btn-block" style="pointer-events:none;background:var(--green);">
                <i class="fas fa-hand-holding-heart"></i> Give Now
              </span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if(empty($campaigns)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:72px 0;color:var(--gray-400);">
          <i class="fas fa-seedling" style="font-size:3rem;display:block;margin-bottom:14px;color:var(--gray-200);"></i>
          <p style="font-weight:700;color:var(--gray-600);">No active drives yet.</p>
          <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary" style="margin-top:14px;">Launch the first one</a>
        </div>
      <?php endif; ?>
    </div>
    <div id="noResultsDonate" style="display:none;text-align:center;padding:60px 0;">
      <i class="fas fa-search" style="font-size:2.5rem;color:var(--gray-200);display:block;margin-bottom:12px;"></i>
      <p style="font-weight:700;color:var(--gray-600);">No drives match that search.</p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
<style>
.obi-cat-pill{padding:7px 18px;border-radius:99px;border:1.5px solid var(--gray-200);background:#fff;font-size:.8rem;font-weight:700;color:var(--gray-600);cursor:pointer;transition:all .15s;}
.obi-cat-pill:hover{border-color:var(--green);color:var(--green);}
.obi-cat-pill.active{background:var(--green);color:#fff;border-color:var(--green);}
.obi-drive-card{text-decoration:none;color:inherit;}
.obi-card-cat{position:absolute;top:10px;left:10px;background:var(--yellow);color:var(--gray-900);font-size:.64rem;font-weight:800;padding:3px 10px;border-radius:99px;text-transform:uppercase;}
.obi-raised-row{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:4px;}
.obi-raised-amt{font-weight:800;color:var(--green-dark);font-size:.9rem;}
.obi-raised-pct{font-size:.76rem;font-weight:700;color:var(--green);}
@media(max-width:1023px){.campaigns-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:767px){.campaigns-grid{grid-template-columns:1fr;}}
</style>
<script>
var allCards = Array.from(document.querySelectorAll('#donateGrid .obi-drive-card'));
var activeCat = '';
function filterDonate(){
  var q = document.getElementById('donateSearch').value.toLowerCase();
  var vis = allCards.filter(function(c){ return (!q||c.dataset.title.includes(q))&&(!activeCat||c.dataset.cat===activeCat); });
  allCards.forEach(function(c){ c.style.display='none'; });
  vis.forEach(function(c){ c.style.display=''; });
  document.getElementById('noResultsDonate').style.display = vis.length?'none':'block';
}
document.getElementById('donateSearch').addEventListener('input', filterDonate);
document.querySelectorAll('.obi-cat-pill').forEach(function(btn){
  btn.addEventListener('click',function(){
    document.querySelectorAll('.obi-cat-pill').forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
    activeCat = this.dataset.cat;
    filterDonate();
  });
});
</script>
