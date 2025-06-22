<?php
/**
 * Render <link> tags for CSS styles.
 *
 * Variables expected:
 *   - array $styles    List of relative paths or absolute URLs
 *   - bool  $embedded  If true, main.css will not be included
 */
$embedded = $embedded ?? (defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED);
$styles = $styles ?? [];
if (!$embedded) {
    echo '<link rel="stylesheet" href="'.asset('assets/css/components/main.css').'">'.PHP_EOL;
}
foreach ($styles as $href) {
    $url = str_starts_with($href, 'http') ? $href : asset($href);
    echo '<link rel="stylesheet" href="'.$url.'">'.PHP_EOL;
}
if ($embedded) {
    echo '<style>body{background-color:transparent!important;}</style>'.PHP_EOL;
}
?>
