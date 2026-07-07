<?php
// ============================================================
// ObiFunds – index.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$pageTitle       = 'ObiFunds – Raise Money for What Matters';
$pageDescription = 'Africa\'s smartest crowdfunding platform. Launch a fundraising drive in 60 seconds and receive funds via MTN & Airtel Money — same day.';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $protocol = trim($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : $protocol;
$siteUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
$ogImage = BASE . '/img/logo.png';

$extraCss = <<<HTML
  <meta property="og:type"        content="website"/>
  <meta property="og:url"         content="{$siteUrl}/"/>
  <meta property="og:site_name"   content="ObiFunds"/>
  <meta property="og:title"       content="ObiFunds – Raise Money for What Matters"/>
  <meta property="og:description" content="Africa's smartest crowdfunding platform. Launch a drive in 60 seconds."/>
  <meta property="og:image"       content="{$ogImage}"/>
  <meta name="twitter:card"       content="summary_large_image"/>
  <meta name="twitter:title"      content="ObiFunds – Raise Money for What Matters"/>
  <meta name="twitter:image"      content="{$ogImage}"/>
HTML;

$dbConnected = ($conn && !$conn->connect_error);

// Platform stats
$totalRaised       = (float)$conn->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='completed'")->fetch_row()[0];
$activeCampaigns   = (int)$conn->query("SELECT COUNT(*) FROM campaigns WHERE status='active'")->fetch_row()[0];
$totalContributors = (int)$conn->query("SELECT COUNT(DISTINCT donor_phone) FROM donations WHERE status='completed'")->fetch_row()[0];
$totalCampaigns    = (int)$conn->query("SELECT COUNT(*) FROM campaigns")->fetch_row()[0];

// Featured campaigns (latest 4 active)
$featured = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name,
            ROUND((c.raised_amount/c.goal_amount)*100,1) AS pct,
            DATEDIFF(c.end_date, NOW()) AS days_left
     FROM campaigns c
     JOIN users u ON c.campaigner_id = u.user_id
     WHERE c.status='active'
     ORDER BY c.created_at DESC LIMIT 4"
);

// Slider campaigns (6 for the hero carousel)
$sliderCampaigns = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name,
            ROUND((c.raised_amount/c.goal_amount)*100,1) AS pct
     FROM campaigns c
     JOIN users u ON c.campaigner_id = u.user_id
     WHERE c.status='active' AND c.image_url IS NOT NULL AND c.image_url != ''
     ORDER BY c.raised_amount DESC LIMIT 6"
);
$slides = [];
if ($sliderCampaigns && $sliderCampaigns->num_rows > 0) {
    while ($s = $sliderCampaigns->fetch_assoc()) $slides[] = $s;
}

// Fallback slides — vivid Unsplash photos when no live campaigns exist
$fallbackSlides = [
    [
        'title'    => 'Together We Can Build Better Schools',
        'category' => 'Education',
        'pct'      => 68,
        'raised'   => 'UGX 3.4M',
        'img'      => 'https://images.unsplash.com/photo-1540479859555-17af45c78602?w=1600&q=85',
        'sub'      => 'Kampala, Uganda',
    ],
    [
        'title'    => 'Emergency Medical Fund — Nakato Family',
        'category' => 'Medical',
        'pct'      => 42,
        'raised'   => 'UGX 1.2M',
        'img'      => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1600&q=85',
        'sub'      => 'Mbarara, Uganda',
    ],
    [
        'title'    => 'Clean Water for 500 Families in Karamoja',
        'category' => 'Community',
        'pct'      => 85,
        'raised'   => 'UGX 8.5M',
        'img'      => 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=1600&q=85',
        'sub'      => 'Moroto, Uganda',
    ],
];

