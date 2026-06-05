<?php

/**
 * Start the session with hardened cookie settings.
 * Call this at the top of every page that uses $_SESSION.
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly'  => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}

/**
 * Regenerate the session ID (call on login to prevent session fixation).
 */
function regenerate_session(): void
{
    session_regenerate_id(true);
}

/**
 * Generate or retrieve the current CSRF token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field for forms.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validate the submitted CSRF token. Exits with 403 on failure.
 */
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

/**
 * Require that the user is logged in. Optionally restrict to specific roles.
 *
 * @param string|array|null $roles  Allowed role(s). null = any logged-in user.
 *                                  Pass a string for one role, or an array for multiple.
 */
function require_login(string|array|null $roles = null): void
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    if ($roles !== null) {
        $allowed = is_array($roles) ? $roles : [$roles];
        if (!in_array($_SESSION['user_role'], $allowed, true)) {
            http_response_code(403);
            exit('Access denied. You do not have permission to view this page.');
        }
    }
}

/**
 * Legacy compatibility: require admin login.
 * Now delegates to the role-based system.
 */
function require_admin(): void
{
    require_login('admin');
}

/**
 * Get the currently logged-in user's role.
 */
function current_role(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get the currently logged-in user's display name.
 */
function current_user_name(): ?string
{
    return $_SESSION['user_fullname'] ?? null;
}

/**
 * Get the currently logged-in user's ID.
 */
function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Check if the current user has one of the given roles.
 */
function has_role(string|array $roles): bool
{
    $allowed = is_array($roles) ? $roles : [$roles];
    return in_array($_SESSION['user_role'] ?? '', $allowed, true);
}

/**
 * Escape output for safe HTML rendering.
 */
function h(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
