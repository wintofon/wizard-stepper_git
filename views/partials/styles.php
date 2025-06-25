<?php
/**
 * Render <link> tags for CSS styles.
 *
 * Variables expected:
 *   - array $styles       List of relative paths or absolute URLs
 *   - bool  $embedded     If true, main.css will not be included
 *   - array &$assetErrors Optional array where missing local files are noted
 */
$embedded    = $embedded ?? (defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED);
$styles      = $styles ?? [];
$assetErrors = $assetErrors ?? [];
// main.css is now loaded globally from wizard_layout.php
foreach ($styles as $href) {
    $isUrl = str_starts_with($href, 'http');
    $url   = $isUrl ? $href : asset($href);
    if (!$isUrl) {
        $file = dirname(__DIR__, 2) . '/' . ltrim($href, '/');
        if (!is_readable($file)) {
            $assetErrors[] = basename($href) . ' no encontrado localmente.';
        }
    }
    echo '<link rel="stylesheet" href="'.$url.'">'.PHP_EOL;
}
if ($embedded) {
    echo '<style nonce="'.get_csp_nonce().'">body{background-color:transparent!important;}</style>'.PHP_EOL;
}
?>
