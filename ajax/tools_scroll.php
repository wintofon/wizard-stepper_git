<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/init.php';

use PDO;

require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Utils/Session.php';
require_once __DIR__ . '/../includes/db.php';

sendSecurityHeaders('application/json; charset=UTF-8', 31536000, true);
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

startSecureSession();

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCsrfToken($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'csrf']);
    exit;
}

$materialId = filter_input(INPUT_GET, 'material_id', FILTER_VALIDATE_INT);
$strategyId = filter_input(INPUT_GET, 'strategy_id', FILTER_VALIDATE_INT);
$page       = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$perPage    = filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT) ?: 12;

if ($materialId === false || $materialId === null) {
    $materialId = $_SESSION['material_id'] ?? null;
}
if ($strategyId === false || $strategyId === null) {
    $strategyId = $_SESSION['strategy_id'] ?? null;
}
if ($materialId === null || $strategyId === null) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_params']);
    exit;
}

$page    = max(1, $page);
$perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 12;
$offset  = ($page - 1) * $perPage;

$pdo = db();

$defs = [
    ['table' => 'tools_sgs', 'mat' => 'toolsmaterial_sgs', 'brand' => 'SGS'],
    ['table' => 'tools_maykestag', 'mat' => 'toolsmaterial_maykestag', 'brand' => 'MAYKESTAG'],
    ['table' => 'tools_schneider', 'mat' => 'toolsmaterial_schneider', 'brand' => 'SCHNEIDER'],
    ['table' => 'tools_generico', 'mat' => 'toolsmaterial_generico', 'brand' => 'GENÉRICO'],
];

$parts = [];
foreach ($defs as $d) {
    $parts[] = "SELECT t.tool_id, t.series_id, s.code AS serie, t.tool_code, t.name,"
             . " t.diameter_mm, t.shank_diameter_mm, t.cut_length_mm, t.flute_count,"
             . " '{$d['brand']}' AS brand, '{$d['table']}' AS source_table,"
             . " tm.rating, t.image"
             . " FROM {$d['table']} t"
             . " JOIN toolstrategy ts ON ts.tool_table='{$d['table']}' AND ts.tool_id=t.tool_id AND ts.strategy_id=:sid"
             . " JOIN {$d['mat']} tm ON tm.tool_id=t.tool_id AND tm.material_id=:mid"
             . " JOIN series s ON s.id=t.series_id";
}

$sql = implode(' UNION ALL ', $parts)
     . ' ORDER BY rating DESC LIMIT :limit OFFSET :offset';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':sid', $strategyId, PDO::PARAM_INT);
$stmt->bindValue(':mid', $materialId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as &$r) {
    $img = $r['image'] ?? '';
    $r['image_url'] = $img ? asset($img) : '';
}
unset($r);

$hasMore = count($rows) === $perPage;

echo json_encode([
    'tools'    => $rows,
    'has_more' => $hasMore,
]);
