<?php
// General security helpers

declare(strict_types=1);

/**
 * Determine if debug mode is enabled via GET ?debug=1 or APP_DEBUG env.
 */
function is_debug_mode(): bool
{
    static $debug = null;
    if ($debug === null) {
        $debug = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN) || getenv('APP_DEBUG') === '1';
    }
    return $debug;
}

/**
 * Abort with 403 unless debug mode is enabled.
 */
function require_debug_mode(): void
{
    if (!is_debug_mode()) {
        http_response_code(403);
        exit('Forbidden');
    }
}

/**
 * Get or create the CSRF token for current session.
 */
function generate_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a provided CSRF token.
 */
function validate_csrf_token(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escape a string for safe HTML output.
 *
 * @param string|null $value Raw value to escape
 * @return string Escaped value
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
