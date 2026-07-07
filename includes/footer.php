<?php
// ObiFunds – includes/footer.php
?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
          <div style="width:40px;height:40px;background:linear-gradient(135deg,var(--green),var(--green-dark));border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--yellow);font-weight:900;font-size:.85rem;">OB</div>
          <span style="color:var(--white);font-weight:900;font-size:1.1rem;letter-spacing:-.01em;">ObiFunds</span>
        </div>
        <p>Empowering communities to raise money for what truly matters — fast, transparent, and built for Africa.</p>
        <div class="footer-socials" style="margin-top:16px;">
          <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
          <a href="#" aria-label="Twitter/X"><i class="fab fa-x-twitter"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Platform</h4>
        <ul>
          <li><a href="<?= BASE ?>/campaign-drives.php">Browse Drives</a></li>
          <li><a href="<?= BASE ?>/donate.php">Give Now</a></li>
          <li><a href="<?= BASE ?>/create-campaign.php">Start a Drive</a></li>
          <li><a href="<?= BASE ?>/index.php#how-it-works">How It Works</a></li>
          <li><a href="<?= BASE ?>/index.php#faq">FAQ</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Company</h4>
        <ul>
          <li><a href="#">About ObiFunds</a></li>
          <li><a href="#">Our Story</a></li>
          <li><a href="#">Contact</a></li>
          <li><a href="#">Careers</a></li>
          <li><a href="#">Press</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Legal</h4>
        <ul>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Use</a></li>
          <li><a href="#">Cookie Policy</a></li>
          <li><a href="#">Refund Policy</a></li>
        </ul>
        <div style="margin-top:20px;background:rgba(255,255,255,.06);border-radius:10px;padding:12px 14px;">
          <p style="font-size:.72rem;color:rgba(255,255,255,.5);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;">Powered by</p>
          <p style="font-size:.82rem;color:var(--yellow);font-weight:700;">ioTec Pay · MTN · Airtel</p>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> ObiFunds. All rights reserved.</span>
      <div class="footer-legal">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Cookies</a>
      </div>
    </div>
  </div>
</footer>
<script src="<?= BASE ?>/js/main.js"></script>
<?php if (!empty($extraJs)) echo $extraJs; ?>
</body>
</html>
