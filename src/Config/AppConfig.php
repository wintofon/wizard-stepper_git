<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// 📁 AppConfig.php – Configuración global del proyecto CNC Wizard
// Define BASE_URL, BASE_HOST, asset() y otras constantes globales
// ---------------------------------------------------------------------------

if (!defined('BASE_URL')) {
    // 🌐 BASE_URL → solo path (funciona bien en XAMPP y producción)
    $basePath = getenv('BASE_URL') ?: dirname($_SERVER['SCRIPT_NAME']);
    define('BASE_URL', rtrim($basePath, '/'));
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
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

// 🔧 Función helper para generar URLs absolutas completas (con host)
if (!function_exists('full_url')) {
    function full_url(string $path): string
    {
        return BASE_HOST . asset($path);
    }
}
