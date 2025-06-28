<?php
/**
 * File: step4.php — Paso 4 (Manual)
 * Descripción: Selección de material y espesor dentro del Wizard CNC.
 *
 * ▶ Cambios v6.2 (28‑Jun‑2025)
 *   • "Buscar material" y subtítulo "— o elegí por categoría —" ahora usan
 *     la misma clase .mat-label (celeste corporativo, 1.25 rem).
 *   • Hint “(2 + letras)” mantiene color heredado y tamaño 0.9 rem.
 *
 * Dependencias:
 *   • assets/css/generic/material.css (define .mat-label)
 */

declare(strict_types=1);

//--------------------------------------------------------------------------
// [0] Helper rápido para respuestas de error
//--------------------------------------------------------------------------
if (!function_exists('respondError')) {
    function respondError(int $code, string $msg): void {
        http_response_code($code);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
        } else {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        exit;
    }
}

require_once __DIR__ . '/../../../src/Utils/Session.php';
require_once __DIR__ . '/../../../includes/wizard_helpers.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/debug.php';

//--------------------------------------------------------------------------
// [A] Cabeceras de seguridad / anti‑caching
//--------------------------------------------------------------------------
sendSecurityHeaders('text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");

//--------------------------------------------------------------------------
// [B] Debug runtime (?debug=true)
//--------------------------------------------------------------------------
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors', '1'); }
else        { error_reporting(0);    ini_set('display_errors', '0'); }
if ($DEBUG && function_exists('dbg')) dbg('🔧 step4.php iniciado');

//--------------------------------------------------------------------------
// [C] Sesión segura
//--------------------------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL . '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
    dbg('🔒 Sesión iniciada');
}

//--------------------------------------------------------------------------
// [D] Control de flujo
//--------------------------------------------------------------------------
if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state'] !== 'wizard') {
    header('Location:' . asset('wizard.php')); exit;
}
if ((int)($_SESSION['wizard_progress'] ?? 0) < 3) {
    header('Location:' . asset('views/steps/auto/step' . (int)$_SESSION['wizard_progress'] . '.php')); exit;
}

//--------------------------------------------------------------------------
// [E] Rate‑limit
//--------------------------------------------------------------------------
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$_SESSION['rate_limit']       ??= [];
$_SESSION['rate_limit'][$clientIp] = array_filter(
    $_SESSION['rate_limit'][$clientIp] ?? [],
    fn(int $t) => ($t + 300) > time()
);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && count($_SESSION['rate_limit'][$clientIp]) >= 10) {
    respondError(200, '429 – Demasiados intentos.');
}

//--------------------------------------------------------------------------
// [F] CSRF token
//--------------------------------------------------------------------------
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

//--------------------------------------------------------------------------
// [G] Herramienta seleccionada
//--------------------------------------------------------------------------
if (empty($_SESSION['tool_id']) || empty($_SESSION['tool_table'])) {
    header('Location:' . asset('views/steps/auto/step2.php')); exit;
}
$toolId  = (int)$_SESSION['tool_id'];
$toolTbl = preg_replace('/[^a-z0-9_]/i', '', $_SESSION['tool_table']);

//--------------------------------------------------------------------------
// [H] Cargar materiales compatibles (solo “Madera%”)
//--------------------------------------------------------------------------
$compatTbl = 'toolsmaterial_' . str_replace('tools_', '', $toolTbl);
$sql = "
  SELECT m.material_id, m.name, c.category_id, c.name AS cat
    FROM {$compatTbl} tm
    JOIN materials          m ON m.material_id = tm.material_id
    JOIN materialcategories c ON c.category_id = m.category_id
   WHERE tm.tool_id = :tid AND c.name LIKE 'Madera%'
   ORDER BY c.name, m.name";
$stmt = $pdo->prepare($sql);
$stmt->execute([':tid' => $toolId]);
$mats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$parents  = [];
$children = [];
foreach ($mats as $m) {
    $cid              = (int)$m['category_id'];
    $parents[$cid]    = $m['cat'];
    $children[$cid][] = [
        'id'   => (int)$m['material_id'],
        'cid'  => $cid,
        'name' => $m['name'],
    ];
}
if ($DEBUG) { dbg('parents', $parents); dbg('children', $children); }

//--------------------------------------------------------------------------
// [I] Procesamiento POST
//--------------------------------------------------------------------------
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? ''))         $err = 'Token de seguridad inválido.';
    $mat = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);
    $thk = filter_input(INPUT_POST, 'thickness',   FILTER_VALIDATE_FLOAT);
    if (!$err && ($mat === false || $mat < 1))                   $err = 'Material no válido.';
    if (!$err && ($thk === false || $thk <= 0))                  $err = 'Espesor no válido.';
    if (!$err && !array_key_exists($mat, array_column($mats, 'material_id', 'material_id')))
        $err = 'Material no válido.';

    if (!$err) {
        $_SESSION['rate_limit'][$clientIp][] = time();
        session_regenerate_id(true);
        $_SESSION['material_id']     = $mat;
        $_SESSION['thickness']       = (float)$thk;
        $_SESSION['wizard_progress'] = 4;
        header('Location:' . asset('views/steps/manual/step5.php')); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 4 – Material</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
<?php
  $styles = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'assets/css/generic/material.css',
    'assets/css/objects/step-common.css',
  ];
  $embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
  include __DIR__ . '/../../partials/styles.php';
?>
<style>.mat-label small{font-size:.9rem;color:inherit}</style>
<?php if (!$embedded): ?>
<script>
  window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
  window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
</script>
<?php endif; ?>
</head>
<body>
<main class="container py-4">
  <h2 class="step-title"><i data-feather="layers"></i> Material y espesor</h2>
  <p class="step-desc">Indicá el material a procesar y su espesor.</p>

  <?php if ($err): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <form id="formMat" method="post" novalidate>
    <input type="hidden" name="step"        value="4">
    <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="material_id" id="material_id" value="">

    <!-- 1) Buscador ---------------------------------------------------- -->
    <div class="mb-3 position-relative">
      <label for="matSearch" class="form-label mat-label">
        Buscar material <span style="font-size:.9rem">(2 + letras)</span>
      </label>
      <input id="matSearch" class="form-control" autocomplete="off" placeholder="Ej.: MDF…">
      <div id="no-match-msg">Material no encontrado</
