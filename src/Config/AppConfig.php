<?php
/**
 * File: AppConfig.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
declare(strict_types=1);

// ---------------------------------------------------------------------------
// 📁 AppConfig.php – Configuración global del proyecto CNC Wizard
// Define BASE_URL, BASE_HOST, asset() y otras constantes globales
// ---------------------------------------------------------------------------

if (!defined('BASE_URL')) {
    $env  = getenv('BASE_URL');
    $base = $env !== false ? $env : dirname(dirname($_SERVER['SCRIPT_NAME']));

    if (($_SERVER['HTTP_HOST'] ?? '') === 'localhost' && !str_contains($base, 'wizard-stepper')) {
        $base = rtrim($base, '/') . '/wizard-stepper';
    }

    define('BASE_URL', rtrim($base, '/'));
}

if (!defined('BASE_HOST')) {
    // 🌍 BASE_HOST → protocolo + dominio (para redirecciones, APIs, etc.)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_HOST', $scheme . '://' . $host);
}

// 🔧 Función helper para generar rutas absolutas a recursos públicos
if (!function_exists('asset')) {
    function asset(string $path): string
    {
        if (preg_match('#^(?:https?:)?//#', $path)) {
            return $path; // ya es absoluta
        }

        $clean = '/' . ltrim($path, '/');
        return rtrim(BASE_URL, '/') . $clean;
    }

    // Test manual: visitar .../src/Config/AppConfig.php?test_asset=1 y verificar
    // en la pestaña Red que la URL devuelta responda 200 OK
    if (isset($_GET['test_asset'])) {
        echo asset('assets/js/step6.js');
        exit;
    }
}

// 🔧 Función helper para generar URLs absolutas completas (con host)
if (!function_exists('full_url')) {
    function full_url(string $path): string
    {
        return BASE_HOST . asset($path);
    }
}
