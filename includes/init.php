<?php
// Global initialization for dynamic base URL
if (!defined('BASE_URL')) {
    $scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = dirname($_SERVER['SCRIPT_NAME']);
    define('BASE_URL', rtrim("{$scheme}://{$host}{$dir}", '/\\') . '/');
}