include __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════ HERO SLIDER ═══════════════ -->
<section class="obi-hero" id="heroSlider">
  <div class="obi-slides-wrap" id="slidesWrap">

    <?php if (!empty($slides)): ?>
      <?php foreach ($slides as $i => $s): ?>
      <div class="obi-slide <?= $i===0?'active':'' ?>"
           style="background-image:url('<?= htmlspecialchars($s['image_url']) ?>')">
        <div class="obi-slide-overlay"></div>
        <div class="container obi-slide-inner">
          <span class="obi-slide-cat"><?= htmlspecialchars($s['category']) ?></span>
          <h1 class="obi-slide-title"><?= htmlspecialchars($s['title']) ?></h1>
          <div class="obi-slide-prog">
            <div class="obi-slide-prog-bar"><div style="width:<?= min(100,(float)$s['pct']) ?>%"></div></div>
            <span><?= $s['pct'] ?>% of goal reached · <?= $s['currency'] ?> <?= number_format($s['raised_amount']) ?> raised</span>
          </div>
          <div class="obi-slide-btns">
            <a href="<?= BASE ?>/campaign-detail.php?id=<?= $s['campaign_id'] ?>" class="btn btn-yellow btn-lg">
              Support This Drive
            </a>
            <a href="<?= BASE ?>/create-campaign.php" class="btn btn-outline-white btn-lg">
              Start Your Own
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($fallbackSlides as $i => $s): ?>
      <div class="obi-slide <?= $i===0?'active':'' ?>"
           style="background-image:url('<?= $s['img'] ?>')">
        <div class="obi-slide-overlay"></div>
        <div class="container obi-slide-inner">
          <span class="obi-slide-cat"><?= $s['category'] ?></span>
          <h1 class="obi-slide-title"><?= $s['title'] ?></h1>
          <p style="color:rgba(255,255,255,.7);font-size:.88rem;margin-bottom:10px;"><i class="fas fa-map-marker-alt" style="margin-right:5px;"></i><?= $s['sub'] ?></p>
          <div class="obi-slide-prog">
            <div class="obi-slide-prog-bar"><div style="width:<?= $s['pct'] ?>%"></div></div>
            <span><?= $s['pct'] ?>% of goal · <?= $s['raised'] ?> raised</span>
          </div>
          <div class="obi-slide-btns">
            <a href="<?= BASE ?>/campaign-drives.php" class="btn btn-yellow btn-lg">
              Support This Drive
            </a>
            <a href="<?= BASE ?>/create-campaign.php" class="btn btn-outline-white btn-lg">Start Your Own</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Slider controls -->
  <button class="obi-slide-prev" id="slidePrev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
  <button class="obi-slide-next" id="slideNext" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
  <div class="obi-slide-dots" id="slideDots"></div>

  <!-- Floating stat chips -->
  <div class="obi-hero-chips">
    <div class="obi-chip"><i class="fas fa-fire"></i><span><?= number_format($activeCampaigns) ?> Active Drives</span></div>
    <div class="obi-chip"><i class="fas fa-users"></i><span><?= number_format($totalContributors) ?>+ Givers</span></div>
    <div class="obi-chip obi-chip-yellow"><i class="fas fa-bolt"></i><span>Same-Day Payout</span></div>
  </div>
</section>

<!-- ═══════════════ STATS COUNTER BAR ═══════════════ -->
<div class="obi-stats-bar">
  <div class="container">
    <div class="obi-stats-grid">
      <div class="obi-stat" data-target="<?= (int)($totalRaised/1000) ?>" data-suffix="K">
        <span class="obi-stat-val" id="stat0">0</span>
        <span class="obi-stat-lbl">UGX raised (thousands)</span>
      </div>
      <div class="obi-stat" data-target="<?= $totalCampaigns ?>" data-suffix="">
        <span class="obi-stat-val" id="stat1">0</span>
        <span class="obi-stat-lbl">Drives launched</span>
      </div>
      <div class="obi-stat" data-target="<?= $activeCampaigns ?>" data-suffix="">
        <span class="obi-stat-val" id="stat2">0</span>
        <span class="obi-stat-lbl">Live right now</span>
      </div>
      <div class="obi-stat" data-target="<?= $totalContributors ?>" data-suffix="+">
        <span class="obi-stat-val" id="stat3">0</span>
        <span class="obi-stat-lbl">Community givers</span>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════ LIVE DRIVES ═══════════════ -->
