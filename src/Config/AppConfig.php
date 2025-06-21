<?php
declare(strict_types=1);

// 🔒 BASE_URL → sólo el path raíz del proyecto (sin host, sin protocolo)
if (!defined('BASE_URL')) {
    // Podés setearlo por .env o variable de entorno del sistema
    $base = getenv('BASE_URL');

    // Si no existe, lo intenta detectar automáticamente
    if (!$base) {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($scriptName));
        $base = rtrim($dir, '/');
    }

    define('BASE_URL', $base);
}

// 🎯 asset() → genera una ruta interna absoluta relativa a BASE_URL
if (!function_exists('asset')) {
    function asset(string $path): string {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

// 🌐 FULL_BASE_URL → BASE_URL con protocolo + host (ideal para APIs, redirecciones, correos)
if (!defined('FULL_BASE_URL')) {
    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        $_SERVER['SERVER_PORT'] === '443'
    );
    $protocol = $isSecure ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    define('FULL_BASE_URL', rtrim($protocol . $host . BASE_URL, '/'));
}
