<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vex-js@4.1.0/dist/js/vex.combined.min.js"></script>
<?php if (isset($_SESSION['flash'])): ?>
<script>
window.__AGRI_FLASH__ = <?= json_encode($_SESSION['flash'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php unset($_SESSION['flash']); endif; ?>
<script src="<?= url('/assets/js/agrilink.js') ?>"></script>
