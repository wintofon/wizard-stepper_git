<?php
/**
 * File: views/steps/step6.php
 * Descripción: Paso 6 – Resultados expertos del Wizard CNC
 * Versión pulida: se corrigieron nombres de IDs, clases CSS, chequeos de constantes y algunas advertencias PHP.
 */

declare(strict_types=1);

if (!getenv('BASE_URL')) {
    // Sube 3 niveles: /views/steps/step6.php → /wizard-stepper_git
    putenv(
        'BASE_URL=' . rtrim(
            dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))),
            '/'
        )
    );
}
require_once __DIR__ . '/../../src/Config/AppConfig.php';

use App\Controller\ExpertResultController;

// ────────────────────────────────────────────────────────────────
// Utilidades / helpers
// ────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../../includes/wizard_helpers.php';

// ────────────────────────────────────────────────────────────────
// ¿Vista embebida por load-step.php?
// ────────────────────────────────────────────────────────────────
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;


if (!$embedded) {
    /* Cabeceras de seguridad */
    header('Content-Type: text/html; charset=UTF-8');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header("Permissions-Policy: geolocation=(), microphone=()");
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header(
        "Content-Security-Policy: default-src 'self';"
        . " script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;"
        . " style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;"
    );
}

// ────────────────────────────────────────────────────────────────
// Sesión segura
// ────────────────────────────────────────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// ────────────────────────────────────────────────────────────────
// Debug opcional
// ────────────────────────────────────────────────────────────────
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable(__DIR__ . '/../../includes/debug.php')) {
    require_once __DIR__ . '/../../includes/debug.php';
}

// ────────────────────────────────────────────────────────────────
// Conexión BD
// ────────────────────────────────────────────────────────────────
$dbFile = __DIR__ . '/../../includes/db.php';
if (!is_readable($dbFile)) {
    http_response_code(500);
    exit('Error interno: falta el archivo de conexión a la BD.');
}
require_once $dbFile;           //-> $pdo
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Error interno: no hay conexión a la base de datos.');
}

// ────────────────────────────────────────────────────────────────
// Normalizar nombres en sesión
// ────────────────────────────────────────────────────────────────
$_SESSION['material'] = $_SESSION['material_id']     ?? ($_SESSION['material']   ?? null);
$_SESSION['trans_id'] = $_SESSION['transmission_id'] ?? ($_SESSION['trans_id']   ?? null);
$_SESSION['fr_max']   = $_SESSION['feed_max']        ?? ($_SESSION['fr_max']     ?? null);
$_SESSION['strategy'] = $_SESSION['strategy_id']     ?? ($_SESSION['strategy']   ?? null);

// ────────────────────────────────────────────────────────────────
// CSRF token
// ────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('Error CSRF: petición no autorizada.');
    }
}

// ────────────────────────────────────────────────────────────────
// Validar claves requeridas
// ────────────────────────────────────────────────────────────────
$requiredKeys = [
    'tool_table','tool_id','material','trans_id',
    'rpm_min','rpm_max','fr_max','thickness',
    'strategy','hp'
];
$missing = array_filter($requiredKeys, fn($k) => empty($_SESSION[$k]));
if ($missing) {
    http_response_code(400);
    echo "<pre class='step6-error'>ERROR – faltan claves en sesión:\n" . implode(', ', $missing) . "</pre>";
    exit;
}

// ────────────────────────────────────────────────────────────────
// Cargar modelos y utilidades
// ────────────────────────────────────────────────────────────────
$root = dirname(__DIR__, 2) . '/';
foreach ([
    'src/Controller/ExpertResultController.php',
    'src/Model/ToolModel.php',
    'src/Model/ConfigModel.php',
    'src/Utils/CNCCalculator.php'
] as $rel) {
    if (!is_readable($root.$rel)) {
        http_response_code(500);
        exit("Error interno: falta {$rel}");
    }
    require_once $root.$rel;
}

// ────────────────────────────────────────────────────────────────
// Datos herramienta y parámetros base
// ────────────────────────────────────────────────────────────────
$toolTable = (string)$_SESSION['tool_table'];
$toolId    = (int)$_SESSION['tool_id'];
$toolData  = ToolModel::getTool($pdo, $toolTable, $toolId) ?: null;
if (!$toolData) {
    http_response_code(404);
    exit('Herramienta no encontrada.');
}

$params      = ExpertResultController::getResultData($pdo, $_SESSION);
$jsonParams  = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonParams === false) {
    http_response_code(500);
    exit('Error interno: no se pudo serializar parámetros técnicos.');
}

// ────────────────────────────────────────────────────────────────
// Variables de salida (HTML / JS)
// ────────────────────────────────────────────────────────────────
$serialNumber  = htmlspecialchars($toolData['serie']       ?? '', ENT_QUOTES);
$toolCode      = htmlspecialchars($toolData['tool_code']   ?? '', ENT_QUOTES);
$toolName      = htmlspecialchars($toolData['name']        ?? 'N/A', ENT_QUOTES);
$toolType      = htmlspecialchars($toolData['tool_type']   ?? 'N/A', ENT_QUOTES);
$imageURL      = !empty($toolData['image'])             ? asset($toolData['image'])             : '';
$vectorURL     = !empty($toolData['image_dimensions'])   ? asset($toolData['image_dimensions'])  : '';

