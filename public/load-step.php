<?php
/**
 * File: load-step.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 *
 * Called by: wizard.php via asynchronous requests
 * Important GET params:
 *   - step    Requested wizard step
 *   - debug   Enable verbose output
 * Important session keys used:
 *   - $_SESSION['wizard_state']
 *   - $_SESSION['wizard_progress']
 *   - $_SESSION['tool_mode']
 * @TODO Extend documentation.
 */
declare(strict_types=1);
// Asegurar que la constante BASE_URL sea la misma que la utilizada por wizard.php
if (!getenv('BASE_URL')) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    putenv('BASE_URL=' . $base);
}
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../src/Utils/Session.php';
/**
 * File: C:\xampp\htdocs\wizard-stepper_git\load-step.php
 * ---------------------------------------------------------------
 * Cargador asincrónico de cada paso del wizard
 */

// ─────────────────────────────────────────────────────────────
// [1] CABECERAS DE SEGURIDAD Y NO-CACHING
// ─────────────────────────────────────────────────────────────
sendSecurityHeaders('text/html; charset=UTF-8', 63072000, true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ─────────────────────────────────────────────────────────────
// [2] SESIÓN SEGURA
// ─────────────────────────────────────────────────────────────
startSecureSession();

// ─────────────────────────────────────────────────────────────
// [3] DEBUG OPCIONAL
// ─────────────────────────────────────────────────────────────
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable(__DIR__ . '/../includes/debug.php')) {
    require_once __DIR__ . '/../includes/debug.php';
    dbg('🔧 load-step.php iniciado (modo DEBUG)');
} else {
    if (!function_exists('dbg')) {
        function dbg(...$args) { /* stub vacío */ }
    }
}

// ─────────────────────────────────────────────────────────────
// [4] INCLUIR CONEXIÓN A LA BASE DE DATOS
// ─────────────────────────────────────────────────────────────
$dbFile = __DIR__ . '/../includes/db.php';
if (!is_readable($dbFile)) {
    dbg('❌ No se encontró includes/db.php en: ' . $dbFile);
    http_response_code(500);
    exit('Error interno: falta el archivo de conexión a la BD.');
}
require_once $dbFile;
dbg('✔ Conexión a la BD establecida');

// ─────────────────────────────────────────────────────────────
// [5] LEER PARÁMETRO “step” ADELANTADO
// ─────────────────────────────────────────────────────────────
$requestedStep = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT);

// ─────────────────────────────────────────────────────────────
// [6] VERIFICAR ESTADO DE SESIÓN (PERMITIR PASO 1 INICIAL)
// ─────────────────────────────────────────────────────────────
// wizard_state is created in wizard.php when starting the flow
if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    if ($requestedStep === 1) {
        // Initialize wizard state and progress for first step
        $_SESSION['wizard_state']    = 'wizard';
        $_SESSION['wizard_progress'] = $_SESSION['wizard_progress'] ?? 1;
        session_regenerate_id(true);
        dbg('⚙️ Estado wizard inicializado en Paso 1');
    } else {
        dbg('❌ Acceso a load-step.php sin estado "wizard" en sesión');
        http_response_code(403);
        exit('Acceso prohibido: no estás en el wizard.');
    }
}
dbg('✔ Estado wizard: OK');

// ─────────────────────────────────────────────────────────────
// [7] VALIDAR PARÁMETRO “step”
// ─────────────────────────────────────────────────────────────
$step = filter_var($requestedStep, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 6]
]);
if ($step === false || $step === null) {
    dbg('❌ Parámetro step inválido');
    http_response_code(400);
    exit('Parámetro inválido.');
}
dbg("📥 Paso solicitado: {$step}");
// ─────────────────────────────────────────────────────────────
// [7] VERIFICAR PROGRESO DEL USUARIO
// ─────────────────────────────────────────────────────────────
$currentProgress = (int)($_SESSION['wizard_progress'] ?? 0); // progress set in handle-step.php
dbg("🔢 Progreso actual (sesión): {$currentProgress}");

$maxAllowedStep = $currentProgress + 1;
if ($step > $maxAllowedStep) {
    dbg("🚫 Paso solicitado ({$step}) excede el permitido ({$maxAllowedStep}), redirigiendo...");
    header("Location: load-step.php?step={$maxAllowedStep}");
    exit;
}

// ─────────────────────────────────────────────────────────────
// [8] DETECTAR MODO (auto vs manual)
// ─────────────────────────────────────────────────────────────
$modeRaw = $_SESSION['tool_mode'] ?? 'manual'; // set during step selection
$mode    = ($modeRaw === 'auto') ? 'auto' : 'manual';
dbg("🧭 Modo actual: {$mode}");

// ─────────────────────────────────────────────────────────────
// [9] BUSCAR ARCHIVO DE VISTA DEL PASO
// ─────────────────────────────────────────────────────────────
$baseDir        = __DIR__ . '/../views/steps';
$viewCandidates = [
    "{$baseDir}/{$mode}/step{$step}.php",
    "{$baseDir}/step{$step}.php"
];
$view = null;
foreach ($viewCandidates as $file) {
    if (is_readable($file)) {
        $view = $file;
        break;
    }
}
if (!$view) {
    dbg("❌ View no encontrada para step{$step} en modo {$mode}");
    http_response_code(404);
    exit('Página no encontrada.');
}
dbg("✔ Usando view: {$view}");

// ─────────────────────────────────────────────────────────────
// [10] DEFINIR CONSTANTE Y CARGAR LA VISTA
// ─────────────────────────────────────────────────────────────
define('WIZARD_EMBEDDED', true);
include $view;

// Fin de load-step.php
