<?php
/**
 * File: load-step.php
 * ---------------------------------------------------------------------------
 * Cargador asincrónico de vistas para el CNC Wizard Stepper
 * ---------------------------------------------------------------------------
 * RESPONSABILIDAD
 *   • Verifica sesión y progreso antes de servir un paso embebido
 *   • Define WIZARD_EMBEDDED para evitar <html> en la vista
 *   • Devuelve errores como JSON o bloques HTML con clase .step-error
 *
 * GET params:
 *   step   (1-6) Paso solicitado
 *   debug  (1)   Activa trazas en consola si está disponible
 */

declare(strict_types=1);

/* ───────────────────────────────────────────────────────────── */
/* 0. BOOTSTRAP                                                 */
/* ───────────────────────────────────────────────────────────── */
define('ROOT_DIR', dirname(__DIR__));
define('BASE_URL', getenv('BASE_URL') ?: rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
putenv('BASE_URL=' . BASE_URL);

/* ───────────────────────────────────────────────────────────── */
/* 1. DEPENDENCIAS                                               */
/* ───────────────────────────────────────────────────────────── */
require_once ROOT_DIR . '/src/Config/AppConfig.php';
require_once ROOT_DIR . '/src/Utils/Session.php';
if (is_readable(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
}

define('WIZARD_EMBEDDED', true);

/* ───────────────────────────────────────────────────────────── */
/* 2. CABECERAS SEGURAS                                          */
/* ───────────────────────────────────────────────────────────── */
sendSecurityHeaders('text/html; charset=UTF-8', 31536000, true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* ───────────────────────────────────────────────────────────── */
/* 3. SESIÓN SEGURA                                              */
/* ───────────────────────────────────────────────────────────── */
startSecureSession();

/* ───────────────────────────────────────────────────────────── */
/* 4. DEBUG OPCIONAL                                             */
/* ───────────────────────────────────────────────────────────── */
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable(ROOT_DIR . '/includes/debug.php')) {
    require_once ROOT_DIR . '/includes/debug.php';
    dbg('🔧 DEBUG activo – load-step.php');
} elseif (!function_exists('dbg')) {
    function dbg(...$a): void { /* no-op */ }
}

/* ───────────────────────────────────────────────────────────── */
/* 5. RESPONDER CON ERROR                                        */
/* ───────────────────────────────────────────────────────────── */
function respondError(int $code, string $msg): never {
    http_response_code($code);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    } else {
        echo '<div class="step-error alert alert-danger m-3">' .
             htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') .
             '</div>';
    }
    exit;
}

/* ───────────────────────────────────────────────────────────── */
/* 6. CONEXIÓN A BD (si falla la vista, igual tira error limpio) */
/* ───────────────────────────────────────────────────────────── */
$dbFile = ROOT_DIR . '/includes/db.php';
if (!is_readable($dbFile)) {
    respondError(500, 'Error interno: incluye de base de datos faltante.');
}
require_once $dbFile;

/* ───────────────────────────────────────────────────────────── */
/* 7. VALIDAR PARÁMETRO "step"                                   */
/* ───────────────────────────────────────────────────────────── */
$step = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 6]
]) ?: 1;
dbg("📥 Paso solicitado: {$step}");

/* ───────────────────────────────────────────────────────────── */
/* 8. VALIDAR ESTADO DE WIZARD Y PROGRESO                        */
/* ───────────────────────────────────────────────────────────── */
if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    if ($step === 1) {
        $_SESSION['wizard_state']    = 'wizard';
        $_SESSION['wizard_progress'] = 1;
        session_regenerate_id(true);
        dbg('⚙️ Wizard iniciado (paso 1)');
    } else {
        respondError(403, 'Debés iniciar el wizard desde el paso 1.');
    }
}

$currentProgress = (int)($_SESSION['wizard_progress'] ?? 1);
$maxAllowed = $currentProgress + 1;
if ($step > $maxAllowed) {
    dbg("🚫 Paso {$step} mayor al permitido {$maxAllowed}");
    respondError(403, "Paso no permitido aún. Avanzá en orden.");
}

/* ───────────────────────────────────────────────────────────── */
/* 9. DETECTAR MODO Y RESOLVER VISTA                             */
/* ───────────────────────────────────────────────────────────── */
$mode     = ($_SESSION['tool_mode'] ?? 'manual') === 'auto' ? 'auto' : 'manual';
$viewBase = ROOT_DIR . '/views/steps';
$viewPath = null;

foreach ([
    "{$viewBase}/{$mode}/step{$step}.php",
    "{$viewBase}/step{$step}.php"
] as $candidate) {
    if (is_readable($candidate)) {
        $viewPath = $candidate;
        break;
    }
}

if (!$viewPath) {
    respondError(404, "No se encontró la vista del paso {$step} en modo {$mode}.");
}
dbg("✔ Vista embebida: {$viewPath}");

/* ───────────────────────────────────────────────────────────── */
/* 10. INCLUIR LA VISTA EMBEBIDA                                 */
/* ───────────────────────────────────────────────────────────── */
include $viewPath;

/* ───────────────────────────────────────────────────────────── */
