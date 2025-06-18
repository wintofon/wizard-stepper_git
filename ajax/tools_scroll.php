<?php
/**
 * AJAX endpoint: devuelve fresas paginadas para el navegador con scroll infinito.
 * Requiere una sesión activa del wizard y un token CSRF válido.
 */
declare(strict_types=1);

// ─────────────────────── Cabeceras de seguridad ──────────────────────
header('Content-Type: application/json; charset=UTF-8');
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=()');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/wizard_helpers.php';

// ─────────────────────── Sesión segura ───────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

dbg('tools_scroll.php iniciado');

// ─────────────────────── Validaciones básicas ────────────────────────
if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    echo json_encode(['status' => 'error', 'message' => 'noAuth']);
    exit;
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    echo json_encode(['status' => 'error', 'message' => 'csrf']);
    exit;
}

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$page = max(1, $page);
$pageSize = filter_input(INPUT_GET, 'page_size', FILTER_VALIDATE_INT) ?: 30;
$pageSize = max(1, min(100, $pageSize));
$offset = ($page - 1) * $pageSize;
$limit  = $pageSize + 1; // pedir una extra para saber si hay más

$sql = "SELECT t.tool_id, t.tool_code, t.name, t.diameter_mm, t.image
          FROM tools_generico t
         ORDER BY t.diameter_mm ASC
         LIMIT :lim OFFSET :off";

$cacheKey = "tools_scroll_{$page}_{$pageSize}";
$rows = function_exists('apcu_fetch') ? apcu_fetch($cacheKey) : false;
if ($rows === false) {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':lim',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':off',  $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (function_exists('apcu_store')) {
        apcu_store($cacheKey, $rows, 60);
    }
}

$hasMore = count($rows) > $pageSize;
$tools = array_slice($rows, 0, $pageSize);

foreach ($tools as &$t) {
    $t['tool_id']     = (int)$t['tool_id'];
    $t['diameter_mm'] = (float)$t['diameter_mm'];
    $t['image']       = (string)($t['image'] ?? '');
}
unset($t);

echo json_encode([
    'status'   => 'ok',
    'tools'    => $tools,
    'hasMore'  => $hasMore,
    'nextPage' => $hasMore ? $page + 1 : $page,
], JSON_UNESCAPED_UNICODE);

