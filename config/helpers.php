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
 * Require admin login or redirect.
 */
function require_admin(): void
{
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: ../auth/login.php");
        exit();
    }
}

/**
 * Escape output for safe HTML rendering.
 */
function h(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
