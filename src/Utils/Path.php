<?php
/**
 * Utility helpers for generating URLs to public assets.
 */

declare(strict_types=1);

// Determine base URL of the application once
if (!defined('BASE_URL')) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base   = rtrim(str_replace(basename($script), '', $script), '/');
    define('BASE_URL', $base ?: '');
}

if (!function_exists('asset_url')) {
    /**
     * Returns an absolute URL for a file under the `assets` directory.
     */
    function asset_url(string $path): string
    {
        return rtrim(BASE_URL, '/') . '/assets/' . ltrim($path, '/');
    }
}
