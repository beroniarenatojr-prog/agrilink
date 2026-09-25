<?php
/**
 * One-time script to replace root-absolute paths with url() helper.
 * Run: php scripts/fix_base_paths.php
 */

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$skipDirs = ['scripts', 'database', 'uploads', '.git', '.cursor'];

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    foreach ($skipDirs as $skip) {
        if (str_starts_with($relative, $skip . '/')) {
            continue 2;
        }
    }

    $content = file_get_contents($file->getPathname());
    $original = $content;

    // Skip if already mostly converted
    if (str_contains($content, "url('/") && !str_contains($content, 'href="/')) {
        continue;
    }

    // header('Location: /path')
    $content = preg_replace(
        "/header\\('Location: (\\/[^']*)'\\)/",
        "redirect('$1')",
        $content
    );

    // Simple href="/path" (no embedded PHP)
    $content = preg_replace_callback(
        '/href="(\/[^"?][^"]*)"/',
        static function (array $m): string {
            if (str_contains($m[1], '<?=')) {
                return $m[0];
            }
            return 'href="<?= url(\'' . $m[1] . '\') ?>"';
        },
        $content
    );

    // action="/path"
    $content = preg_replace_callback(
        '/action="(\/[^"]*)"/',
        static function (array $m): string {
            if (str_contains($m[1], '<?=')) {
                return $m[0];
            }
            return 'action="<?= url(\'' . $m[1] . '\') ?>"';
        },
        $content
    );

    // link href="/assets/..."
    $content = preg_replace(
        '/href="(\/assets\/[^"]*)"/',
        'href="<?= url(\'$1\') ?>"',
        $content
    );

    // src="/media.php?path=<?= urlencode(...)
    $content = preg_replace(
        '/src="\/media\.php\?path=<\?= urlencode\(([^)]+)\) \?>"/',
        'src="<?= url(\'/media.php?path=\' . urlencode($1)) ?>"',
        $content
    );

    // onclick switchImage('/media.php?path=...
    $content = preg_replace(
        "/switchImage\\('\/media\\.php\\?path=<\?= urlencode\\(([^)]+)\\) \\}',/",
        "switchImage('<?= url('/media.php?path=' . urlencode($1)) ?>',",
        $content
    );

    if ($content !== $original) {
        file_put_contents($file->getPathname(), $content);
        echo "Updated: $relative\n";
    }
}

echo "Done.\n";
