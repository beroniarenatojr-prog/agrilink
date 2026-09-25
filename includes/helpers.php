<?php
/**
 * Shared view helpers.
 */

/**
 * Build an app URL respecting subdirectory base path.
 */
function url(string $path = ''): string
{
    if ($path === '') {
        return APP_BASE ?: '/';
    }

    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }

    return APP_BASE . $path;
}

/**
 * Build a static asset URL with a version stamp so browsers/CDN refetch it after it changes.
 */
function asset(string $path): string
{
    $file = dirname(__DIR__) . '/' . ltrim($path, '/');
    $ver  = is_file($file) ? filemtime($file) : null;

    return url($path) . ($ver ? '?v=' . $ver : '');
}

/**
 * Redirect and exit.
 */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function statusBadge(string $status): string
{
    $label = ucfirst(str_replace('_', ' ', $status));
    return '<span class="badge-status badge-status-' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '">' . $label . '</span>';
}

function paymentBadge(string $status): string
{
    $label = ucfirst($status);
    return '<span class="badge-status badge-payment-' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '">' . $label . '</span>';
}

function peso(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

function countLabel(int $count, string $singular, ?string $plural = null): string
{
    $plural ??= $singular . 's';
    return $count . ' ' . ($count === 1 ? $singular : $plural);
}

function listPage(): int
{
    return max(1, (int) ($_GET['page'] ?? 1));
}

function paginationMeta(int $total, ?int $page = null, ?int $perPage = null): array
{
    $perPage    = $perPage ?? LIST_PER_PAGE;
    $page       = $page ?? listPage();
    $totalPages = max(1, (int) ceil($total / max(1, $perPage)));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    return [
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
        'offset'      => $offset,
        'from'        => $total === 0 ? 0 : $offset + 1,
        'to'          => min($total, $offset + $perPage),
        'has_prev'    => $page > 1,
        'has_next'    => $page < $totalPages,
    ];
}

function paginationUrl(int $page): string
{
    $params = $_GET;
    if ($page <= 1) {
        unset($params['page']);
    } else {
        $params['page'] = $page;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
    if (defined('APP_BASE') && APP_BASE !== '' && str_starts_with($path, APP_BASE)) {
        $path = substr($path, strlen(APP_BASE)) ?: '/';
    }

    $qs = http_build_query($params);
    return url($path) . ($qs !== '' ? '?' . $qs : '');
}

function renderPaginationNav(array $meta): string
{
    if ($meta['total_pages'] <= 1) {
        return '';
    }

    $html = '<nav class="agri-pagination" aria-label="Pagination">';

    if ($meta['has_prev']) {
        $html .= '<a class="agri-pagination__btn" href="' . htmlspecialchars(paginationUrl($meta['page'] - 1), ENT_QUOTES, 'UTF-8') . '">Prev</a>';
    } else {
        $html .= '<span class="agri-pagination__btn is-disabled">Prev</span>';
    }

    $start = max(1, $meta['page'] - 2);
    $end   = min($meta['total_pages'], $meta['page'] + 2);

    if ($start > 1) {
        $html .= '<a class="agri-pagination__page" href="' . htmlspecialchars(paginationUrl(1), ENT_QUOTES, 'UTF-8') . '">1</a>';
        if ($start > 2) {
            $html .= '<span class="agri-pagination__ellipsis">…</span>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $meta['page']) {
            $html .= '<span class="agri-pagination__page is-active" aria-current="page">' . $i . '</span>';
        } else {
            $html .= '<a class="agri-pagination__page" href="' . htmlspecialchars(paginationUrl($i), ENT_QUOTES, 'UTF-8') . '">' . $i . '</a>';
        }
    }

    if ($end < $meta['total_pages']) {
        if ($end < $meta['total_pages'] - 1) {
            $html .= '<span class="agri-pagination__ellipsis">…</span>';
        }
        $html .= '<a class="agri-pagination__page" href="' . htmlspecialchars(paginationUrl($meta['total_pages']), ENT_QUOTES, 'UTF-8') . '">' . $meta['total_pages'] . '</a>';
    }

    if ($meta['has_next']) {
        $html .= '<a class="agri-pagination__btn" href="' . htmlspecialchars(paginationUrl($meta['page'] + 1), ENT_QUOTES, 'UTF-8') . '">Next</a>';
    } else {
        $html .= '<span class="agri-pagination__btn is-disabled">Next</span>';
    }

    $html .= '</nav>';
    return $html;
}

function renderListFooter(array $meta, string $singular, ?string $plural = null): string
{
    if ($meta['total'] === 0) {
        return '';
    }

    $plural ??= $singular . 's';
    $word   = $meta['total'] === 1 ? $singular : $plural;
    $range  = $meta['from'] === $meta['to']
        ? (string) $meta['from']
        : $meta['from'] . '–' . $meta['to'];
    $summary = 'Showing ' . $range . ' of ' . $meta['total'] . ' ' . $word;
    $nav     = renderPaginationNav($meta);

    return '<div class="table-card-footer' . ($nav !== '' ? ' table-card-footer--split' : '') . '">'
        . '<span class="table-card-footer__summary">' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</span>'
        . $nav
        . '</div>';
}

function renderListPaginationBar(array $meta, string $singular, ?string $plural = null): string
{
    if ($meta['total'] === 0) {
        return '';
    }

    $plural ??= $singular . 's';
    $word   = $meta['total'] === 1 ? $singular : $plural;
    $range  = $meta['from'] === $meta['to']
        ? (string) $meta['from']
        : $meta['from'] . '–' . $meta['to'];
    $summary = 'Showing ' . $range . ' of ' . $meta['total'] . ' ' . $word;
    $nav     = renderPaginationNav($meta);

    return '<div class="agri-list-pagination' . ($nav !== '' ? ' agri-list-pagination--split' : '') . '">'
        . '<span class="agri-list-pagination__summary">' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</span>'
        . $nav
        . '</div>';
}

function userInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function timeGreeting(): string
{
    $hour = (int) date('G');
    if ($hour < 12) {
        return 'Good morning';
    }
    if ($hour < 17) {
        return 'Good afternoon';
    }
    return 'Good evening';
}

function uploadImage(array $file, string $prefix = ''): ?string
{
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedMimes, true)) {
        return null;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }

    $ext      = match($mime) {
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };

    $filename = ($prefix ? $prefix . '_' : '') . bin2hex(random_bytes(8)) . '.' . $ext;
    $dir      = __DIR__ . '/../uploads/products/';
    $dest     = $dir . $filename;

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    return 'products/' . $filename;
}

