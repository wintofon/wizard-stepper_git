<?php
// ajax/tools_scroll.php
declare(strict_types=1);

use PDO;
require_once __DIR__ . '/../src/Utils/Session.php';
require_once __DIR__ . '/../includes/db.php';

sendSecurityHeaders('application/json; charset=UTF-8', 31536000, true);
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

startSecureSession();

if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'invalid_state']);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('CSRF fail');
}

if (isset($_SESSION['tools_permission']) && !$_SESSION['tools_permission']) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'forbidden']);
    exit;
}

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$limit = 30;
$offset = ($page - 1) * $limit;
$mode = $_GET['mode'] ?? 'manual';

if ($mode === 'auto') {
    $materialId = $_SESSION['material_id'] ?? null;
    $strategyId = $_SESSION['strategy_id'] ?? null;
    if (!$materialId || !$strategyId) {
        echo json_encode(['tools' => [], 'hasMore' => false]);
        exit;
    }
    $tables = [
        'tools_sgs'       => 'toolsmaterial_sgs',
        'tools_maykestag' => 'toolsmaterial_maykestag',
        'tools_schneider' => 'toolsmaterial_schneider',
        'tools_generico'  => 'toolsmaterial_generico',
    ];
    $parts = [];
    foreach ($tables as $t => $m) {
        if (!preg_match('/^[a-z0-9_]+$/', $t) || !preg_match('/^[a-z0-9_]+$/', $m)) {
            continue;
        }
        $parts[] = "SELECT t.tool_id, s.code AS serie, b.name AS brand, t.tool_code, t.name, t.image, t.diameter_mm, t.shank_diameter_mm, t.flute_length_mm, t.cut_length_mm, t.flute_count, m.rating, '{$t}' AS source_table FROM {$t} t JOIN {$m} m ON t.tool_id = m.tool_id JOIN series s ON t.series_id = s.id JOIN brands b ON s.brand_id = b.id JOIN toolstrategy ts ON ts.tool_id = t.tool_id AND ts.tool_table = '{$t}' WHERE m.material_id = :mat AND ts.strategy_id = :str AND m.rating > 0";
    }
    $sql = implode(' UNION ALL ', $parts) . ' ORDER BY rating DESC LIMIT :limit OFFSET :offset';
    $pdo = db();
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':mat', $materialId, PDO::PARAM_INT);
    $stmt->bindValue(':str', $strategyId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as &$row) {
        $img = $row['image'] ?? '';
        $row['img_url'] = $img !== '' ? '/wizard-stepper_git/' . ltrim((string)$img, '/') : '';
    }
    unset($row);
    $hasMore = count($data) === $limit;
    echo json_encode(['tools' => $data, 'hasMore' => $hasMore]);
    exit;
}

$sql = 'SELECT * FROM tools_generico ORDER BY diameter_mm ASC LIMIT :limit OFFSET :offset';
$key = 'tools_scroll_' . md5($sql . '|' . $page);
$cacheAvailable = function_exists('apcu_fetch');
$data = $cacheAvailable ? apcu_fetch($key, $hit) : false;
if (!$cacheAvailable || !$hit) {
    $pdo = db();
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as &$row) {
        $img = $row['image'] ?? '';
        $row['img_url'] = $img !== '' ? '/wizard-stepper_git/' . ltrim((string)$img, '/') : '';
    }
    unset($row);
    if ($cacheAvailable) {
        apcu_store($key, $data, 60);
    }
} else {
    foreach ($data as &$row) {
        if (!isset($row['img_url'])) {
            $img = $row['image'] ?? '';
            $row['img_url'] = $img !== '' ? '/wizard-stepper_git/' . ltrim((string)$img, '/') : '';
        }
    }
    unset($row);
}

$hasMore = count($data) === $limit;
echo json_encode(['tools' => $data, 'hasMore' => $hasMore]);

