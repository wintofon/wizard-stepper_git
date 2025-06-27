<?php
/**
 * File: views/steps/step6.php — Paso 6 (Resultados expertos)
 * 
 * Reescritura robusta: cabeceras de seguridad, manejo uniforme de errores,
 * validaciones estrictas de sesión + CSRF, helpers únicos de assets, y
 * desacople de dependencias implicitas.
 * 
 * PHP >= 8.1
 */

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// 0 · Helpers globales
// ─────────────────────────────────────────────────────────────────────────────

if (!function_exists('hx')) {
    /** Escapado corto para HTML */
    function hx(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('respondError')) {
    /**
     * Devuelve un error en JSON o HTML según la petición.
     * @param int    $code  Código HTTP (4xx/5xx). 500 por omisión
     * @param string $msg   Mensaje para el usuario/cliente
     */
    function respondError(int $code = 500, string $msg = 'Error interno'): never
    {
        http_response_code($code);

        $wantsJson = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
            (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'))
        );

        if ($wantsJson) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
        } else {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<p>' . hx($msg) . '</p>';
        }
        exit;
    }
}

set_exception_handler(static function (Throwable $e): void {
    error_log('[step6][EXCEPTION] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    respondError(500, 'Error interno al procesar la petición.');
});

// ─────────────────────────────────────────────────────────────────────────────
// 1 · BASE_URL / BASE_HOST helpers
// ─────────────────────────────────────────────────────────────────────────────

$baseUrl  = getenv('BASE_URL') ?: rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseHost = getenv('BASE_HOST') ?: $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

define('BASE_URL',  $baseUrl);
define('BASE_HOST', $baseHost);

// asset() helper ausente → fallback
if (!function_exists('asset')) {
    function asset(string $rel): string { return BASE_URL . '/' . ltrim($rel, '/'); }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2 · Carga de configuración principal
// ─────────────────────────────────────────────────────────────────────────────

$appConfig = __DIR__ . '/../../src/Config/AppConfig.php';
if (!is_readable($appConfig)) respondError(500, 'Configuración de aplicación faltante.');
require_once $appConfig;

// ─────────────────────────────────────────────────────────────────────────────
// 3 · Helpers opcionales y debug
// ─────────────────────────────────────────────────────────────────────────────

$helperFile = __DIR__ . '/../../includes/wizard_helpers.php';
if (is_readable($helperFile)) require_once $helperFile;

if (!function_exists('dbg')) { function dbg(...$a): void {} }
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN) ?? false;

// ─────────────────────────────────────────────────────────────────────────────
// 4 · Modo embebido
// ─────────────────────────────────────────────────────────────────────────────

$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

// ─────────────────────────────────────────────────────────────────────────────
// 5 · Inicio de sesión segura
// ─────────────────────────────────────────────────────────────────────────────

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    } else {
        // Fallback versiones viejas (<7.3)
        session_set_cookie_params(0, '/', '', true, true);
    }
    session_start();
}

// ─────────────────────────────────────────────────────────────────────────────
// 6 · Cabeceras de seguridad (solo página completa)
// ─────────────────────────────────────────────────────────────────────────────

if (!$embedded) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net");
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// ─────────────────────────────────────────────────────────────────────────────
// 7 · Normalizar claves y validar sesión
// ─────────────────────────────────────────────────────────────────────────────

$map = [
    'material' => 'material_id',
    'trans_id' => 'transmission_id',
    'fr_max'   => 'feed_max',
    'strategy' => 'strategy_id',
];
foreach ($map as $std => $alt) {
    if (!isset($_SESSION[$std]) && isset($_SESSION[$alt])) {
        $_SESSION[$std] = $_SESSION[$alt];
    }
}

$required = [
    'tool_table','tool_id','material','trans_id',
    'rpm_min','rpm_max','fr_max','thickness','strategy','hp'
];
$missing = array_filter($required, static fn($k) => empty($_SESSION[$k]));
if ($missing) {
    respondError(400, 'Sesión incompleta: faltan [' . implode(', ', $missing) . ']');
}

