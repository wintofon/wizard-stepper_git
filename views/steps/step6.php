<?php
/**
 * Paso 6 – Resultados expertos del Wizard CNC
 * Reescrito con manejo robusto de errores mediante try/catch.
 */

declare(strict_types=1);

set_exception_handler(function(Throwable $e){
    error_log('[step6][EXCEPTION] '.$e->getMessage()."\n".$e->getTraceAsString());
    http_response_code(500);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['error'=>'Error interno al procesar parámetros']);
    } else {
        include __DIR__.'/../partials/error_500.php';
    }
    exit;
});

// ------------------------------------------------------------------
// 1. BASE_URL
// ------------------------------------------------------------------
if (!getenv('BASE_URL')) {
    $base = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
    putenv('BASE_URL=' . rtrim($base, '/'));
}

// ------------------------------------------------------------------
// 2. Carga de configuración principal
// ------------------------------------------------------------------
$appConfig = __DIR__ . '/../../src/Config/AppConfig.php';
if (!is_readable($appConfig)) {
    http_response_code(500);
    echo 'Error interno: configuración faltante';
    exit;
}
require_once $appConfig;

use App\Controller\ExpertResultController;

// ------------------------------------------------------------------
// 3. Helpers opcionales
// ------------------------------------------------------------------
$helperFile = __DIR__ . '/../../includes/wizard_helpers.php';
if (is_readable($helperFile)) {
    require_once $helperFile;
}
if (!function_exists('dbg')) {
    function dbg(...$a): void { /* no-op */ }
}

// ------------------------------------------------------------------
// 4. Modo embebido
// ------------------------------------------------------------------
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

// ------------------------------------------------------------------
// 5. Inicio de sesión
// ------------------------------------------------------------------
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

// ------------------------------------------------------------------
// 6. Cabeceras de seguridad
// ------------------------------------------------------------------
if (!$embedded) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('X-DNS-Prefetch-Control: off');
    header('Expect-CT: max-age=86400, enforce');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net");
}

// ------------------------------------------------------------------
// 7. Debug opcional
// ------------------------------------------------------------------
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable(__DIR__ . '/../../includes/debug.php')) {
    require_once __DIR__ . '/../../includes/debug.php';
}

// ------------------------------------------------------------------
// 8. Normalizar claves de sesión
// ------------------------------------------------------------------
$_SESSION['material'] = $_SESSION['material_id']     ?? ($_SESSION['material']   ?? null);
$_SESSION['trans_id'] = $_SESSION['transmission_id'] ?? ($_SESSION['trans_id']   ?? null);
$_SESSION['fr_max']   = $_SESSION['feed_max']        ?? ($_SESSION['fr_max']     ?? null);
$_SESSION['strategy'] = $_SESSION['strategy_id']     ?? ($_SESSION['strategy']   ?? null);

// Validaciones adicionales de sesión
$toolId = filter_var($_SESSION['tool_id'] ?? null, FILTER_VALIDATE_INT);
$frMax  = filter_var($_SESSION['fr_max'] ?? null, FILTER_VALIDATE_FLOAT);
$rpmMin = filter_var($_SESSION['rpm_min'] ?? null, FILTER_VALIDATE_FLOAT);
$rpmMax = filter_var($_SESSION['rpm_max'] ?? null, FILTER_VALIDATE_FLOAT);
if ($toolId === false || $toolId <= 0 ||
    $frMax === false || $frMax < 0 ||
    $rpmMin === false || $rpmMax === false || $rpmMin >= $rpmMax) {
    respondError(400, 'Parámetro inválido');
}

// ------------------------------------------------------------------
// 9. CSRF token
// ------------------------------------------------------------------
// Regenera token cada 15 minutos para mayor seguridad
$tokenTTL = 900; // segundos
$needsToken = empty($_SESSION['csrf_token']) ||
              empty($_SESSION['csrf_token_time']) ||
              ($_SESSION['csrf_token_time'] + $tokenTTL) < time();
if ($needsToken) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}
$csrfToken = $_SESSION['csrf_token'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrfToken, $posted)) {
        respondError(200, 'Error CSRF: petición no autorizada.');
    }
    if (($_SESSION['csrf_token_time'] + $tokenTTL) < time()) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        respondError(200, 'Error CSRF: token expirado.');
    }
}

