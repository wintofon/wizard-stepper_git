<?php
if (!defined('BASE_URL')) {
    $scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = dirname($_SERVER['SCRIPT_NAME']);
    define('BASE_URL', rtrim("{$scheme}://{$host}{$path}", '/\\') . '/');
}