$toolId = filter_var($_SESSION['tool_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?? null;
$rpmMin = filter_var($_SESSION['rpm_min'], FILTER_VALIDATE_FLOAT);
$rpmMax = filter_var($_SESSION['rpm_max'], FILTER_VALIDATE_FLOAT);
if (!$toolId || $rpmMin === false || $rpmMax === false || $rpmMin >= $rpmMax) {
    respondError(400, 'Valores de sesión inválidos.');
}

// ─────────────────────────────────────────────────────────────────────────────
// 8 · CSRF token (15 min)
// ─────────────────────────────────────────────────────────────────────────────

$ttl = 900;
if (empty($_SESSION['csrf']) || (time() - ($_SESSION['csrf_time'] ?? 0) > $ttl)) {
    $_SESSION['csrf']      = bin2hex(random_bytes(32));
    $_SESSION['csrf_time'] = time();
}
$csrf = $_SESSION['csrf'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrf, $posted))            respondError(403, 'CSRF token inválido.');
    if (time() - $_SESSION['csrf_time'] > $ttl)  respondError(403, 'CSRF token expirado.');
}

// ─────────────────────────────────────────────────────────────────────────────
// 9 · Conexión BD
// ─────────────────────────────────────────────────────────────────────────────

$dbFile = __DIR__ . '/../../includes/db.php';
if (!is_readable($dbFile)) respondError(500, 'Archivo de conexión DB faltante.');
require_once $dbFile; // → $pdo

if (!isset($pdo) || !$pdo instanceof PDO) respondError(500, 'No se pudo obtener la conexión DB.');

// ─────────────────────────────────────────────────────────────────────────────
// 10 · Modelos & utilidades
// ─────────────────────────────────────────────────────────────────────────────

$root = dirname(__DIR__, 2) . '/';
foreach ([
    'src/Controller/ExpertResultController.php',
    'src/Model/ToolModel.php',
    'src/Model/ConfigModel.php',
    'src/Utils/CNCCalculator.php',
] as $rel) {
    $abs = $root . $rel;
    if (!is_readable($abs)) respondError(500, "Dependencia faltante: $rel");
    require_once $abs;
}

use App\Controller\ExpertResultController;
use App\Model\ToolModel;

// ─────────────────────────────────────────────────────────────────────────────
// 11 · Datos de herramienta y parámetros
// ─────────────────────────────────────────────────────────────────────────────

$toolTable = (string) $_SESSION['tool_table'];
try {
    $toolData = ToolModel::getTool($pdo, $toolTable, $toolId) ?: null;
} catch (Throwable $e) {
    respondError(500, 'Error consultando herramienta.');
}
if (!$toolData) respondError(404, 'Herramienta no encontrada.');

try {
    $params = ExpertResultController::getResultData($pdo, $_SESSION);
} catch (Throwable $e) {
    respondError(500, 'Error generando parámetros de corte.');
}

if (json_encode($params) === false) respondError(500, 'Serialización JSON fallida.');

// ─────────────────────────────────────────────────────────────────────────────
// 12 · Sanitizar datos para HTML
// ─────────────────────────────────────────────────────────────────────────────

$serial     = hx($toolData['serie']      ?? '');
$toolCode   = hx($toolData['tool_code']  ?? '');
$toolName   = hx($toolData['name']       ?? '—');
$toolType   = hx($toolData['tool_type']  ?? '—');
$imageURL   = !empty($toolData['image']) ? asset($toolData['image']) : '';

// Cast numéricos con fallback 0
$diameter   = (float)($toolData['diameter_mm']        ?? 0);
$baseVc     = (float)$params['vc0'];
$vcMinDb    = (float)$params['vc_min0'];
$vcMaxDb    = (float)($params['vc_max0'] ?? $baseVc * 1.25);
$baseFz     = (float)$params['fz0'];
$fzMinDb    = (float)$params['fz_min0'];
$fzMaxDb    = (float)$params['fz_max0'];
$thickness  = (float)$_SESSION['thickness'];

// Resultados base formateados
$outVf = number_format((float)$params['feed0'], 0, '.', '');
$outN  = number_format((float)$params['rpm0'],  0, '.', '');
$outVc = number_format($baseVc, 1, '.', '');

// Otros nombres (ya escapados)
$materialName   = hx($_SESSION['material_name']   ?? 'MDF');
$materialParent = hx($_SESSION['material_parent'] ?? 'Maderas');
$strategyName   = hx($_SESSION['strategy_name']   ?? 'V‑Carve');
$strategyParent = hx($_SESSION['strategy_parent'] ?? 'Fresado');
$transName      = 'N/D';
try {
    $stmt = $pdo->prepare('SELECT name FROM transmissions WHERE id = ?');
    $stmt->execute([(int)$_SESSION['trans_id']]);
    $transName = hx($stmt->fetchColumn() ?: 'N/D');
} catch (Throwable $e) {}