// ------------------------------------------------------------------
// 10. Validación de sesión
// ------------------------------------------------------------------
$requiredKeys = [
    'tool_table','tool_id','material','trans_id',
    'rpm_min','rpm_max','fr_max','thickness','strategy','hp'
];
$missing = array_filter($requiredKeys, fn($k) => empty($_SESSION[$k]));
if ($missing) {
    error_log('[step6] Faltan claves: ' . implode(',', $missing));
    respondError(200, 'ERROR – faltan datos en sesión');
}

// ------------------------------------------------------------------
// 11. Conexión BD
// ------------------------------------------------------------------
$dbFile = __DIR__ . '/../../includes/db.php';
if (!is_readable($dbFile)) {
    respondError(200, 'Error interno: falta el archivo de conexión a la BD.');
}
try {
    require_once $dbFile; // -> $pdo
} catch (\Throwable $e) {
    respondError(200, 'Error interno: fallo al incluir la BD.');
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
    respondError(200, 'Error interno: no hay conexión a la base de datos.');
}

// ------------------------------------------------------------------
// 12. Cargar modelos y utilidades
// ------------------------------------------------------------------
$root = dirname(__DIR__, 2) . '/';
foreach ([
    'src/Controller/ExpertResultController.php',
    'src/Model/ToolModel.php',
    'src/Model/ConfigModel.php',
    'src/Utils/CNCCalculator.php'
] as $rel) {
    if (!is_readable($root.$rel)) {
        respondError(200, "Error interno: falta {$rel}");
    }
    require_once $root.$rel;
}

// ------------------------------------------------------------------
// 13. Obtener datos de herramienta y parámetros
// ------------------------------------------------------------------
$toolTable = (string)$_SESSION['tool_table'];
$toolId    = (int)$_SESSION['tool_id'];
try {
    $toolData  = ToolModel::getTool($pdo, $toolTable, $toolId) ?: null;
} catch (\Throwable $e) {
    respondError(200, 'Error al consultar herramienta.');
}
if (!$toolData) {
    respondError(200, 'Herramienta no encontrada.');
}

try {
    $params = ExpertResultController::getResultData($pdo, $_SESSION);
} catch (\Throwable $e) {
    respondError(200, 'Error al generar datos de corte.');
}
$jsonParams = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonParams === false) {
    respondError(200, 'Error interno: no se pudo serializar parámetros técnicos.');
}

// ------------------------------------------------------------------
// 14. Variables sanitizadas para HTML
// ------------------------------------------------------------------
$serialNumber  = htmlspecialchars($toolData['serie']       ?? '', ENT_QUOTES);
$toolCode      = htmlspecialchars($toolData['tool_code']   ?? '', ENT_QUOTES);
$toolName      = htmlspecialchars($toolData['name']        ?? 'N/A', ENT_QUOTES);
$toolType      = htmlspecialchars($toolData['tool_type']   ?? 'N/A', ENT_QUOTES);
$imageURL      = !empty($toolData['image'])             ? asset($toolData['image'])            : '';
$vectorURL     = !empty($toolData['image_dimensions'])   ? asset($toolData['image_dimensions']) : '';

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

$outVf = number_format($baseFeed, 0, '.', '');
$outN  = number_format($baseRpm, 0, '.', '');
$outVc = number_format($baseVc,   1, '.', '');

$materialName   = (string)($_SESSION['material_name']   ?? 'Genérico Fibrofácil (MDF)');
$materialParent = (string)($_SESSION['material_parent'] ?? 'Maderas Naturales');
$strategyName   = (string)($_SESSION['strategy_name']   ?? 'Grabado en V / 2.5D');
$strategyParent = (string)($_SESSION['strategy_parent'] ?? 'Fresado');
$thickness      = (float)$_SESSION['thickness'];
$powerAvail     = (float)$_SESSION['hp'];

try {
    $transName = $pdo->prepare('SELECT name FROM transmissions WHERE id = ?');
    $transName->execute([(int)$_SESSION['trans_id']]);
    $transName = $transName->fetchColumn() ?: 'N/D';
} catch (\Throwable $e) {
    $transName = 'N/D';
}

$notesArray = $params['notes'] ?? [];

