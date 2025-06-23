<?php
/**
 * Render <link> tags for CSS styles.
 *
 * Variables expected:
 *   - array $styles      List of relative paths or absolute URLs
 *   - bool  $embedded    If true, body background is transparent
 *   - array $assetErrors (optional) collects missing local assets
 */

$embedded    = $embedded ?? (defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED);
$styles      = $styles ?? [];
$assetErrors = $assetErrors ?? [];
$root        = dirname(__DIR__, 2) . '/';

foreach ($styles as $href) {
    if (str_starts_with($href, 'http')) {
        $url = $href;
    } else {
        $file = $root . ltrim($href, '/');
        if (!is_file($file)) {
            $assetErrors[] = basename($href) . ' no encontrado localmente.';
            if ($href === 'assets/css/generic/bootstrap.min.css') {
                $url = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
            } else {
                $url = asset($href);
            }
        } else {
            $url = asset($href);
        }
    }
    echo '<link rel="stylesheet" href="' . $url . '">' . PHP_EOL;
}
if ($embedded) {
    echo '<style>body{background-color:transparent!important;}</style>' . PHP_EOL;
}
?>
