<?php
// wizard/includes/csrf.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Genera/recupera un CSRF token.
 */
function generate_csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF.
 */
function validate_csrf(?string $token): bool
{
    return !empty($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