$diameterMb    = (float)($toolData['diameter_mm']       ?? 0);
$shankMb       = (float)($toolData['shank_diameter_mm'] ?? 0);
$fluteLenMb    = (float)($toolData['flute_length_mm']   ?? 0);
$cutLenMb      = (float)($toolData['cut_length_mm']     ?? 0);
$fullLenMb     = (float)($toolData['full_length_mm']    ?? 0);
$fluteCountMb  = (int)  ($toolData['flute_count']        ?? 0);
$coatingMb     = htmlspecialchars($toolData['coated']    ?? 'N/A', ENT_QUOTES);
$materialMb    = htmlspecialchars($toolData['material']  ?? 'N/A', ENT_QUOTES);
$brandMb       = htmlspecialchars($toolData['brand']     ?? 'N/A', ENT_QUOTES);
$madeInMb      = htmlspecialchars($toolData['made_in']   ?? 'N/A', ENT_QUOTES);

$baseVc  = (float)$params['vc0'];
$vcMinDb = (float)$params['vc_min0'];
$vcMaxDb = (float)($params['vc_max0'] ?? $baseVc * 1.25);
$baseFz  = (float)$params['fz0'];
$fzMinDb = (float)$params['fz_min0'];
$fzMaxDb = (float)$params['fz_max0'];
$apSlot  = (float)$params['ap_slot'];
$aeSlot  = (float)$params['ae_slot'];
$rpmMin  = (float)$params['rpm_min'];
$rpmMax  = (float)$params['rpm_max'];
$frMax   = (float)$params['fr_max'];
$baseRpm = (int)  $params['rpm0'];
$baseFeed= (float)$params['feed0'];
$baseMmr = (float)$params['mmr_base'];

// Valores mostrados en el dash compacto
$outVf = number_format($baseFeed, 0, '.', '');
$outN  = number_format($baseRpm, 0, '.', '');
$outVc = number_format($baseVc,   1, '.', '');

$materialName   = (string)($_SESSION['material_name']   ?? 'Genérico Fibrofácil (MDF)');
$materialParent = (string)($_SESSION['material_parent'] ?? 'Maderas Naturales');
$strategyName   = (string)($_SESSION['strategy_name']   ?? 'Grabado en V / 2.5D');
$strategyParent = (string)($_SESSION['strategy_parent'] ?? 'Fresado');
$thickness      = (float)$_SESSION['thickness'];
$powerAvail     = (float)$_SESSION['hp'];

// Nombre de transmisión
try {
    $transName = $pdo->prepare('SELECT name FROM transmissions WHERE id = ?');
    $transName->execute([(int)$_SESSION['trans_id']]);
    $transName = $transName->fetchColumn() ?: 'N/D';
} catch (Throwable $e) {
    $transName = 'N/D';
}

$notesArray = $params['notes'] ?? [];

// ────────────────────────────────────────────────────────────────
// Assets locales / CDN fall-back
// ────────────────────────────────────────────────────────────────
$cssBootstrapRel = file_exists($root.'assets/css/generic/bootstrap.min.css') ? asset('assets/css/generic/bootstrap.min.css') : '';
$bootstrapJsRel  = file_exists($root.'assets/js/bootstrap.bundle.min.js') ? asset('assets/js/bootstrap.bundle.min.js') : '';
$featherLocal    = $root.'node_modules/feather-icons/dist/feather.min.js';
$chartJsLocal    = $root.'node_modules/chart.js/dist/chart.umd.min.js';
$countUpLocal    = $root.'node_modules/countup.js/dist/countUp.umd.js';
$step6JsRel      = file_exists($root.'assets/js/step6.js') ? asset('assets/js/step6.js') : '';

$assetErrors = [];
if (!$cssBootstrapRel)             $assetErrors[] = 'Bootstrap CSS no encontrado localmente.';
if (!$bootstrapJsRel)              $assetErrors[] = 'Bootstrap JS no encontrado localmente.';
if (!file_exists($featherLocal))   $assetErrors[] = 'Feather Icons JS faltante.';
if (!file_exists($chartJsLocal))   $assetErrors[] = 'Chart.js faltante.';
if (!file_exists($countUpLocal))   $assetErrors[] = 'CountUp.js faltante.';

// =====================================================================
// =========================  COMIENZA SALIDA  ==========================
// =====================================================================
?>
<?php if (!$embedded): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cutting Data – Paso&nbsp;6</title>
  <?php
    $bootstrapCss = $cssBootstrapRel ?: 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
    $styles = [
      $bootstrapCss,
    //  'assets/css/objects/step-common.css',
    //  'assets/css/objects/step6.css',
    //  'assets/css/components/main.css',
    ];
    $embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
    include __DIR__ . '/../partials/styles.php';
  ?>
  <?php if (!$embedded): ?>
  <script>
    window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
  </script>
  <?php endif; ?>
</head>
























</html>
<?php endif; ?>
