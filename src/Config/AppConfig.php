<?php
declare(strict_types=1);

if (!defined('BASE_URL')) {
    $base = getenv('BASE_URL') ?: '/wizard-stepper_git';
    define('BASE_URL', rtrim($base, '/'));
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}
