<?php
/**
 * Secure image serving.
 * Usage: /media.php?path=products/abc123.jpg
 *
 * - Blocks directory traversal (../ and backslashes)
 * - Serves only files inside UPLOAD_DIR
 * - Forces correct Content-Type headers
 */

require_once __DIR__ . '/config/app.php';

$path = $_GET['path'] ?? '';

// Strip any dangerous sequences
$path = str_replace(['\\', "\0"], '/', $path);
$path = preg_replace('/\.{2,}/', '', $path); // remove ..
$path = ltrim($path, '/');

if (empty($path)) {
    http_response_code(400);
    exit('Missing path.');
}

$fullPath = UPLOAD_DIR . '/' . $path;
$realBase = realpath(UPLOAD_DIR);

if ($realBase === false) {
    http_response_code(500);
    exit('Storage directory not found.');
}

$realFile = realpath($fullPath);

// Missing file (e.g. lost upload): serve a neutral placeholder instead of a broken image icon.
if ($realFile === false || !is_file($realFile)) {
    header('Content-Type: image/svg+xml');
    header('Cache-Control: no-store');
    exit('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f1f5f9"/><stop offset="1" stop-color="#e2e8f0"/></linearGradient></defs>'
        . '<rect width="400" height="300" fill="url(#g)"/>'
        . '<g fill="none" stroke="#94a3b8" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" transform="translate(170 104)">'
        . '<rect x="0" y="0" width="60" height="48" rx="8"/><circle cx="18" cy="16" r="5"/><path d="M0 40l18-16 14 12 10-8 18 14"/></g>'
        . '<text x="200" y="192" text-anchor="middle" font-family="Outfit, Arial, sans-serif" font-size="18" font-weight="600" fill="#94a3b8">No photo</text>'
        . '</svg>');
}

if (!str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
    http_response_code(403);
    exit('Access denied.');
}

$ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));

$mimeMap = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'gif'  => 'image/gif',
];

if (!array_key_exists($ext, $mimeMap)) {
    http_response_code(403);
    exit('File type not allowed.');
}

$mime = $mimeMap[$ext];
$size = filesize($realFile);
$mtime = filemtime($realFile);
$etag = '"' . md5($realFile . $mtime . $size) . '"';

// Cache headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=86400');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

// Handle conditional requests
if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime)
) {
    http_response_code(304);
    exit;
}

readfile($realFile);