<section class="section" style="background:var(--gray-50);">
  <div class="container">
    <div class="section-header">
      <span class="section-eyebrow"><i class="fas fa-circle" style="font-size:.5rem;margin-right:6px;animation:obi-pulse 1s infinite;"></i> Live Right Now</span>
      <h2 class="section-title">Active Drives You Can Join</h2>
      <p class="section-sub">Every contribution counts. Pick a cause and make your mark today.</p>
    </div>

    <div class="home-campaigns-grid" id="homeCampaignsGrid">
      <?php
        $featuredArr = [];
        if ($featured && $featured->num_rows > 0) {
            while ($c = $featured->fetch_assoc()) $featuredArr[] = $c;
        }
        $showList = count($featuredArr) > 4;
      ?>
      <?php if (!empty($featuredArr)): ?>
        <?php foreach ($featuredArr as $ci => $c): ?>
          <?php
            $pct      = min(100, (float)$c['pct']);
            $daysLeft = (int)$c['days_left'];
            $daysStr  = $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Ended');
            $image    = $c['image_url'] ?: 'https://picsum.photos/seed/' . ($c['slug'] ?? $c['campaign_id']) . '/600/400';
            $isHidden = $showList && $ci >= 4;
          ?>
          <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>"
             class="card campaign-card obi-campaign-card<?= $isHidden ? ' obi-card-hidden' : '' ?>"
             style="text-decoration:none;color:inherit;">
            <div style="position:relative;">
              <img class="card-img" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
              <span class="obi-card-cat"><?= htmlspecialchars($c['category'] ?? 'General') ?></span>
            </div>
            <div class="card-body">
              <p class="campaign-title"><?= htmlspecialchars($c['title']) ?></p>
              <div class="obi-raised-row">
                <span class="obi-raised-amt"><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?></span>
                <span class="obi-raised-pct"><?= $pct ?>%</span>
              </div>
              <div class="progress-wrap"><div class="progress-fill" data-width="<?= $pct ?>"></div></div>
              <div class="campaign-footer">
                <span class="contributors-count"><i class="fas fa-users" style="margin-right:4px;"></i><?= $c['contributor_count'] ?> givers</span>
                <span class="days-left" <?= $daysLeft <= 3 ? 'style="color:#dc2626;"' : '' ?>><?= $daysStr ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>

        <?php if ($showList): ?>
        <!-- Remaining drives as a compact list -->
        <div class="obi-more-list" id="obiMoreList" style="display:none;grid-column:1/-1;">
          <h3 style="font-size:.88rem;font-weight:800;color:var(--green-dark);margin-bottom:12px;text-transform:uppercase;letter-spacing:.05em;">
            More Drives (<?= count($featuredArr) - 4 ?> more)
          </h3>
          <?php foreach ($featuredArr as $ci => $c): ?>
            <?php if ($ci < 4) continue; ?>
            <?php
              $pct      = min(100, (float)$c['pct']);
              $daysLeft = (int)$c['days_left'];
              $daysStr  = $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Ended');
            ?>
            <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>"
               class="obi-list-item" style="text-decoration:none;">
              <div class="obi-list-left">
                <span class="obi-list-cat"><?= htmlspecialchars($c['category'] ?? 'General') ?></span>
                <span class="obi-list-title"><?= htmlspecialchars($c['title']) ?></span>
              </div>
              <div class="obi-list-right">
                <span class="obi-list-pct"><?= $pct ?>%</span>
                <span class="obi-list-days"><?= $daysStr ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
        <div style="grid-column:1/-1;text-align:center;margin-top:4px;">
          <button id="obiShowMoreBtn" onclick="toggleMoreDrives()"
            style="background:none;border:1.5px solid var(--green);color:var(--green);padding:8px 22px;border-radius:99px;font-size:.82rem;font-weight:700;cursor:pointer;">
            Show <?= count($featuredArr) - 4 ?> more drives <i class="fas fa-chevron-down" style="margin-left:4px;"></i>
          </button>
        </div>
        <?php endif; ?>

      <?php else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:64px 0;color:var(--gray-400);">
          <i class="fas fa-seedling" style="font-size:3rem;margin-bottom:16px;display:block;color:var(--green-light);"></i>
          <p style="font-size:1.05rem;font-weight:700;color:var(--gray-600);margin-bottom:8px;">No drives yet — be the first!</p>
          <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary" style="margin-top:12px;">Launch a Drive</a>
        </div>
      <?php endif; ?>
    </div>

    <div style="text-align:center;margin-top:40px;">
      <a href="<?= BASE ?>/campaign-drives.php" class="btn btn-outline">
        See All Drives <i class="fas fa-arrow-right" style="margin-left:6px;"></i>
      </a>
    </div>
  </div>
</section>

<!-- ═══════════════ HOW IT WORKS ═══════════════ -->
<section class="section obi-how" id="how-it-works">
  <div class="container">
    <div class="section-header">
      <span class="section-eyebrow">The Process</span>
      <h2 class="section-title">Three Steps. Done.</h2>
      <p class="section-sub">From idea to funded in under two minutes. No complicated forms, no waiting.</p>
    </div>
    <div class="obi-how-grid">
      <div class="obi-how-card">
        <div class="obi-how-num">01</div>
        <div class="obi-how-icon"><i class="fas fa-rocket"></i></div>
        <h3>Launch Your Drive</h3>
        <p>Fill in your story, set a goal, add photos — and go live instantly. Free, always.</p>
        <span class="obi-how-tag">Free to start</span>
      </div>
      <div class="obi-how-connector"><i class="fas fa-arrow-right"></i></div>
      <div class="obi-how-card">
        <div class="obi-how-num">02</div>
        <div class="obi-how-icon"><i class="fas fa-share-nodes"></i></div>
        <h3>Share Everywhere</h3>
        <p>Send your link on WhatsApp, Facebook, or email. Anyone can give — no account needed.</p>
        <span class="obi-how-tag">One tap to share</span>
      </div>
      <div class="obi-how-connector"><i class="fas fa-arrow-right"></i></div>
      <div class="obi-how-card">
        <div class="obi-how-num">03</div>
        <div class="obi-how-icon"><i class="fas fa-mobile-screen-button"></i></div>
        <h3>Collect via Mobile Money</h3>
        <p>Donors pay with MTN or Airtel Money. You withdraw to your phone — same day.</p>
        <span class="obi-how-tag obi-how-tag-yellow">Same-day payout</span>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ WHY OBIFUNDS ═══════════════ -->
