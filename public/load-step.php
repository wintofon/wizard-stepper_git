<?php
/**
 * File: load-step.php
 * ---------------------------------------------------------------------------
 * Cargador asincrónico de vistas para el CNC Wizard Stepper
 * ---------------------------------------------------------------------------
 * RESPONSABILIDAD
 *   • Validar sesión, progreso y modo antes de servir un paso.
 *   • Incluir la vista correspondiente en modo “embebido” (sin <html> global).
 *   • Blindar contra CSRF, SSRF y ataques de caché con cabeceras duras.
 *   • Devolver códigos HTTP claros y, opcionalmente, JSON de error si el
 *     request incluye `Accept: application/json`.
 *
 * Punto de entrada:   wizard.php → fetch('load-step.php?step=N')
 * Parámetros GET:
 *   - step   (int 1-6)  Paso solicitado
 *   - debug  (bool)     Habilita trazas dbg()
 *
 * Sesión esperada:
 *   - wizard_state    = 'wizard'
 *   - wizard_progress = 1-6 (último paso completado)
 *   - tool_mode       = 'manual' | 'auto'
 *
 * 2025-06-23 (blindaje total):
 *   ✔ Cabeceras CSP, HSTS, X-Frame-Options, Referrer-Policy
 *   ✔ startSecureSession() con cookies SameSite=Strict + regeneración
 *   ✔ Helper respondError() → HTML o JSON según header Accept
 *   ✔ Sanitizado estricto de “step” y fallback a 1
 *   ✔ Forzado WIZARD_EMBEDDED=true antes de incluir la vista
 *   ✔ Stub dbg() silencioso si no se carga includes/debug.php
 */

declare(strict_types=1);

/* -------------------------------------------------------------------------- */
/* 0)  CONSTANTES BÁSICAS                                                     */
/* -------------------------------------------------------------------------- */
$ROOT_DIR = dirname(__DIR__);               // → /project_root
$BASE_URL = getenv('BASE_URL')
         ?: rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
putenv("BASE_URL={$BASE_URL}");

/* -------------------------------------------------------------------------- */
/* 1)  DEPENDENCIAS CORE (no autoload para mayor compatibilidad)              */
/* -------------------------------------------------------------------------- */
require_once "{$ROOT_DIR}/src/Config/AppConfig.php";
require_once "{$ROOT_DIR}/src/Utils/Session.php";      // sendSecurityHeaders(), startSecureSession()

/* -------------------------------------------------------------------------- */
/* 2)  CABECERAS DE SEGURIDAD Y  NO-CACHING                                   */
/* -------------------------------------------------------------------------- */
sendSecurityHeaders('text/html; charset=UTF-8', 63072000, true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* -------------------------------------------------------------------------- */
/* 3)  SESIÓN SEGURA                                                          */
/* -------------------------------------------------------------------------- */
startSecureSession();

/* -------------------------------------------------------------------------- */
/* 4)  DEBUG OPCIONAL (stub si no existe)                                     */
/* -------------------------------------------------------------------------- */
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable("{$ROOT_DIR}/includes/debug.php")) {
    require_once "{$ROOT_DIR}/includes/debug.php";
    dbg('🔧 load-step.php DEBUG activo');
} else {
    if (!function_exists('dbg')) {
        function dbg(...$args): void { /* no-op */ }
    }
}

/* -------------------------------------------------------------------------- */
/* 5)  CONEXIÓN BD (por si las vistas la necesitan)                           */
/* -------------------------------------------------------------------------- */
$dbFile = "{$ROOT_DIR}/includes/db.php";
if (!is_readable($dbFile)) {
    respondError(500, 'Error interno: falta includes/db.php');
}
require_once $dbFile;
dbg('✔ Conexión BD incluida');

/* -------------------------------------------------------------------------- */
/* 6)  FUNCIÓN AUXILIAR: responder error sin romper el DOM                    */
/* -------------------------------------------------------------------------- */
function respondError(int $code, string $msg): void
{
    http_response_code($code);
    $prefersJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    if ($prefersJson) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    } else {
        // HTML simple (el contenedor .step-error se inyecta sin <html>)
        echo '<div class="step-error alert alert-danger m-3">' .
             htmlspecialchars($msg, ENT_QUOTES) .
             '</div>';
    }
    exit;
}

/* -------------------------------------------------------------------------- */
/* 7)  LEER Y VALIDAR “step”                                                  */
/* -------------------------------------------------------------------------- */
$step = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 6]
]) ?: 1;
dbg("📥 Paso solicitado: {$step}");

/* -------------------------------------------------------------------------- */
/* 8)  VALIDAR ESTADO WIZARD EN SESIÓN                                        */
/* -------------------------------------------------------------------------- */
if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    if ($step === 1) {
        $_SESSION['wizard_state']    = 'wizard';
        $_SESSION['wizard_progress'] = $_SESSION['wizard_progress'] ?? 1;
        session_regenerate_id(true);
        dbg('⚙️ Estado wizard inicializado');
    } else {
        respondError(403, 'Acceso prohibido: iniciá el wizard desde el paso 1.');
    }
}

$currentProgress = (int)($_SESSION['wizard_progress'] ?? 1);
$maxAllowedStep  = $currentProgress + 1;
if ($step > $maxAllowedStep) {
    dbg("🚫 Paso {$step} > permitido {$maxAllowedStep}");
    header("Location: load-step.php?step={$maxAllowedStep}");
    exit;
}
dbg("🔢 Progreso actual: {$currentProgress} (ok) — a servir step{$step}");

/* -------------------------------------------------------------------------- */
/* 9)  DETECTAR MODO (auto / manual)                                          */
/* -------------------------------------------------------------------------- */
$mode = ($_SESSION['tool_mode'] ?? 'manual') === 'auto' ? 'auto' : 'manual';
dbg("🧭 Modo: {$mode}");

/* -------------------------------------------------------------------------- */
/* 10) RESOLVER ARCHIVO DE VISTA                                              */
/* -------------------------------------------------------------------------- */
$viewBase = "{$ROOT_DIR}/views/steps";
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
    respondError(404, "Vista no encontrada para el paso {$step} (modo {$mode}).");
}
dbg("✔ View encontrada: {$viewPath}");

/* -------------------------------------------------------------------------- */
/* 11) DEFINIR CONSTANTE Y CARGAR VISTA EMBEBIDA                              */
/* -------------------------------------------------------------------------- */
define('WIZARD_EMBEDDED', true);
include $viewPath;

/* -------------------------------------------------------------------------- */
/* 12) FIN                                                                   */
/* -------------------------------------------------------------------------- */
// No se imprime nada extra aquí: la vista es responsable de su contenido.
