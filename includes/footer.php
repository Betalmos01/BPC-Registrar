  <footer class="app-footer">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-title"><?php echo e(APP_NAME); ?></div>
        <div class="footer-copy">© 2026 <?php echo e(APP_NAME); ?>. All rights reserved.</div>
      </div>
      <div class="footer-block">
        <div class="footer-label">Contact</div>
        <div class="footer-text">Address: 123 Campus Road, City, Province</div>
        <div class="footer-text">Phone: (02) 1234-5678</div>
        <div class="footer-text">Email: registrar@bestlink.edu.ph</div>
      </div>
      <div class="footer-block">
        <div class="footer-label">Legal &amp; Compliance</div>
        <a class="footer-link" href="<?php echo BASE_URL; ?>/privacy.php">Privacy Policy</a>
        <a class="footer-link" href="<?php echo BASE_URL; ?>/terms.php">Terms of Service</a>
        <a class="footer-link" href="<?php echo BASE_URL; ?>/accessibility.php">Accessibility Statement</a>
      </div>
      <div class="footer-block">
        <div class="footer-label">Explore</div>
        <a class="footer-link" href="<?php echo BASE_URL; ?>/about.php">About</a>
        <a class="footer-link" href="<?php echo BASE_URL; ?>/services.php">Services</a>
        <a class="footer-link" href="<?php echo BASE_URL; ?>/careers.php">Careers</a>
      </div>
    </div>
  </footer>
  </main>
</div>

<div class="modal" id="app-modal" aria-hidden="true">
  <div class="modal-backdrop" data-modal-close></div>
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-head">
      <div class="modal-title" id="modal-title"></div>
      <button class="modal-x" type="button" data-modal-close aria-label="Close"></button>
    </div>
    <div class="modal-body"></div>
    <div class="modal-foot">
      <button class="secondary btn-sm" type="button" data-modal-close>Cancel</button>
      <button class="primary btn-sm modal-submit" type="button">Save</button>
    </div>
  </div>
</div>
<script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>

