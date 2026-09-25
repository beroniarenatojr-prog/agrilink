<?php

class Geocoder
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    private const TIMEOUT_SEC   = 12;

    /**
     * Geocode structured Philippine address fields (tries simpler queries if the full string fails).
     */
    public static function geocodeFromFields(array $fields, ?string $fallbackAddress = null): ?array
    {
        $fields = [
            'province' => trim((string) ($fields['province'] ?? '')),
            'city'     => trim((string) ($fields['city'] ?? '')),
            'barangay' => trim((string) ($fields['barangay'] ?? '')),
            'street'   => trim((string) ($fields['street'] ?? '')),
        ];

        $attempts = [];

        if ($fields['street'] !== '' && $fields['barangay'] !== '' && $fields['city'] !== '') {
            $attempts[] = self::joinParts([$fields['street'], $fields['barangay'], $fields['city'], $fields['province'], 'Philippines']);
        }
        if ($fields['barangay'] !== '' && $fields['city'] !== '') {
            $attempts[] = self::joinParts([$fields['barangay'], $fields['city'], $fields['province'], 'Philippines']);
            $attempts[] = self::joinParts(['Barangay ' . $fields['barangay'], $fields['city'], $fields['province'], 'Philippines']);
        }
        if ($fields['city'] !== '' && $fields['province'] !== '') {
            $attempts[] = self::joinParts([$fields['city'], $fields['province'], 'Philippines']);
        }
        if ($fields['city'] !== '') {
            $attempts[] = self::joinParts([$fields['city'], 'Philippines']);
        }

        if ($fallbackAddress !== null && trim($fallbackAddress) !== '') {
            $attempts[] = trim($fallbackAddress);
        }

        foreach (array_unique(array_filter($attempts)) as $query) {
            $result = self::geocode($query);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Geocode a free-text address via Nominatim (OpenStreetMap).
     */
    public static function geocode(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $query = http_build_query([
            'q'            => $address,
            'format'       => 'json',
            'limit'        => 1,
            'countrycodes' => 'ph',
        ]);

        $url  = self::NOMINATIM_URL . '?' . $query;
        $body = self::httpGet($url);

        if ($body === null) {
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lng' => (float) $data[0]['lon'],
        ];
    }

    private static function joinParts(array $parts): string
    {
        $clean = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return implode(', ', $clean);
    }

    private static function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT_SEC,
                CURLOPT_HTTPHEADER     => [
                    'User-Agent: ' . GEOCODER_USER_AGENT,
                    'Accept: application/json',
                ],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body !== false && $code >= 200 && $code < 300) {
                return $body;
            }
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => 'User-Agent: ' . GEOCODER_USER_AGENT . "\r\nAccept: application/json\r\n",
                'timeout' => self::TIMEOUT_SEC,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        return $body !== false ? $body : null;
    }
}