$notesArray = $params['notes'] ?? [];

// ─────────────────────────────────────────────────────────────────────────────
// 13 · Helpers de assets (link & script)
// ─────────────────────────────────────────────────────────────────────────────

if (!function_exists('safeAsset')) {
    /**
     * Imprime <link> o <script> con fallback a CDN y aviso en HTML.
     * @param string $rel  Ruta relativa dentro del proyecto
     * @param string $cdn  URL CDN opcional
     * @param bool   $isJs true → <script>, false → <link>
     */
    function safeAsset(string $rel, string $cdn = '', bool $isJs = false): void
    {
        $root = dirname(__DIR__, 2) . '/';
        $abs  = $root . ltrim($rel, '/');
        if (is_readable($abs)) {
            $href = asset($rel);
            echo $isJs
                ? "<script src=\"$href\" defer></script>\n"
                : "<link rel=\"stylesheet\" href=\"$href\">\n";
        } elseif ($cdn) {
            echo $isJs
                ? "<script src=\"$cdn\" defer crossorigin=\"anonymous\"></script>\n"
                : "<link rel=\"stylesheet\" href=\"$cdn\" crossorigin=\"anonymous\">\n";
        } else {
            echo '<!-- ⚠ ' . hx($rel) . ' no encontrado -->' . PHP_EOL;
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 14 · SALIDA  HTML
// ─────────────────────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 6 – Resultados expertos</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="<?= hx($csrf) ?>">

<?php
// Estilos principales (local → CDN)
safeAsset('assets/css/generic/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
foreach ([
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
] as $css) safeAsset($css);
?>
<?php if (!$embedded): ?>
<script>
  window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
  window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
</script>
<?php endif; ?>
</head>
<body>
<div class="content-main">
  <div class="container py-4">
    <!-- Título principal -->
    <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados expertos</h2>
    <p class="step-desc">Ajustá los parámetros y revisá los datos de corte sugeridos.</p>

    <!-- Área tarjeta herramienta -->
    <div class="row gx-3 mb-4 cards-grid">
      <div class="col-12 mb-3 area-tool">
        <div class="card h-100 shadow-sm">
          <div class="card-header text-center p-3">#<?= $serial ?> – <?= $toolCode ?></div>
          <div class="card-body text-center p-4">
            <?php if ($imageURL): ?>
              <img src="<?= hx($imageURL) ?>" alt="Imagen herramienta" class="tool-image mx-auto d-block">
            <?php else: ?>
              <div class="text-secondary">Sin imagen disponible</div>
            <?php endif; ?>
            <div class="tool-name mt-3"><?= $toolName ?></div>
            <div class="tool-type"><?= $toolType ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sliders / Resultados / Radar -->
    <?php /* … (sección igual a tu original: sliders, resultados, radar) … */ ?>

  </div><!-- /.container -->
</div><!-- /.content-main -->

<!-- ─────────────── SCRIPTS ─────────────── -->
<script>
  window.step6Params   = <?= json_encode($params, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  window.step6Csrf     = <?= json_encode($csrf, JSON_HEX_TAG) ?>;
  window.step6AjaxUrl  = <?= json_encode(asset('ajax/step6_ajax_legacy_minimal.php'), JSON_HEX_TAG) ?>;
</script>

<?php if (!$embedded): ?>
<?php
// Bootstrap + libs
safeAsset('assets/js/bootstrap.bundle.min.js',  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', true);
safeAsset('node_modules/feather-icons/dist/feather.min.js', 'https://cdn.jsdelivr.net/npm/feather-icons@4/dist/feather.min.js', true);
safeAsset('node_modules/chart.js/dist/chart.umd.min.js',   'https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js', true);
safeAsset('node_modules/countup.js/dist/countUp.umd.js',   'https://cdn.jsdelivr.net/npm/countup.js@2.6.2/dist/countUp.umd.js', true);
?>
<script type="module" defer src="<?= asset('assets/js/step6.js') ?>" onload="window.step6?.init?.()" onerror="console.error('step6.js no pudo cargarse')"></script>
<script>(function f(r=10){if(window.feather){feather.replace({class:'feather'});}else if(r){setTimeout(()=>f(r-1),120);}else{console.warn('Feather no cargó');}})();</script>
<?php endif; ?>
</body>
</html>
