<?php
declare(strict_types=1);
require_once __DIR__ . '/src/Config/AppConfig.php';
/**
 * File: wizard.php
 * Router principal del Wizard CNC
 * ---------------------------------------------------------------
 * ▸ Flujo: welcome → select_mode → wizard
 * ▸ Soporte para debug detallado vía ?debug=1
 * ▸ Inicializa sesión y variables clave de forma segura
 * ▸ Genera cabeceras de seguridad HTTP
 * ▸ Implementa CSRF para la selección de modo
 * ▸ Limpia localStorage cuando se forzó el estado “mode”
 * ▸ Dispatch dinámico según estado: welcome, mode, wizard
 * ---------------------------------------------------------------
 */

// -------------------------------------------
// [A] CONFIGURACIÓN DE ERRORES Y DEBUG
// -------------------------------------------
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);

if ($DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

if (!function_exists('dbg')) {
    function dbg(string $msg): void {
        global $DEBUG;
        if ($DEBUG) {
            error_log("[wizard.php] " . $msg);
        }
    }
}
dbg('🔧 wizard.php iniciado');

require_once __DIR__ . '/src/Utils/Session.php';

// -------------------------------------------
// [B] CABECERAS DE SEGURIDAD HTTP
// -------------------------------------------
sendSecurityHeaders('text/html; charset=UTF-8', 63072000, true);

// -------------------------------------------
// [C] INICIO DE SESIÓN SEGURA
// -------------------------------------------
startSecureSession();

// -------------------------------------------
// [D] FUNCIONES CSRF
// -------------------------------------------

// -------------------------------------------
// [E] OVERRIDE DE ESTADO “mode” POR GET
//     Y LIMPIEZA DE localStorage
// -------------------------------------------
if (filter_input(INPUT_GET, 'state', FILTER_SANITIZE_STRING) === 'mode') {
    $_SESSION['wizard_state'] = 'mode';
    session_regenerate_id(true);
    dbg('⤴ Forzado a estado = mode vía GET');

    // Emitimos un pequeño script que limpie localStorage['wizard_progress']
    echo '<script>
            try {
                localStorage.removeItem("wizard_progress");
            } catch(e) {}
          </script>';
}

// -------------------------------------------
// [F] ESTADO INICIAL POR DEFECTO
// -------------------------------------------
if (!isset($_SESSION['wizard_state'])) {
    $_SESSION['wizard_state'] = 'welcome';
    dbg('➕ Estado inicial seteado: welcome');
}

// -------------------------------------------
// [G] PROCESAR SELECCIÓN DE MODO (POST)
// -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tool_mode'])) {
    $postedCsrf = filter_input(INPUT_POST, 'csrf_token', FILTER_UNSAFE_RAW);
    if (!validateCsrfToken($postedCsrf)) {
        dbg('❌ CSRF inválido en selección de modo');
        http_response_code(400);
        exit('Solicitud inválida.');
    }

    // Saneamiento y validación de “tool_mode”
    $modeRaw = filter_input(INPUT_POST, 'tool_mode', FILTER_SANITIZE_STRING) ?? '';
    $mode = ($modeRaw === 'auto') ? 'auto' : 'manual';

    // Guardar en sesión
    $_SESSION['tool_mode']       = $mode;
    $_SESSION['wizard_progress'] = 1;       // Iniciamos flujo en Paso 1
    $_SESSION['wizard_state']    = 'wizard'; // Pasamos a “wizard”
    session_regenerate_id(true);

    dbg("✅ Modo seleccionado: {$mode}");
    // Redireccionamos a wizard.php para evitar reenvío de POST
    header('Location: wizard.php');
    exit;
}

// -------------------------------------------
// [H] AUTOLOAD + NAMESPACE PARA STEPflow
// -------------------------------------------
require_once __DIR__ . '/vendor/autoload.php';
use IndustrialWizard\StepperFlow;

// -------------------------------------------
// [I] DISPATCH SEGÚN ESTADO ACTUAL
// -------------------------------------------
$state = $_SESSION['wizard_state'] ?? 'welcome';
dbg("📦 Estado actual: {$state}");

switch ($state) {
    // ---------------------------------------
    // 1) WELCOME: Página inicial (bienvenida)
    // ---------------------------------------
    case 'welcome':
        include __DIR__ . '/views/welcome_page.php';
        break;

    // ---------------------------------------
    // 2) MODE: Selección entre “auto” o “manual”
    // ---------------------------------------
    case 'mode':
        $csrfToken = generateCsrfToken(); 
        include __DIR__ . '/views/mode_select.php';
        break;

    // ---------------------------------------
    // 3) WIZARD: Disparador del flujo completo
    // ---------------------------------------
    case 'wizard':
    default:
        // Leemos “tool_mode” de sesión (por defecto manual)
        $mode = $_SESSION['tool_mode'] ?? 'manual';

        // Etiquetas para la barra de progreso (1..6)
        $labels = [
            1 => $mode === 'auto' ? 'Material + Espesor'    : 'Herramienta',
            2 => $mode === 'auto' ? 'Estrategia'            : 'Detalles Herramienta',
            3 => $mode === 'auto' ? 'Recomend. Herramienta' : 'Estrategia',
            4 => $mode === 'auto' ? 'Detalles Herramienta'  : 'Material + Espesor',
            5 => 'Máquina',
            6 => 'Resultado'
        ];

        // Obtenemos el arreglo de pasos desde StepperFlow
        $flow = StepperFlow::get($mode);

        dbg("🧭 Ejecutando wizard con modo = {$mode}");
        include __DIR__ . '/views/wizard_layout.php';
        
        break;
}
