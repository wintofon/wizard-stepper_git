<?php
/**
 * AJAX endpoint for paginated tool list used by infinite scroll.
 * Validates wizard session and CSRF token.
 */

declare(strict_types=1);

use PDO; // for type hints

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../src/Utils/Session.php';
require_once __DIR__ . '/../includes/wizard_helpers.php';

// ───────────────────────── Security headers ─────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ────────────────────────── Session / CSRF ──────────────────────────
startSecureSession();
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (($_SESSION['wizard_state'] ?? '') !== 'wizard' || !validateCsrfToken($token)) {
    echo json_encode(['status' => 'error', 'message' => 'noAuth']);
    exit;
}

// ────────────────────────── Pagination params ───────────────────────
$page      = max(1, (int)($_GET['page'] ?? 1));
$pageSize  = (int)($_GET['page_size'] ?? 30);
$pageSize  = min(max($pageSize, 1), 100);
$offset    = ($page - 1) * $pageSize;

// ────────────────────────────── Caching ─────────────────────────────
$queryHash = sha1("generico_{$page}_{$pageSize}");
$cached    = function_exists('apcu_fetch') ? apcu_fetch($queryHash) : false;
if ($cached !== false) {
    echo json_encode($cached, JSON_UNESCAPED_UNICODE);
    exit;
}

// ─────────────────────────── Database query ─────────────────────────
try {
    $sql  = 'SELECT t.tool_id, t.tool_code, t.name, t.diameter_mm, t.image
             FROM tools_generico t
             ORDER BY t.diameter_mm ASC
             LIMIT :ps OFFSET :off';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':ps',  $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,   PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    dbg('DB error', $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'db']);
    exit;
}

$hasMore  = count($rows) === $pageSize;
$nextPage = $hasMore ? $page + 1 : $page;
$response = [
    'status'  => 'ok',
    'tools'   => $rows,
    'hasMore' => $hasMore,
    'nextPage'=> $nextPage,
];

if (function_exists('apcu_store')) {
    apcu_store($queryHash, $response, 60);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

