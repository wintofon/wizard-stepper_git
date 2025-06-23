<?php
/**
 * File: load-step.php
 * ---------------------------------------------------------------------------
 * Cargador asincrónico de vistas para el CNC Wizard Stepper
 * ---------------------------------------------------------------------------
 * RESPONSABILIDAD
 *   • Verificar sesión, progreso y modo (auto | manual) antes de servir un paso
 *   • Incluir la vista en modo embebido (sin <html> global)
 *   • Blindar contra CSRF/SSRF y forzar cabeceras seguras
 *   • Devolver errores claros: JSON (si se pide) u HTML placeholder
 *
 * Endpoint: wizard.php  → fetch('load-step.php?step=N')
 * GET params:
 *   step   (int 1-6)  Paso solicitado
 *   debug  (bool)     Activa trazas dbg()
 *
 * Sesión esperada:
 *   wizard_state    = 'wizard'
 *   wizard_progress = 1-6  (último paso completado)
 *   tool_mode       = 'manual' | 'auto'
 *
 * 2025-06-23  (v2 ultra-blindada)
 */

declare(strict_types=1);

/* -------------------------------------------------------------------------- */
/* 0)  BOOTSTRAP BÁSICO                                                       */
/* -------------------------------------------------------------------------- */
define('ROOT_DIR', dirname(__DIR__));           // /project_root
define('BASE_URL', getenv('BASE_URL')
    ?: rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
putenv('BASE_URL=' . BASE_URL);

/* -------------------------------------------------------------------------- */
/* 1)  DEPENDENCIAS CORE                                                      */
/* -------------------------------------------------------------------------- */
require_once ROOT_DIR . '/src/Config/AppConfig.php';
require_once ROOT_DIR . '/src/Utils/Session.php';        // sendSecurityHeaders(), startSecureSession()

/* Autoload PSR-4 si existe vendor/autoload.php */
$autoload = ROOT_DIR . '/vendor/autoload.php';
if (is_readable($autoload)) {
    require_once $autoload;
}

/* -------------------------------------------------------------------------- */
/* 2)  CABECERAS DE SEGURIDAD                                                 */
/* -------------------------------------------------------------------------- */
sendSecurityHeaders('text/html; charset=UTF-8', 63072000, true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* -------------------------------------------------------------------------- */
/* 3)  SESIÓN SEGURA                                                          */
/* -------------------------------------------------------------------------- */
startSecureSession();

/* -------------------------------------------------------------------------- */
/* 4)  DEBUG (stub si no existe)                                              */
/* -------------------------------------------------------------------------- */
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable(ROOT_DIR . '/includes/debug.php')) {
    require_once ROOT_DIR . '/includes/debug.php';
    dbg('🔧 DEBUG activo – load-step.php');
} elseif (!function_exists('dbg')) {
    function dbg(...$a): void { /* no-op */ }
}

/* -------------------------------------------------------------------------- */
/* 5)  DB CONNECTION (on-demand para las vistas)                              */
/* -------------------------------------------------------------------------- */
$dbFile = ROOT_DIR . '/includes/db.php';
if (!is_readable($dbFile)) {
    respondError(500, 'Falta includes/db.php');
}
require_once $dbFile;
dbg('✔ DB incluida');

/* -------------------------------------------------------------------------- */
/* 6)  HELPER GLOBAL respondError()                                           */
/* -------------------------------------------------------------------------- */
function respondError(int $code, string $msg): never
{
    http_response_code($code);
    $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($wantsJson) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    } else {
        echo '<div class="step-error alert alert-danger m-3">' .
             htmlspecialchars($msg, ENT_QUOTES) .
             '</div>';
    }
    exit;
}

/* -------------------------------------------------------------------------- */
/* 7)  PARÁMETRO “step”                                                       */
/* -------------------------------------------------------------------------- */
$step = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 6],
]) ?: 1;
dbg("📥 Paso solicitado: {$step}");

/* -------------------------------------------------------------------------- */
/* 8)  VALIDAR ESTADO WIZARD                                                  */
/* -------------------------------------------------------------------------- */
if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    if ($step === 1) {
        $_SESSION['wizard_state']    = 'wizard';
        $_SESSION['wizard_progress'] = 1;
        session_regenerate_id(true);
        dbg('⚙️ Wizard iniciado (paso 1)');
    } else {
        respondError(403, 'Iniciá el wizard desde el paso 1.');
    }
}

$currentProgress = (int)($_SESSION['wizard_progress'] ?? 1);
$maxAllowedStep  = $currentProgress + 1;

if ($step > $maxAllowedStep) {
    dbg("🚫 step{$step} > permitido {$maxAllowedStep}");
    header("Location: load-step.php?step={$maxAllowedStep}");
    exit;
}
dbg("🔢 Progreso actual OK ({$currentProgress})");

/* -------------------------------------------------------------------------- */
/* 9)  DETECTAR MODO                                                          */
/* -------------------------------------------------------------------------- */
$mode = ($_SESSION['tool_mode'] ?? 'manual') === 'auto' ? 'auto' : 'manual';
dbg("🧭 Modo: {$mode}");

/* -------------------------------------------------------------------------- */
/* 10)  RESOLVER VISTA                                                        */
/* -------------------------------------------------------------------------- */
$viewBase = ROOT_DIR . '/views/steps';
$viewPath = null;
foreach ([
    "{$viewBase}/{$mode}/step{$step}.php",
    "{$viewBase}/step{$step}.php",
] as $candidate) {
    if (is_readable($candidate)) {
        $viewPath = $candidate;
        break;
    }
}
if (!$viewPath) {
    respondError(404, "Vista paso {$step} (modo {$mode}) no encontrada.");
}
dbg("✔ Vista: {$viewPath}");

/* -------------------------------------------------------------------------- */
/* 11)  CARGAR VISTA EMBEBIDA                                                 */
/* -------------------------------------------------------------------------- */
define('WIZARD_EMBEDDED', true);
include $viewPath;

/* -------------------------------------------------------------------------- */
/* 12)  FIN                                                                   */
/* -------------------------------------------------------------------------- */
