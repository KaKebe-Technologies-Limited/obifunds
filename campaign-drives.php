<?php
// ObiFunds – campaign-drives.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$pageTitle       = 'Browse Drives – ObiFunds';
$pageDescription = 'Browse active fundraising drives across Africa. Support medical, education, community and emergency causes via mobile money.';

$result = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name,
            ROUND((c.raised_amount/c.goal_amount)*100,1) AS pct,
            DATEDIFF(c.end_date,NOW()) AS days_left
     FROM campaigns c JOIN users u ON c.campaigner_id=u.user_id
     WHERE c.status='active' ORDER BY c.created_at DESC"
);
$campaigns = [];
while ($r = $result->fetch_assoc()) $campaigns[] = $r;
$totalCount = count($campaigns);

include __DIR__ . '/includes/header.php';
?>

<!-- PAGE HERO -->
<div style="background:linear-gradient(135deg,var(--green-dark) 0%,var(--green) 100%);padding:100px 0 44px;margin-top:68px;">
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;">
      <div>
        <span style="display:inline-flex;align-items:center;gap:6px;background:rgba(245,197,24,.2);color:var(--yellow);font-size:.75rem;font-weight:800;padding:4px 14px;border-radius:99px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;">
          <span style="width:7px;height:7px;background:var(--yellow);border-radius:50%;animation:obi-pulse 1s infinite;"></span>
          Live Drives
        </span>
        <h1 style="font-size:clamp(1.6rem,4vw,2.4rem);font-weight:900;color:#fff;letter-spacing:-.02em;margin-bottom:8px;">Active Fundraising Drives</h1>
        <p style="color:rgba(255,255,255,.7);font-size:.9rem;">
          <?= number_format($totalCount) ?> drives live right now — pick a cause and make your contribution count.
        </p>
      </div>
      <a href="<?= BASE ?>/create-campaign.php" class="btn btn-yellow btn-lg">
        <i class="fas fa-rocket"></i> Start a Drive
      </a>
    </div>
  </div>
</div>

<!-- FILTERS -->
<div style="background:#fff;border-bottom:1px solid var(--gray-200);padding:14px 0;position:sticky;top:68px;z-index:200;box-shadow:0 2px 12px rgba(0,0,0,.05);">
  <div class="container">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
      <div style="position:relative;flex:1;min-width:160px;max-width:340px;">
        <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:.82rem;"></i>
        <input type="text" id="campaignSearch" class="form-input" placeholder="Search drives…" style="padding-left:36px;border-radius:10px;height:42px;"/>
      </div>
      <select id="categoryFilter" class="form-input" style="flex:1;min-width:120px;max-width:160px;height:42px;border-radius:10px;">
        <option value="">All Categories</option>
        <option value="family">Family</option><option value="medical">Medical</option>
        <option value="education">Education</option><option value="community">Community</option>
        <option value="business">Business</option><option value="emergency">Emergency</option>
      </select>
      <select id="sortFilter" class="form-input" style="flex:1;min-width:120px;max-width:160px;height:42px;border-radius:10px;">
        <option value="newest">Newest First</option>
        <option value="funded">Most Funded</option>
        <option value="ending">Ending Soon</option>
      </select>
      <span id="resultCount" style="font-size:.82rem;color:var(--gray-400);font-weight:600;white-space:nowrap;"><?= $totalCount ?> drives</span>
    </div>
  </div>
</div>

