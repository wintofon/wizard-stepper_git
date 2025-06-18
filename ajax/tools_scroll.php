<?php
/**
 * ajax/tools_scroll.php
 * Endpoint para scroll infinito de herramientas.
 */

declare(strict_types=1);

// 1) Cabeceras de seguridad y JSON
header('Content-Type: application/json; charset=UTF-8');
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Content-Security-Policy: default-src 'self'");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=()');

// 2) Sesión necesaria para validar estado y CSRF
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 3) Verificar que estamos dentro del wizard
if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'noAuth']);
    exit;
}

// 4) CSRF: compara token de cabecera con el almacenado en sesión
$headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $headerToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'csrf']);
    exit;
}

// 5) Permiso mínimo: progress >= 1
if ((int)($_SESSION['wizard_progress'] ?? 0) < 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'noPerm']);
    exit;
}

// 6) Leer parámetros de paginación
$page = max(1, (int)($_GET['page'] ?? 1));
$size = (int)($_GET['page_size'] ?? 30);
$size = max(1, min(100, $size));
$offset = ($page - 1) * $size;

// 7) Consulta SQL base
require_once __DIR__ . '/../includes/db.php';
$sql = "SELECT t.tool_id, t.tool_code, t.name, t.diameter_mm, t.image
          FROM tools_generico t
         ORDER BY t.diameter_mm ASC
         LIMIT :lim OFFSET :off";

// 8) Intentar obtener datos en caché APCu
$cacheKey = 'tools_scroll_' . md5($sql . $page);
$rows = function_exists('apcu_fetch') ? apcu_fetch($cacheKey) : false;

// 9) Si no hay caché, ejecutar la consulta
if ($rows === false) {
    $st = $pdo->prepare($sql);
    $st->bindValue(':lim', $size, PDO::PARAM_INT);
    $st->bindValue(':off', $offset, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (function_exists('apcu_store')) {
        apcu_store($cacheKey, $rows, 60); // 60 s
    }
}

// 10) Determinar si existe otra página
$hasMore = count($rows) === $size;

// 11) Enviar respuesta JSON
echo json_encode([
    'status'   => 'ok',
    'tools'    => $rows,
    'hasMore'  => $hasMore,
    'nextPage' => $page + 1,
]);
