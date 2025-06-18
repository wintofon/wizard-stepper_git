<?php
// ajax/tools_scroll.php
declare(strict_types=1);

use PDO;
require_once __DIR__ . '/../src/Utils/Session.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=UTF-8');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=()');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

startSecureSession();

if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'invalid_state']);
    exit;
}

$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'csrf']);
    exit;
}

if (isset($_SESSION['tools_permission']) && !$_SESSION['tools_permission']) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'forbidden']);
    exit;
}

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$pageSize = filter_input(INPUT_GET, 'page_size', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]) ?: 30;

$offset = ($page - 1) * $pageSize;
$sql = 'SELECT * FROM tools_generico ORDER BY diameter_mm ASC LIMIT :limit OFFSET :offset';

$key = 'tools_scroll_' . md5($sql . '|' . $page . '|' . $pageSize);
$data = apcu_fetch($key, $hit);
if (!$hit) {
    $pdo = db();
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    apcu_store($key, $data, 60);
}

$hasMore = count($data) === $pageSize;

echo json_encode([
    'status'   => 'ok',
    'tools'    => $data,
    'hasMore'  => $hasMore,
    'nextPage' => $page + 1,
], JSON_UNESCAPED_UNICODE);

