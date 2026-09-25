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

if ($realFile === false || !str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
    http_response_code(403);
    exit('Access denied.');
}

if (!is_file($realFile)) {
    http_response_code(404);
    exit('File not found.');
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