<section class="section" style="background:var(--green-dark);overflow:hidden;position:relative;">
  <div style="position:absolute;top:-60px;right:-60px;width:320px;height:320px;border-radius:50%;background:rgba(245,197,24,.06);"></div>
  <div style="position:absolute;bottom:-80px;left:-40px;width:240px;height:240px;border-radius:50%;background:rgba(255,255,255,.04);"></div>
  <div class="container" style="position:relative;z-index:2;">
    <div class="section-header">
      <span class="section-eyebrow" style="color:var(--yellow);">Why Us</span>
      <h2 class="section-title" style="color:#fff;">Built Different. Built for Africa.</h2>
      <p class="section-sub" style="color:rgba(255,255,255,.65);">We didn't just translate a Western product — we built ObiFunds from the ground up for how money moves here.</p>
    </div>
    <div class="obi-why-grid">
      <div class="obi-why-card">
        <div class="obi-why-icon"><i class="fas fa-sim-card"></i></div>
        <h4>Mobile Money Native</h4>
        <p>MTN MoMo, Airtel Money, and more — no bank account needed to give or receive.</p>
      </div>
      <div class="obi-why-card">
        <div class="obi-why-icon"><i class="fas fa-bolt"></i></div>
        <h4>Same-Day Withdrawals</h4>
        <p>Funds hit your phone within hours of approval. No 7-day holds, no nonsense.</p>
      </div>
      <div class="obi-why-card">
        <div class="obi-why-icon"><i class="fas fa-eye"></i></div>
        <h4>Full Transparency</h4>
        <p>Every contribution is tracked on a public ledger. Donors see exactly where money goes.</p>
      </div>
      <div class="obi-why-card">
        <div class="obi-why-icon"><i class="fas fa-globe-africa"></i></div>
        <h4>Across the Continent</h4>
        <p>Uganda, Kenya, Rwanda, Tanzania and growing. One platform, many networks.</p>
      </div>
      <div class="obi-why-card">
        <div class="obi-why-icon"><i class="fas fa-lock"></i></div>
        <h4>Bank-Grade Security</h4>
        <p>256-bit encryption, licensed payment partners, fraud monitoring on every transaction.</p>
      </div>
      <div class="obi-why-card">
        <div class="obi-why-icon"><i class="fas fa-headset"></i></div>
        <h4>Real Human Support</h4>
        <p>WhatsApp, email, phone — talk to a real person, not a bot, when you need help.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ TESTIMONIALS SLIDER ═══════════════ -->
<section class="section obi-testimonials" style="background:var(--gray-50);">
  <div class="container">
    <div class="section-header">
      <span class="section-eyebrow">Community Voices</span>
      <h2 class="section-title">Real People, Real Impact</h2>
    </div>
    <div class="obi-testi-track" id="testiTrack">
      <div class="obi-testi-card">
        <div class="obi-testi-stars">★★★★★</div>
        <p class="obi-testi-quote">"I raised UGX 4.2 million for my mother's surgery in just 9 days. The WhatsApp sharing made it so easy — people I hadn't spoken to in years contributed."</p>
        <div class="obi-testi-author">
          <div class="obi-testi-ava" style="background:#1a7a3c;">GN</div>
          <div><strong>Grace Nakato</strong><span>Kampala, Uganda</span></div>
        </div>
      </div>
      <div class="obi-testi-card">
        <div class="obi-testi-stars">★★★★★</div>
        <p class="obi-testi-quote">"We funded a complete classroom block for our village school. Transparent, fast — every parent could see every shilling donated."</p>
        <div class="obi-testi-author">
          <div class="obi-testi-ava" style="background:#145f2e;">JO</div>
          <div><strong>James Ochieng</strong><span>Kisumu, Kenya</span></div>
        </div>
      </div>
      <div class="obi-testi-card">
        <div class="obi-testi-stars">★★★★★</div>
        <p class="obi-testi-quote">"Within 48 hours of launching, we had 60 contributors. The real-time tracking kept donors engaged — it was amazing to watch the goal fill up."</p>
        <div class="obi-testi-author">
          <div class="obi-testi-ava" style="background:#d4a914;">AM</div>
          <div><strong>Amina Mwangi</strong><span>Nairobi, Kenya</span></div>
        </div>
      </div>
      <div class="obi-testi-card">
        <div class="obi-testi-stars">★★★★☆</div>
        <p class="obi-testi-quote">"As a donor I love knowing exactly which campaign I'm funding. The mobile money prompt is seamless — three taps and done."</p>
        <div class="obi-testi-author">
          <div class="obi-testi-ava" style="background:#1a7a3c;">PK</div>
          <div><strong>Patrick Kimani</strong><span>Kigali, Rwanda</span></div>
        </div>
      </div>
    </div>
    <div class="obi-testi-nav">
      <button class="obi-testi-btn" id="testPrev"><i class="fas fa-arrow-left"></i></button>
      <div class="obi-testi-dots" id="testiDots"></div>
      <button class="obi-testi-btn" id="testNext"><i class="fas fa-arrow-right"></i></button>
    </div>
  </div>
</section>

