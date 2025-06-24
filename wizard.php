<?php
/**
 * File: wizard.php
 * Router principal del CNC Wizard Stepper
 */

declare(strict_types=1);

/* ──────────────── BASE_URL & DEPENDENCIAS ──────────────── */
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
    putenv('BASE_URL=' . BASE_URL);
}
require_once __DIR__ . '/src/Config/AppConfig.php';

/* ──────────────── DEBUG CONFIG ──────────────── */
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
error_reporting($DEBUG ? E_ALL : 0);
ini_set('display_errors', $DEBUG ? '1' : '0');
if (!function_exists('dbg')) {
    function dbg(string $msg): void {
        global $DEBUG;
        if ($DEBUG) error_log("[wizard.php] " . $msg);
    }
}
dbg('🔧 wizard.php iniciado');

/* ──────────────── SESIÓN SEGURA ──────────────── */
require_once __DIR__ . '/src/Utils/Session.php';
sendSecurityHeaders('text/html; charset=UTF-8', 63072000, true);
startSecureSession();

/* ──────────────── FORZAR ESTADO “mode” SI SE PIDE ──────────────── */
$stateOverride = filter_input(INPUT_GET, 'state', FILTER_UNSAFE_RAW);
if (trim((string)$stateOverride) === 'mode') {
    $_SESSION['wizard_state'] = 'mode';
    session_regenerate_id(true);
    dbg('⤴ Forzado a estado = mode vía GET');
    echo '<script>try{localStorage.removeItem("wizard_progress");}catch(e){}</script>';
}

/* ──────────────── ESTADO POR DEFECTO ──────────────── */
if (!isset($_SESSION['wizard_state'])) {
    $_SESSION['wizard_state'] = 'welcome';
    dbg('➕ Estado inicial seteado: welcome');
}

/* ──────────────── PROCESAR POST DE MODO ──────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tool_mode'])) {
    $postedCsrf = filter_input(INPUT_POST, 'csrf_token', FILTER_UNSAFE_RAW);
    if (!validateCsrfToken($postedCsrf)) {
        dbg('❌ CSRF inválido en selección de modo');
        http_response_code(400);
        exit('Solicitud inválida.');
    }

    $mode = trim((string)($_POST['tool_mode'] ?? ''));
    $mode = ($mode === 'auto') ? 'auto' : 'manual';

    $_SESSION['tool_mode']       = $mode;
    $_SESSION['wizard_progress'] = 1;
    $_SESSION['wizard_state']    = 'wizard';
    session_regenerate_id(true);

    dbg("✅ Modo seleccionado: {$mode}");
    header('Location: wizard.php');
    exit;
}

/* ──────────────── AUTOLOAD + DISPATCH ──────────────── */
require_once __DIR__ . '/vendor/autoload.php';
use IndustrialWizard\StepperFlow;

$state = $_SESSION['wizard_state'] ?? 'welcome';
dbg("📦 Estado actual: {$state}");

switch ($state) {
    case 'welcome':
        include __DIR__ . '/views/welcome.php';
        break;

    case 'mode':
        $csrfToken = generateCsrfToken();
        include __DIR__ . '/views/select_mode.php';
        break;

    case 'wizard':
    default:
        $mode = $_SESSION['tool_mode'] ?? 'manual';
        $labels = [
            1 => $mode === 'auto' ? 'Material + Espesor'    : 'Herramienta',
            2 => $mode === 'auto' ? 'Estrategia'            : 'Detalles Herramienta',
            3 => $mode === 'auto' ? 'Recomend. Herramienta' : 'Estrategia',
            4 => $mode === 'auto' ? 'Detalles Herramienta'  : 'Material + Espesor',
            5 => 'Máquina',
            6 => 'Resultado'
        ];
        $flow = StepperFlow::get($mode);
        dbg("🧭 Ejecutando wizard con modo = {$mode}");
        include __DIR__ . '/views/wizard_layout.php';
        break;
}
