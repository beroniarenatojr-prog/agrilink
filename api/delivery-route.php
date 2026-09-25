<?php
/**
 * Server-side OSRM proxy for delivery road routing (avoids browser CORS / blocks).
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$fromLat = isset($_GET['from_lat']) ? (float) $_GET['from_lat'] : 0.0;
$fromLng = isset($_GET['from_lng']) ? (float) $_GET['from_lng'] : 0.0;
$toLat   = isset($_GET['to_lat']) ? (float) $_GET['to_lat'] : 0.0;
$toLng   = isset($_GET['to_lng']) ? (float) $_GET['to_lng'] : 0.0;

if ($fromLat === 0.0 && $fromLng === 0.0 || $toLat === 0.0 && $toLng === 0.0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

$osrmUrl = sprintf(
    'https://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s?overview=full&geometries=geojson',
    rawurlencode((string) $fromLng),
    rawurlencode((string) $fromLat),
    rawurlencode((string) $toLng),
    rawurlencode((string) $toLat)
);

$context = stream_context_create([
    'http' => [
        'timeout' => 25,
        'header'  => "Accept: application/json\r\nUser-Agent: AgriLink/1.0\r\n",
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
]);

$raw = @file_get_contents($osrmUrl, false, $context);

if ($raw === false && function_exists('curl_init')) {
    $ch = curl_init($osrmUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'User-Agent: AgriLink/1.0'],
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $raw = null;
    }
    curl_close($ch);
}

if ($raw === false || $raw === null) {
    http_response_code(502);
    echo json_encode(['error' => 'Routing service unavailable']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data) || empty($data['routes'][0]['geometry']['coordinates'])) {
    http_response_code(404);
    echo json_encode(['error' => 'No route found', 'osrm' => $data['code'] ?? null]);
    exit;
}

$route = $data['routes'][0];
$coordinates = [];

foreach ($route['geometry']['coordinates'] as $pair) {
    $coordinates[] = [(float) $pair[1], (float) $pair[0]];
}

/** Downsample very long routes so the browser can render road geometry reliably. */
$maxPoints = 600;
$total = count($coordinates);
if ($total > $maxPoints) {
    $sampled = [];
    $step = ($total - 1) / ($maxPoints - 1);
    for ($i = 0; $i < $maxPoints; $i++) {
        $sampled[] = $coordinates[(int) round($i * $step)];
    }
    $coordinates = $sampled;
}

echo json_encode([
    'coordinates' => $coordinates,
    'distance'    => (float) ($route['distance'] ?? 0),
    'duration'    => (float) ($route['duration'] ?? 0),
], JSON_UNESCAPED_UNICODE);
exit;