<!-- ═══════════════ CTA BAND ═══════════════ -->
<section class="obi-cta-band">
  <div class="container">
    <div class="obi-cta-inner">
      <div>
        <h2 class="obi-cta-title">Your cause deserves to be funded.</h2>
        <p class="obi-cta-sub">Launch free. Share instantly. Receive same day.</p>
      </div>
      <div class="obi-cta-btns">
        <a href="<?= BASE ?>/create-campaign.php" class="btn btn-yellow btn-lg">
          <i class="fas fa-rocket"></i> Start a Drive — It's Free
        </a>
        <a href="<?= BASE ?>/campaign-drives.php" class="btn btn-outline-white btn-lg">
          Browse Drives
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ FAQ ═══════════════ -->
<section class="section" id="faq" style="background:#fff;">
  <div class="container" style="max-width:780px;">
    <div class="section-header">
      <span class="section-eyebrow">FAQ</span>
      <h2 class="section-title">Got Questions?</h2>
      <p class="section-sub">We've got straight answers.</p>
    </div>
    <div>
      <div class="faq-item">
        <button class="faq-question">How is ObiFunds different from other platforms? <span class="faq-icon">+</span></button>
        <div class="faq-answer"><div class="faq-answer-inner">ObiFunds is built natively for African mobile money ecosystems — no bank account required. We support MTN, Airtel, Orange and more with same-day withdrawals, unlike platforms designed for Western card payments.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-question">How do I know contributions reach the right person? <span class="faq-icon">+</span></button>
        <div class="faq-answer"><div class="faq-answer-inner">Every contribution is logged on a real-time public ledger visible on the campaign page. Funds go directly to the drive creator's verified mobile money number — no intermediaries.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-question">What fees does ObiFunds charge? <span class="faq-icon">+</span></button>
        <div class="faq-answer"><div class="faq-answer-inner">Creating a drive is always free. We charge a 7.5% platform fee per contribution, deducted only at withdrawal. There are no hidden charges.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-question">How fast do I get my money? <span class="faq-icon">+</span></button>
        <div class="faq-answer"><div class="faq-answer-inner">Same day, during business hours (8am–6pm). Once your withdrawal request is approved, funds arrive on your mobile money wallet within minutes.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-question">Do donors need an ObiFunds account? <span class="faq-icon">+</span></button>
        <div class="faq-answer"><div class="faq-answer-inner">No. Anyone with a mobile phone and mobile money can donate — no account, no registration. Just a phone number and a PIN.</div></div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<style>
/* ═══════════════ HERO SLIDER ═══════════════ */
.obi-hero { position:relative; height:100vh; min-height:560px; max-height:800px; overflow:hidden; margin-top:68px; }
.obi-slides-wrap { position:relative; width:100%; height:100%; }
.obi-slide {
  position:absolute; inset:0;
  background-size:cover; background-position:center;
  opacity:0; transition:opacity .9s ease; z-index:0;
}
.obi-slide.active { opacity:1; z-index:1; }
.obi-slide-overlay { position:absolute; inset:0; background:linear-gradient(to right, rgba(10,40,20,.85) 0%, rgba(10,40,20,.55) 55%, rgba(10,40,20,.2) 100%); }
.obi-slide-inner { position:relative; z-index:2; height:100%; display:flex; flex-direction:column; justify-content:center; padding:0 0 60px; }
.obi-slide-cat { display:inline-block; background:var(--yellow); color:var(--gray-900); font-size:.72rem; font-weight:800; padding:4px 14px; border-radius:99px; text-transform:uppercase; letter-spacing:.08em; margin-bottom:18px; }
.obi-slide-title { font-size:clamp(1.6rem,5vw,3.2rem); font-weight:900; color:#fff; line-height:1.15; max-width:620px; margin-bottom:22px; letter-spacing:-.02em; text-shadow:0 2px 20px rgba(0,0,0,.3); }
.obi-slide-prog { margin-bottom:28px; }
.obi-slide-prog-bar { height:6px; background:rgba(255,255,255,.2); border-radius:99px; overflow:hidden; width:320px; max-width:100%; margin-bottom:8px; }
.obi-slide-prog-bar div { height:100%; background:var(--yellow); border-radius:99px; transition:width 1s ease; }
.obi-slide-prog span { font-size:.84rem; color:rgba(255,255,255,.8); font-weight:600; }
.obi-slide-btns { display:flex; flex-wrap:wrap; gap:12px; }

/* Slider controls */
.obi-slide-prev, .obi-slide-next {
  position:absolute; top:50%; transform:translateY(-50%);
  z-index:10; width:48px; height:48px; border-radius:50%;
  background:rgba(255,255,255,.15); backdrop-filter:blur(4px);
  color:#fff; border:1.5px solid rgba(255,255,255,.3);
  font-size:1rem; display:flex; align-items:center; justify-content:center;
  cursor:pointer; transition:all .2s;
}
.obi-slide-prev { left:24px; }
.obi-slide-next { right:24px; }
.obi-slide-prev:hover, .obi-slide-next:hover { background:rgba(255,255,255,.3); }

.obi-slide-dots { position:absolute; bottom:24px; left:50%; transform:translateX(-50%); z-index:10; display:flex; gap:8px; }
.obi-dot { width:10px; height:10px; border-radius:50%; background:rgba(255,255,255,.4); border:none; cursor:pointer; transition:all .2s; padding:0; }
.obi-dot.active { background:var(--yellow); width:28px; border-radius:5px; }

/* Hero floating chips */
.obi-hero-chips { position:absolute; bottom:30px; right:32px; z-index:10; display:flex; flex-direction:column; gap:8px; }
.obi-chip { display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.15); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,.2); border-radius:99px; padding:8px 16px; font-size:.8rem; font-weight:700; color:#fff; }
.obi-chip i { font-size:.82rem; color:var(--yellow); }
.obi-chip-yellow { background:var(--yellow); color:var(--gray-900); border-color:var(--yellow); }
.obi-chip-yellow i { color:var(--gray-900); }

/* ═══════════════ STATS BAR ═══════════════ */
.obi-stats-bar { background:var(--green); padding:32px 0; }
.obi-stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:0; }
.obi-stat { text-align:center; padding:0 16px; border-right:1px solid rgba(255,255,255,.15); }
.obi-stat:last-child { border-right:none; }
.obi-stat-val { display:block; font-size:2.2rem; font-weight:900; color:var(--yellow); letter-spacing:-.03em; line-height:1; margin-bottom:6px; }
.obi-stat-lbl { font-size:.78rem; color:rgba(255,255,255,.7); font-weight:600; }