function deleteUploadedFile(?string $path): void
{
    if (!$path) return;
    $full = __DIR__ . '/../uploads/' . $path;
    if (is_file($full)) {
        unlink($full);
    }
}

/**
 * Read structured address fields from POST (optional name prefix, e.g. delivery_).
 */
function addressFieldsFromPost(string $prefix = ''): array
{
    return [
        'province' => trim((string) ($_POST[$prefix . 'province'] ?? '')),
        'city'     => trim((string) ($_POST[$prefix . 'city'] ?? '')),
        'barangay' => trim((string) ($_POST[$prefix . 'barangay'] ?? '')),
        'street'   => trim((string) ($_POST[$prefix . 'street'] ?? '')),
    ];
}

/**
 * Build a single-line Philippine address for display and geocoding.
 */
function formatPhilippineAddress(array $fields): string
{
    $parts = [];
    foreach (['street', 'barangay', 'city', 'province'] as $key) {
        $value = trim((string) ($fields[$key] ?? ''));
        if ($value !== '') {
            $parts[] = $value;
        }
    }
    $parts[] = 'Philippines';

    return implode(', ', $parts);
}

/**
 * Validate phone + required address fields. Returns field => message errors.
 */
function validatePhoneAndAddress(string $phone, array $addressFields, string $phoneKey = 'phone'): array
{
    $errors = [];

    $phone = trim($phone);
    if ($phone === '') {
        $errors[$phoneKey] = 'Phone number is required.';
    } else {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            $errors[$phoneKey] = 'Enter a valid phone number (10–15 digits).';
        }
    }

    if (trim($addressFields['province'] ?? '') === '') {
        $errors['province'] = 'Province is required.';
    }
    if (trim($addressFields['city'] ?? '') === '') {
        $errors['city'] = 'City / municipality is required.';
    }
    if (trim($addressFields['barangay'] ?? '') === '') {
        $errors['barangay'] = 'Barangay is required.';
    }
    if (trim($addressFields['street'] ?? '') === '') {
        $errors['street'] = 'Street / purok is required.';
    }

    return $errors;
}

/**
 * Map a users/orders row to structured address field values.
 */
function addressFieldsFromRow(array $row, string $prefix = 'address'): array
{
    if ($prefix === 'address') {
        return [
            'province' => trim((string) ($row['address_province'] ?? '')),
            'city'     => trim((string) ($row['address_city'] ?? '')),
            'barangay' => trim((string) ($row['address_barangay'] ?? '')),
            'street'   => trim((string) ($row['address_street'] ?? '')),
        ];
    }

    return [
        'province' => trim((string) ($row['delivery_province'] ?? '')),
        'city'     => trim((string) ($row['delivery_city'] ?? '')),
        'barangay' => trim((string) ($row['delivery_barangay'] ?? '')),
        'street'   => trim((string) ($row['delivery_street'] ?? '')),
    ];
}

