<?php
declare(strict_types=1);

if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    if ($envBase !== false && $envBase !== '') {
        $base = rtrim($envBase, '/');
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path   = rtrim(dirname($_SERVER['SCRIPT_NAME'], 2), '/\\');
        $base   = rtrim("$scheme://$host$path", '/');
    }
    define('BASE_URL', $base);
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}
