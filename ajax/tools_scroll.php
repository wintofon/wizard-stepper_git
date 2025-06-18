<?php
declare(strict_types=1);
/**
 * ajax/tools_scroll.php
 * ---------------------
 * Devuelve lista paginada de fresas para el paso 1.
 * Requiere sesión activa en modo wizard y token CSRF válido.
 */

// ────── Cabeceras de seguridad ──────
header('Content-Type: application/json; charset=UTF-8');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=()');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header("Content-Security-Policy: default-src 'self';");

// ────── Sesión segura ──────
session_start([
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
]);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

// ────── Validaciones básicas ──────
if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    echo json_encode(['status' => 'error', 'message' => 'noAuth']);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_GET['csrf_token'] ?? '');
if (!validate_csrf_token($token)) {
    echo json_encode(['status' => 'error', 'message' => 'noAuth']);
    exit;
}

$page      = max(1, (int)($_GET['page'] ?? 1));
$page_size = (int)($_GET['page_size'] ?? 30);
if ($page_size < 1 || $page_size > 100) {
    $page_size = 30;
}
$offset = ($page - 1) * $page_size;

$sql = 'SELECT t.tool_id, t.tool_code, t.name, t.diameter_mm, t.image
        FROM tools_generico t
        ORDER BY t.diameter_mm ASC
        LIMIT :lim OFFSET :off';

// ────── Cache APCu 60s ──────
$cacheKey = 'tools_scroll_' . md5($sql . $page_size . '_' . $offset);
if (function_exists('apcu_fetch')) {
    $cached = apcu_fetch($cacheKey);
    if ($cached !== false) {
        echo json_encode($cached);
        exit;
    }
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':lim', $page_size, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$tools = $stmt->fetchAll();

$response = [
    'status'   => 'ok',
    'tools'    => $tools,
    'hasMore'  => count($tools) === $page_size,
    'nextPage' => $page + 1,
];

if (function_exists('apcu_store')) {
    apcu_store($cacheKey, $response, 60);
}

echo json_encode($response);
