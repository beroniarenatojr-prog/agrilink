    </div><!-- /.container -->
</main>

<footer class="agri-footer mt-auto">
    <div class="container">
        <span>&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> &mdash; Connecting Filipino Farmers to Markets</span>
    </div>
</footer>

<?php require __DIR__ . '/scripts.php'; ?>
<?php if (isset($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>
</body>
</html>
