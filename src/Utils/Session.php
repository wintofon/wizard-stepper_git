<?php
/**
 * Session utilities for secure session handling and CSRF protection.
 */

declare(strict_types=1);

if (!function_exists('startSecureSession')) {
    /**
     * Starts PHP session with secure cookie parameters if not active.
     */
    function startSecureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
            if (function_exists('dbg')) {
                dbg('🔒 Sesión iniciada de forma segura');
            }
        }
    }
}

if (!function_exists('generateCsrfToken')) {
    /**
     * Generates (if needed) and returns the CSRF token stored in session.
     */
    function generateCsrfToken(): string
    {
        startSecureSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCsrfToken')) {
    /**
     * Validates a provided CSRF token against the one in session.
     */
    function validateCsrfToken(?string $token): bool
    {
        startSecureSession();
        if (empty($_SESSION['csrf_token']) || !is_string($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('sendSecurityHeaders')) {
    /**
     * Sends a common set of security headers.
     *
     * @param string $contentType      MIME type for the response.
     * @param int    $hstsMaxAge       Max-age for the Strict-Transport-Security header.
     * @param bool   $xssProtection    Whether to send the X-XSS-Protection header.
     *
     * @return void
     */
    function sendSecurityHeaders(string $contentType = 'text/html; charset=UTF-8', int $hstsMaxAge = 31536000, bool $xssProtection = false): void
    {
        header('Content-Type: ' . $contentType);
        header('Strict-Transport-Security: max-age=' . $hstsMaxAge . '; includeSubDomains; preload');
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        if ($xssProtection) {
            header('X-XSS-Protection: 1; mode=block');
        }
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), microphone=()');
    }
}
