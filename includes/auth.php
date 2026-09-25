<?php
/**
 * Auth helpers — session start + role middleware.
 * Include this at the top of every protected page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';

/**
 * Return the currently logged-in user array or null.
 */
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Return the current user's role or null.
 */
function currentRole(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

/**
 * True if the visitor is logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

/**
 * Require one or more roles; redirect to login if not met.
 * Usage: requireRole('farmer');
 *        requireRole('farmer', 'admin');
 */
function requireRole(string ...$roles): void
{
    if (!isLoggedIn()) {
        redirect('/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
    if (!in_array(currentRole(), $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

/**
 * Require the visitor NOT to be logged in (for login/register pages).
 */
function requireGuest(): void
{
    if (isLoggedIn()) {
        $role = currentRole();
        if ($role === 'farmer') {
            redirect('/farmer/dashboard.php');
        } elseif ($role === 'admin') {
            redirect('/admin/dashboard.php');
        } elseif ($role === 'logistics') {
            redirect('/logistics/dashboard.php');
        } else {
            redirect('/marketplace/index.php');
        }
    }
}


/**
 * Redirect to the role's default landing page after login.
 */
function redirectAfterLogin(?string $next = null): void
{
    if ($next && (str_starts_with($next, '/') || str_starts_with($next, APP_BASE))) {
        header('Location: ' . $next);
        exit;
    }

    $role = currentRole();
    if ($role === 'farmer') {
        redirect('/farmer/dashboard.php');
    } elseif ($role === 'admin') {
        redirect('/admin/dashboard.php');
    } elseif ($role === 'logistics') {
        redirect('/logistics/dashboard.php');
    } else {
        redirect('/marketplace/index.php');
    }
}
