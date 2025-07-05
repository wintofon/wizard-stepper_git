<?php
// api/series/bulk-column.php
require_once __DIR__.'/../../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$column = $data['column'] ?? '';
$value  = $data['value'] ?? '';
$seriesId = isset($data['series_id']) ? (int)$data['series_id'] : 0;

$allowed = ['made_in','material','tool_type','coated'];
if (!in_array($column, $allowed, true) || !$seriesId) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$stmt = $pdo->prepare('SELECT brand_id FROM series WHERE id = ?');
$stmt->execute([$seriesId]);
$brandId = $stmt->fetchColumn();
if (!$brandId) {
    echo json_encode(['success' => false, 'error' => 'Serie no encontrada']);
    exit;
}

$table = brandTable((int)$brandId);
$sql = "UPDATE {$table} SET {$column} = :val WHERE series_id = :sid";
$u = $pdo->prepare($sql);
$ok = $u->execute([':val' => $value, ':sid' => $seriesId]);

echo json_encode(['success' => $ok]);