/* ═══════════════ CAMPAIGN CARDS ═══════════════ */
.obi-campaign-card { position:relative; }
.obi-card-cat { position:absolute; top:10px; left:10px; background:var(--yellow); color:var(--gray-900); font-size:.65rem; font-weight:800; padding:3px 10px; border-radius:99px; text-transform:uppercase; letter-spacing:.05em; }
.obi-raised-row { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:4px; }
.obi-raised-amt { font-weight:800; color:var(--green-dark); font-size:.9rem; }
.obi-raised-pct { font-size:.78rem; font-weight:700; color:var(--green); }

/* ═══════════════ HOW IT WORKS ═══════════════ */
.obi-how { background:#fff; }
.obi-how-grid { display:grid; grid-template-columns:1fr auto 1fr auto 1fr; gap:0; align-items:start; margin-top:52px; }
.obi-how-card { background:var(--gray-50); border-radius:20px; padding:32px 28px; text-align:center; border:1px solid var(--gray-200); position:relative; }
.obi-how-num { position:absolute; top:-14px; left:50%; transform:translateX(-50%); background:var(--green); color:var(--yellow); font-weight:900; font-size:.78rem; padding:4px 12px; border-radius:99px; }
.obi-how-icon { width:64px; height:64px; border-radius:18px; background:linear-gradient(135deg,var(--green),var(--green-dark)); display:flex; align-items:center; justify-content:center; font-size:1.6rem; color:var(--yellow); margin:12px auto 18px; box-shadow:0 8px 24px rgba(26,122,60,.25); }
.obi-how-card h3 { font-weight:800; color:var(--green-dark); font-size:1rem; margin-bottom:10px; }
.obi-how-card p  { font-size:.88rem; color:var(--gray-500); line-height:1.7; margin-bottom:14px; }
.obi-how-tag { display:inline-block; background:var(--green-light); color:var(--green-dark); font-size:.72rem; font-weight:700; padding:4px 12px; border-radius:99px; }
.obi-how-tag-yellow { background:var(--yellow-light); color:#856404; }
.obi-how-connector { display:flex; align-items:center; justify-content:center; padding:0 12px; color:var(--green); font-size:1.2rem; margin-top:80px; }

/* ═══════════════ WHY US ═══════════════ */
.obi-why-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-top:12px; }
.obi-why-card { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); border-radius:16px; padding:28px 24px; transition:all .2s; }
.obi-why-card:hover { background:rgba(255,255,255,.1); transform:translateY(-3px); }
.obi-why-icon { width:48px; height:48px; border-radius:14px; background:rgba(245,197,24,.15); display:flex; align-items:center; justify-content:center; font-size:1.2rem; color:var(--yellow); margin-bottom:16px; }
.obi-why-card h4 { font-weight:800; color:#fff; font-size:.95rem; margin-bottom:8px; }
.obi-why-card p  { font-size:.86rem; color:rgba(255,255,255,.6); line-height:1.7; }
</style>

<style>
/* ═══════════════ TESTIMONIALS ═══════════════ */
.obi-testimonials { overflow:hidden; }
.obi-testi-track { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; transition:transform .4s ease; }
.obi-testi-card { background:#fff; border-radius:18px; padding:28px 24px; border:1px solid var(--gray-200); box-shadow:var(--shadow-sm); }
.obi-testi-stars { color:var(--yellow); font-size:1rem; letter-spacing:3px; margin-bottom:14px; }
.obi-testi-quote { font-size:.9rem; color:var(--gray-600); line-height:1.75; margin-bottom:20px; font-style:italic; }
.obi-testi-author { display:flex; align-items:center; gap:12px; }
.obi-testi-ava { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; color:#fff; font-size:.82rem; flex-shrink:0; }
.obi-testi-author strong { display:block; font-size:.88rem; font-weight:700; color:var(--green-dark); }
.obi-testi-author span   { font-size:.74rem; color:var(--gray-400); }
.obi-testi-nav { display:flex; justify-content:center; align-items:center; gap:16px; margin-top:28px; }
.obi-testi-btn { width:40px; height:40px; border-radius:50%; border:2px solid var(--gray-200); background:#fff; color:var(--green); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; font-size:.9rem; }
.obi-testi-btn:hover { background:var(--green); color:#fff; border-color:var(--green); }
.obi-testi-dots { display:flex; gap:8px; }
.obi-testi-dot { width:8px; height:8px; border-radius:50%; background:var(--gray-200); border:none; cursor:pointer; transition:all .2s; padding:0; }
.obi-testi-dot.active { background:var(--green); width:22px; border-radius:4px; }

/* ═══════════════ CTA BAND ═══════════════ */
.obi-cta-band { background:linear-gradient(135deg,var(--green) 0%,var(--green-dark) 100%); padding:72px 0; position:relative; overflow:hidden; }
.obi-cta-band::before { content:''; position:absolute; top:-40px; right:-40px; width:280px; height:280px; border-radius:50%; background:rgba(245,197,24,.1); }
.obi-cta-inner { display:flex; align-items:center; justify-content:space-between; gap:32px; flex-wrap:wrap; position:relative; z-index:2; }
.obi-cta-title { font-size:clamp(1.4rem,4vw,2rem); font-weight:900; color:#fff; letter-spacing:-.02em; margin-bottom:8px; }
.obi-cta-sub   { font-size:.95rem; color:rgba(255,255,255,.7); }
.obi-cta-btns  { display:flex; gap:14px; flex-wrap:wrap; flex-shrink:0; }

/* ═══════════════ ANIMATIONS ═══════════════ */
@keyframes obi-pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }

/* ═══════════════ RESPONSIVE ═══════════════ */
@media (max-width:1023px) {
  .obi-how-grid { grid-template-columns:1fr; }
  .obi-how-connector { display:none; }
  .obi-why-grid { grid-template-columns:repeat(2,1fr); }
  .obi-stats-grid { grid-template-columns:repeat(2,1fr); gap:1px; background:rgba(255,255,255,.15); }
  .obi-stat { background:var(--green); }
  .obi-testi-track { grid-template-columns:repeat(2,1fr); }
  .obi-hero-chips { display:none; }
}
@media (max-width:767px) {
  .obi-hero { height:92vmax; min-height:480px; max-height:680px; }
  .obi-slide-title { font-size:1.4rem; }
  .obi-slide-prog-bar { width:100%; }
  .obi-slide-prev, .obi-slide-next { width:38px; height:38px; font-size:.85rem; }
  .obi-slide-prev { left:12px; }
  .obi-slide-next { right:12px; }
  .obi-stats-grid { grid-template-columns:repeat(2,1fr); }
  .obi-stat { border-right:none; border-bottom:1px solid rgba(255,255,255,.15); padding:20px 16px; }
  .obi-stat:nth-child(odd) { border-right:1px solid rgba(255,255,255,.15); }
  .obi-stat-val { font-size:1.6rem; }
  .obi-why-grid { grid-template-columns:1fr; }
  .obi-testi-track { grid-template-columns:1fr; }
  .obi-cta-inner { flex-direction:column; text-align:center; }
  .obi-cta-btns { justify-content:center; }
}

/* ═══════════════ CATEGORY PILLS (fit-to-content) ═══════════════ */
.obi-cat-row {
  display:flex; flex-wrap:wrap; gap:8px;
  justify-content:flex-start; padding:0;
}
.obi-cat-badge {
  display:inline-block;
  background:var(--yellow);
  color:var(--gray-900);
  font-size:.68rem; font-weight:800;
  padding:4px 12px;
  border-radius:99px;
  text-transform:uppercase;
  letter-spacing:.05em;
  white-space:nowrap;
}

/* ═══════════════ CAMPAIGNS — mobile single card ═══════════════ */
.home-campaigns-grid {
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:20px;
}
/* hidden extra cards when listing mode */
.obi-card-hidden { display:none; }

/* ── More drives list ── */
.obi-more-list {
  border-top:1px solid var(--gray-200);
  padding-top:12px;
  margin-top:8px;
}
.obi-list-item {
  display:flex; align-items:center; justify-content:space-between;
  padding:10px 14px; border-radius:10px;
  border:1px solid var(--gray-200); background:#fff;
  margin-bottom:8px; gap:12px;
  transition:background .15s;
}
.obi-list-item:hover { background:var(--gray-50); }
.obi-list-left { display:flex; align-items:center; gap:10px; min-width:0; }
.obi-list-cat {
  flex-shrink:0;
  background:var(--yellow); color:var(--gray-900);
  font-size:.62rem; font-weight:800;
  padding:3px 9px; border-radius:99px;
  text-transform:uppercase; letter-spacing:.04em;
}
.obi-list-title {
  font-size:.86rem; font-weight:700; color:var(--green-dark);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  min-width:0;
}
.obi-list-right { display:flex; flex-direction:column; align-items:flex-end; flex-shrink:0; gap:2px; }
.obi-list-pct { font-size:.82rem; font-weight:800; color:var(--green); }
.obi-list-days { font-size:.72rem; color:var(--gray-400); white-space:nowrap; }

/* Responsive cards */
@media (max-width:1280px) {
  .home-campaigns-grid { grid-template-columns:repeat(3,1fr); }
}
@media (max-width:1023px) {
  .home-campaigns-grid { grid-template-columns:repeat(2,1fr); }
}
@media (max-width:600px) {
  /* Mobile: show ONE card at a time */
  .home-campaigns-grid {
    display:flex; flex-direction:column; gap:16px;
    padding:0 4px;
  }
  .home-campaigns-grid .obi-campaign-card { display:none; }
  .home-campaigns-grid .obi-campaign-card:first-child { display:flex; flex-direction:column; }
  /* After "show more" is clicked the cards reveal */
  .home-campaigns-grid.obi-show-all .obi-campaign-card { display:flex; flex-direction:column; }

  /* Section padding on mobile */
  .section { padding-left:16px; padding-right:16px; }
  .obi-cta-band { padding:48px 16px; }
  .obi-stats-bar { padding:20px 16px; }
  .obi-how-card { padding:24px 18px; }
  .obi-why-card { padding:20px 18px; }
  .container { padding-left:16px; padding-right:16px; }
}
</style>

<script>
// ── Hero Slider ───────────────────────────────────────────────
(function(){
  var slides = document.querySelectorAll('.obi-slide');
  var dotsWrap = document.getElementById('slideDots');
  var cur = 0, timer;

  slides.forEach(function(_,i){
    var d = document.createElement('button');
    d.className = 'obi-dot' + (i===0?' active':'');
    d.onclick = function(){ go(i); };
    dotsWrap.appendChild(d);
  });

  function go(n){
    slides[cur].classList.remove('active');
    dotsWrap.children[cur].classList.remove('active');
    cur = (n + slides.length) % slides.length;
    slides[cur].classList.add('active');
    dotsWrap.children[cur].classList.add('active');
    clearInterval(timer); start();
  }
  function start(){ timer = setInterval(function(){ go(cur+1); }, 6000); }
  document.getElementById('slideNext').onclick = function(){ go(cur+1); };
  document.getElementById('slidePrev').onclick = function(){ go(cur-1); };
  if(slides.length > 1) start();
})();

// ── Stat counters ─────────────────────────────────────────────
(function(){
  var observed = false;
  var stats = document.querySelectorAll('.obi-stat[data-target]');
  function countUp(){
    stats.forEach(function(el, i){
      var target = parseInt(el.dataset.target)||0;
      var suffix = el.dataset.suffix||'';
      var el2 = el.querySelector('.obi-stat-val');
      var start = 0, dur = 1800;
      var step = target / (dur/16);
      var iv = setInterval(function(){
        start = Math.min(start+step, target);
        el2.textContent = Math.floor(start).toLocaleString() + suffix;
        if(start >= target) clearInterval(iv);
      }, 16);
    });
  }
  var obs = new IntersectionObserver(function(entries){
    if(entries[0].isIntersecting && !observed){ observed=true; countUp(); }
  }, {threshold:.3});
  var bar = document.querySelector('.obi-stats-bar');
  if(bar) obs.observe(bar);
})();

// ── Testimonial slider ────────────────────────────────────────
(function(){
  var track = document.getElementById('testiTrack');
  var cards = track ? track.children : [];
  if(!cards.length) return;
  var dotsW = document.getElementById('testiDots');
  var perView = window.innerWidth >= 1024 ? 2 : 1;
  var cur = 0;
  var total = Math.ceil(cards.length / perView);

  for(var i=0;i<total;i++){
    var d = document.createElement('button');
    d.className='obi-testi-dot'+(i===0?' active':'');
    d.setAttribute('data-i',i);
    d.onclick=function(){ slide(parseInt(this.dataset.i)); };
    dotsW.appendChild(d);
  }
  function slide(n){
    cur = (n+total)%total;
    // move cards
    Array.from(cards).forEach(function(c,i){
      c.style.display = (i >= cur*perView && i < (cur+1)*perView) ? '' : (perView>=2 ? '' : 'none');
    });
    if(perView < 2){
      Array.from(cards).forEach(function(c,i){ c.style.display = i===cur?'':'none'; });
    }
    dotsW.querySelectorAll('.obi-testi-dot').forEach(function(d,i){ d.classList.toggle('active',i===cur); });
  }
  document.getElementById('testNext').onclick = function(){ slide(cur+1); };
  document.getElementById('testPrev').onclick = function(){ slide(cur-1); };
  if(perView < 2) slide(0);
})();

// ── Progress bars ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.progress-fill[data-width]').forEach(function(el){
    setTimeout(function(){ el.style.width = el.dataset.width + '%'; },400);
  });
});

// ── Show/hide extra drives list ───────────────────────────────
function toggleMoreDrives() {
  var list = document.getElementById('obiMoreList');
  var btn  = document.getElementById('obiShowMoreBtn');
  if (!list) return;
  var open = list.style.display !== 'none';
  list.style.display = open ? 'none' : 'block';
  btn.innerHTML = open
    ? btn.innerHTML.replace('fa-chevron-up','fa-chevron-down').replace('Hide','Show')
    : btn.innerHTML.replace('fa-chevron-down','fa-chevron-up').replace('Show','Hide');
}

// ── Mobile: show all cards when "See All" is clicked ─────────
(function(){
  if (window.innerWidth > 600) return;
  var grid = document.getElementById('homeCampaignsGrid');
  if (!grid) return;
  var seeAllBtn = grid.parentElement && grid.parentElement.nextElementSibling &&
                  grid.parentElement.nextElementSibling.querySelector('a');
  // Add a small "Show all drives" link below the first card
  var cards = grid.querySelectorAll('.obi-campaign-card');
  if (cards.length <= 1) return;
  var showBtn = document.createElement('button');
  showBtn.textContent = 'Show all ' + cards.length + ' drives';
  showBtn.style.cssText = 'width:100%;padding:12px;border:1.5px solid var(--green);border-radius:10px;background:#fff;color:var(--green);font-weight:700;font-size:.9rem;cursor:pointer;margin-top:4px;';
  showBtn.addEventListener('click', function(){
    grid.classList.add('obi-show-all');
    this.style.display = 'none';
  });
  grid.after(showBtn);
})();
</script>
