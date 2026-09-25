<?php
/**
 * Leaflet map assets for live delivery tracking pages.
 * Set $loadTrackingMap = true before including sidebar/header.
 */
function tracking_map_head(): string
{
    return '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">' . "\n"
        . '<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>' . "\n"
        . '<script>window.__AGRI_ROUTE_API__=' . json_encode(url('/api/delivery-route.php'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';</script>' . "\n"
        . '<script src="' . htmlspecialchars(url('/assets/js/tracking-map.js'), ENT_QUOTES, 'UTF-8') . '"></script>';
}

function tracking_map_scripts(): string
{
    return '';
}
