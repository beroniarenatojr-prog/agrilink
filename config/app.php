<?php
/**
 * Application base path for subdirectory deployments (e.g. /agrishieldv2).
 * Auto-detected from document root vs project root.
 * Override by defining APP_BASE before including this file.
 */

if (!defined('APP_BASE')) {
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $projectRoot  = realpath(dirname(__DIR__)) ?: '';

    $base = '';
    if ($documentRoot && $projectRoot) {
        $doc = str_replace('\\', '/', $documentRoot);
        $app = str_replace('\\', '/', $projectRoot);
        if (str_starts_with($app, $doc)) {
            $base = substr($app, strlen($doc));
        }
    }

    define('APP_BASE', rtrim($base, '/') ?: '');
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'AgriLink');
}

if (!defined('GEOCODER_USER_AGENT')) {
    define('GEOCODER_USER_AGENT', 'AgriLink/1.0 (agrishieldv2; contact=admin@agrilink.com)');
}

if (!defined('TRACKING_POLL_MS')) {
    define('TRACKING_POLL_MS', 5000);
}

if (!defined('TRACKING_UPDATE_MS')) {
    define('TRACKING_UPDATE_MS', 10000);
}

if (!defined('TRACKING_STALE_SECONDS')) {
    define('TRACKING_STALE_SECONDS', 120);
}

if (!defined('LIST_PER_PAGE')) {
    define('LIST_PER_PAGE', 15);
}