<!-- GRID -->
<section style="background:var(--gray-50);padding:36px 0 80px;">
  <div class="container">
    <div class="campaigns-grid" id="campaignsGrid">
      <?php if (!empty($campaigns)): ?>
        <?php foreach ($campaigns as $c): ?>
          <?php
            $pct     = min(100,(float)$c['pct']);
            $dl      = (int)$c['days_left'];
            $dlStr   = $dl>0 ? "$dl days left" : ($dl===0 ? 'Ends today' : 'Ended');
            $dlUrgent= $dl<=3;
            $cat     = strtolower($c['category']??'other');
            $img     = $c['image_url'] ?: 'https://picsum.photos/seed/'.($c['slug']??$c['campaign_id']).'/600/400';
          ?>
          <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>"
             class="card campaign-card obi-drive-card"
             data-title="<?= strtolower(htmlspecialchars($c['title'])) ?>"
             data-category="<?= $cat ?>"
             data-funded="<?= $c['raised_amount'] ?>"
             data-days="<?= $dl ?>">
            <div style="position:relative;">
              <img class="card-img" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy"/>
              <span class="obi-card-cat"><?= htmlspecialchars($c['category']??'General') ?></span>
              <?php if($dlUrgent&&$dl>=0): ?>
                <span style="position:absolute;top:10px;right:10px;background:#dc2626;color:#fff;font-size:.62rem;font-weight:800;padding:3px 9px;border-radius:99px;text-transform:uppercase;">Urgent</span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <p class="campaign-title"><?= htmlspecialchars($c['title']) ?></p>
              <p style="font-size:.76rem;color:var(--gray-400);margin-bottom:10px;">by <?= htmlspecialchars($c['campaigner_name']) ?></p>
              <div class="obi-raised-row">
                <span class="obi-raised-amt"><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?></span>
                <span class="obi-raised-pct"><?= $pct ?>%</span>
              </div>
              <div class="progress-wrap"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
              <div class="campaign-footer">
                <span class="contributors-count"><i class="fas fa-users" style="margin-right:4px;"></i><?= $c['contributor_count'] ?> givers</span>
                <span class="days-left" <?= $dlUrgent?'style="color:#dc2626;"':'' ?>><?= $dlStr ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:72px 0;color:var(--gray-400);">
          <i class="fas fa-seedling" style="font-size:3rem;margin-bottom:16px;display:block;color:var(--gray-200);"></i>
          <p style="font-size:1rem;font-weight:700;color:var(--gray-600);margin-bottom:8px;">No active drives yet.</p>
          <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary" style="margin-top:14px;">Be the first to launch</a>
        </div>
      <?php endif; ?>
    </div>
    <div id="noResults" style="display:none;text-align:center;padding:60px 0;color:var(--gray-400);">
      <i class="fas fa-search" style="font-size:2.5rem;margin-bottom:14px;display:block;"></i>
      <p style="font-size:1rem;font-weight:700;color:var(--gray-600);">No drives match your search.</p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
<style>
@keyframes obi-pulse{0%,100%{opacity:1;}50%{opacity:.3;}}
.obi-drive-card{text-decoration:none;color:inherit;transition:all .2s;}
.obi-card-cat{position:absolute;top:10px;left:10px;background:var(--yellow);color:var(--gray-900);font-size:.64rem;font-weight:800;padding:3px 10px;border-radius:99px;text-transform:uppercase;letter-spacing:.05em;}
.obi-raised-row{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:4px;}
.obi-raised-amt{font-weight:800;color:var(--green-dark);font-size:.9rem;}
.obi-raised-pct{font-size:.76rem;font-weight:700;color:var(--green);}

/* ── Drives grid — mobile-first ── */
#campaignsGrid.campaigns-grid {
  display: grid;
  grid-template-columns: 1fr;          /* mobile: 1 card full width */
  gap: 20px;
}
@media(min-width:600px) {
  #campaignsGrid.campaigns-grid { grid-template-columns: repeat(2,1fr); }
}
@media(min-width:1024px) {
  #campaignsGrid.campaigns-grid { grid-template-columns: repeat(3,1fr); }
}

/* Make cards a comfortable height on mobile */
@media(max-width:599px) {
  #campaignsGrid .card-img { height: 200px; object-fit: cover; }
  #campaignsGrid .campaign-card { margin: 0; }
  #campaignsGrid .card-body { padding: 16px; }
}
</style>
<script>
var cards = Array.from(document.querySelectorAll('.obi-drive-card'));
function filterCards(){
  var q  = document.getElementById('campaignSearch').value.toLowerCase();
  var cat = document.getElementById('categoryFilter').value;
  var srt = document.getElementById('sortFilter').value;
  var vis = cards.filter(function(c){
    var matchQ   = !q   || c.dataset.title.includes(q);
    var matchCat = !cat || c.dataset.category===cat;
    return matchQ && matchCat;
  });
  if(srt==='funded') vis.sort(function(a,b){return b.dataset.funded-a.dataset.funded;});
  else if(srt==='ending') vis.sort(function(a,b){return a.dataset.days-b.dataset.days;});
  var grid = document.getElementById('campaignsGrid');
  cards.forEach(function(c){ c.style.display='none'; });
  vis.forEach(function(c){ c.style.display=''; grid.appendChild(c); });
  document.getElementById('noResults').style.display = vis.length?'none':'block';
  document.getElementById('resultCount').textContent = vis.length+' drive'+(vis.length!==1?'s':'');
}
['campaignSearch','categoryFilter','sortFilter'].forEach(function(id){
  document.getElementById(id).addEventListener('input',filterCards);
  document.getElementById(id).addEventListener('change',filterCards);
});
</script>