// ------------------------------------------------------------------
// 15. Assets locales
// ------------------------------------------------------------------
$cssBootstrapRel = asset('assets/css/generic/bootstrap.min.css');
$bootstrapJsRel  = asset('assets/js/bootstrap.bundle.min.js');
$featherLocal    = $root.'node_modules/feather-icons/dist/feather.min.js';
$chartJsLocal    = $root.'node_modules/chart.js/dist/chart.umd.min.js';
$countUpLocal    = $root.'node_modules/countup.js/dist/countUp.umd.js';
$step6JsRel      = asset('assets/js/step6.js');

$assetErrors = [];
if (!is_readable($root.'assets/css/generic/bootstrap.min.css'))
    $assetErrors[] = 'Bootstrap CSS no encontrado localmente.';
if (!is_readable($root.'assets/js/bootstrap.bundle.min.js'))
    $assetErrors[] = 'Bootstrap JS no encontrado localmente.';
if (!file_exists($featherLocal))
    $assetErrors[] = 'Feather Icons JS faltante.';
if (!file_exists($chartJsLocal))
    $assetErrors[] = 'Chart.js faltante.';
if (!file_exists($countUpLocal))
    $assetErrors[] = 'CountUp.js faltante.';

// Render with Twig
$styles = [
    $cssBootstrapRel,
    'assets/css/settings/settings.css',
    'assets/css/generic/generic.css',
    'assets/css/elements/elements.css',
    'assets/css/objects/objects.css',
    'assets/css/objects/wizard.css',
    'assets/css/objects/stepper.css',
    'assets/css/objects/step-common.css',
    'assets/css/objects/step6.css',
    'assets/css/components/components.css',
    'assets/css/components/main.css',
    'assets/css/components/footer-schneider.css',
    'assets/css/utilities/utilities.css',
];
ob_start();
include __DIR__ . '/../partials/styles.php';
$stylesHtml = ob_get_clean();

$loader = new \Twig\Loader\FilesystemLoader(__DIR__);
$twig = new \Twig\Environment($loader, ['autoescape' => 'html']);

$context = [
    'embedded'        => $embedded,
    'stylesHtml'      => $stylesHtml,
    'assetErrors'     => $assetErrors,
    'serialNumber'    => $serialNumber,
    'toolCode'        => $toolCode,
    'imageURL'        => $imageURL,
    'toolName'        => $toolName,
    'toolType'        => $toolType,
    'fzMinDb'         => number_format($fzMinDb,4,'.',''),
    'fzMaxDb'         => number_format($fzMaxDb,4,'.',''),
    'baseFz'          => number_format($baseFz,4,'.',''),
    'vcMinDb'         => number_format($vcMinDb,1,'.',''),
    'vcMaxDb'         => number_format($vcMaxDb,1,'.',''),
    'baseVc'          => number_format($baseVc,1,'.',''),
    'diameterMb'      => number_format($diameterMb,1,'.',''),
    'aeDefault'       => number_format($diameterMb * 0.5,1,'.',''),
    'thickness'       => number_format($thickness,2),
    'outVf'           => $outVf,
    'outN'            => $outN,
    'outVc'           => $outVc,
    'shankMb'         => $shankMb,
    'cutLenMb'        => $cutLenMb,
    'fluteLenMb'      => $fluteLenMb,
    'fullLenMb'       => $fullLenMb,
    'fluteCountMb'    => $fluteCountMb,
    'coatingMb'       => $coatingMb,
    'materialMb'      => $materialMb,
    'brandMb'         => $brandMb,
    'madeInMb'        => $madeInMb,
    'vectorURL'       => $vectorURL,
    'materialParent'  => $materialParent,
    'materialName'    => $materialName,
    'strategyParent'  => $strategyParent,
    'strategyName'    => $strategyName,
    'frMax'           => $frMax,
    'rpmMin'          => $rpmMin,
    'rpmMax'          => $rpmMax,
    'powerAvail'      => $powerAvail,
    'transName'       => $transName,
    'notesArray'      => $notesArray,
    'jsonParams'      => $jsonParams,
    'csrfToken'       => $csrfToken,
    'bootstrapJsRel'  => $bootstrapJsRel,
    'featherJs'       => asset('node_modules/feather-icons/dist/feather.min.js'),
    'chartJs'         => asset('node_modules/chart.js/dist/chart.umd.min.js'),
    'countUpJs'       => asset('node_modules/countup.js/dist/countUp.umd.js'),
    'step6JsRel'      => $step6JsRel,
    'BASE_URL'        => BASE_URL,
    'BASE_HOST'       => BASE_HOST,
];

echo $twig->render('step6.twig', $context);
return;
