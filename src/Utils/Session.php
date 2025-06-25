<?php
/**
 * File: utils/Session.php
 * --------------------------------------------------------------------------
 * Utilidades de sesión y cabeceras seguras para el CNC Wizard Stepper
 * --------------------------------------------------------------------------
 * - Siempre arranca la sesión con cookies SameSite=Strict y secure/httponly.
 * - Detecta entorno local (http://localhost) para no forzar "secure" si no es HTTPS.
 * - Genera, valida y renueva token CSRF.
 * - Envía cabeceras de seguridad (CSP opcional) verificando que no se hayan enviado.
 *
 * 2025-06-23 – Refactor completo, PHP 8.3-ready.
 */

declare(strict_types=1);

/* -------------------------------------------------------------------------- */
/*  🔒 Helper: detectar si la conexión es HTTPS                                */
/* -------------------------------------------------------------------------- */
if (!function_exists('isHttps')) {
    function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }
}

/* -------------------------------------------------------------------------- */
/*  1) startSecureSession                                                     */
/* -------------------------------------------------------------------------- */
if (!function_exists('startSecureSession')) {
    /**
     * Inicia la sesión con parámetros de cookie seguros. Idempotente.
     */
    function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return; // Ya iniciada
        }

        // BASE_URL puede no estar definido en CLI; usamos "/" como fallback
        $basePath = defined('BASE_URL') ? BASE_URL : rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $basePath . '/',
            'domain'   => '',
            'secure'   => isHttps(),           // evita romper en localhost sin HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        session_start();
        session_regenerate_id();               // mitigate fixation

        if (function_exists('dbg')) {
            dbg('🔒 Sesión segura iniciada');
        }
    }
}

/* -------------------------------------------------------------------------- */
/*  2) CSRF helpers                                                           */
/* -------------------------------------------------------------------------- */
if (!function_exists('generateCsrfToken')) {
    /**
     * Devuelve el token CSRF actual o lo genera si no existe.
     */
    function generateCsrfToken(): string
    {
        startSecureSession();
        if (empty($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) !== 64) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCsrfToken')) {
    /**
     * Valida el token CSRF enviado por POST/HEADER.
     */
    function validateCsrfToken(?string $token): bool
    {
        startSecureSession();
        return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

/* -------------------------------------------------------------------------- */
/*  3) sendSecurityHeaders                                                    */
/* -------------------------------------------------------------------------- */
if (!function_exists('sendSecurityHeaders')) {
    /**
     * Envía cabeceras de seguridad, verificando que no se hayan mandado.
     */
    function sendSecurityHeaders(
        string $contentType      = 'text/html; charset=UTF-8',
        int    $hstsMaxAge       = 31536000,
        bool   $withXssProtect   = false,
        ?string $csp             = null
    ): void {
        if (headers_sent()) {
            return; // Evita warnings y roturas de DOM
        }

        $embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

        header('Content-Type: ' . $contentType);
        header('Strict-Transport-Security: max-age=' . $hstsMaxAge . '; includeSubDomains; preload');
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        if ($withXssProtect) {
            header('X-XSS-Protection: 1; mode=block');
        }
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), microphone=()');

        if (!$embedded) {
            header('X-Permitted-Cross-Domain-Policies: none');
            header('X-DNS-Prefetch-Control: off');
            header('Expect-CT: max-age=86400, enforce');
        }

        if ($csp) {
            header('Content-Security-Policy: ' . $csp);
        }
    }
}

/* -------------------------------------------------------------------------- */
/*  4) helper getPostParam / getGetParam  (safe wrappers)                     */
/* -------------------------------------------------------------------------- */
if (!function_exists('getPostInt')) {
    function getPostInt(string $key, ?int $default = null): ?int
    {
        $v = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
        return $v === false ? $default : $v;
    }
}

if (!function_exists('getPostFloat')) {
    function getPostFloat(string $key, ?float $default = null): ?float
    {
        $v = filter_input(INPUT_POST, $key, FILTER_VALIDATE_FLOAT);
        return $v === false ? $default : $v;
    }
}
