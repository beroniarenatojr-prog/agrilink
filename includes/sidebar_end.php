    </main><!-- /.agri-content -->
</div><!-- /.agri-main -->

<?php require __DIR__ . '/scripts.php'; ?>
<?php if (isset($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>
<?php if (function_exists('isLoggedIn') && isLoggedIn() && currentRole() === 'logistics'): ?>
<script src="<?= url('/assets/js/logistics-tracker.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof AgriLogisticsTracker === 'undefined') return;
    AgriLogisticsTracker.start({
        activeUrl: <?= json_encode(url('/logistics/tracking/active.php')) ?>,
        updateUrl: <?= json_encode(url('/logistics/tracking/update.php')) ?>,
        csrfToken: <?= json_encode(csrf_token()) ?>,
        orderViewBase: <?= json_encode(url('/logistics/orders/view.php?id=')) ?>,
        updateIntervalMs: <?= (int) TRACKING_UPDATE_MS ?>
    });
});
</script>
<?php endif; ?>
<script>
// Mobile sidebar toggle
(function() {
    const toggle   = document.getElementById('menuToggle');
    const sidebar  = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    function openSidebar() {
        sidebar.classList.add('show');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (toggle) toggle.addEventListener('click', openSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
})();
</script>
</body>
</html>
