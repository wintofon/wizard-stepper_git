<?php
// strategy_ajax.php
// BASE_URL debe apuntar a la carpeta raíz, no a /ajax
if (!getenv('BASE_URL')) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL=' . $base);
}
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_GET['ajax']) || $_GET['ajax'] !== '1') {
    http_response_code(400);
    exit('Bad request');
}

$toolId = isset($_GET['tool_id']) ? (int)$_GET['tool_id'] : 0;
$toolTable = $_GET['tool_table'] ?? '';
$machiningTypeId = isset($_GET['machining_type_id']) ? (int)$_GET['machining_type_id'] : 0;

if (!$toolId || !$toolTable || !$machiningTypeId) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT s.strategy_id, s.name
        FROM toolstrategy ts
        INNER JOIN strategies s ON ts.strategy_id = s.strategy_id
        WHERE ts.tool_table = :tool_table
          AND ts.tool_id = :tool_id
          AND s.machining_type_id = :machining_type_id
        ORDER BY s.name ASC
    ");
    $stmt->execute([
        'tool_table' => $toolTable,
        'tool_id' => $toolId,
        'machining_type_id' => $machiningTypeId,
    ]);
    $strategies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($strategies);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
