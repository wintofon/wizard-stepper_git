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
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net");
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
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 5 – Configurá tu router</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
<?php
/* ─── Helper: <link> seguro ───────────────────────────────────────────── */
function safeStyle(string $local, string $cdn = ''): void
{
    $root = dirname(__DIR__, 2) . '/';              // raíz del proyecto
    $abs  = $root . ltrim($local, '/');             // ruta absoluta

    if (is_readable($abs)) {
        // OK local
        echo '<link rel="stylesheet" href="' . asset($local) . '">' . PHP_EOL;
    } elseif ($cdn) {
        // Fallback CDN
        echo '<link rel="stylesheet" href="' . $cdn . '" crossorigin="anonymous">' . PHP_EOL;
    } else {
        // Sin recurso: no se rompe, solo avisa en HTML
        echo '<!-- ⚠️  ' . htmlspecialchars($local) . ' no encontrado -->' . PHP_EOL;
    }
}

/* ─── Lista de hojas de estilo (local + opcional CDN) ──────────────────── */
$styles = [
    // [local, cdn]
    [$cssBootstrapRel, 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'],
    ['assets/css/settings/settings.css',          ''],
    ['assets/css/generic/generic.css',            ''],
    ['assets/css/elements/elements.css',          ''],
    ['assets/css/objects/objects.css',            ''],
    ['assets/css/objects/wizard.css',             ''],
    ['assets/css/objects/stepper.css',            ''],
    ['assets/css/objects/step-common.css',        ''],
    ['assets/css/objects/step6.css',              ''],
    ['assets/css/components/components.css',      ''],
    ['assets/css/components/main.css',            ''],
    ['assets/css/components/footer-schneider.css',''],
    ['assets/css/utilities/utilities.css',        ''],
];

/* ─── Render de <link> blindados ──────────────────────────────────────── */
foreach ($styles as [$local, $cdn]) {
    safeStyle($local, $cdn);
}
?>

<?php if (!$embedded): ?>
<script>
  window.BASE_URL = <?= json_encode(BASE_URL) ?>;
  window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
</script>
<?php endif; ?>
</head><body>
<div class="content-main">
  <div class="container py-4">
    <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
    <p class="step-desc">Ajustá los parámetros y revisá los datos de corte.</p>
  <!-- BLOQUE CENTRAL -->
  <div class="row gx-3 mb-4 cards-grid">
    <div class="col-12 mb-3 area-tool">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3">
          <span>#<?= $serialNumber ?> – <?= $toolCode ?></span>
        </div>
        <div class="card-body text-center p-4">
          <?php if ($imageURL): ?>
            <img src="<?= htmlspecialchars($imageURL, ENT_QUOTES) ?>"
                 alt="Imagen principal herramienta"
                 class="tool-image mx-auto d-block">
          <?php else: ?>
            <div class="text-secondary">Sin imagen disponible</div>
          <?php endif; ?>
          <div class="tool-name mt-3"><?= $toolName ?></div>
          <div class="tool-type"><?= $toolType ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- AJUSTES / RESULTADOS / RADAR -->
  <div class="row gx-3 mb-4 cards-grid">
    <!-- Ajustes -->
    <div class="col-12 col-lg-4 mb-3 area-sliders">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Ajustes</h5></div>
        <div class="card-body p-4">
          <!-- fz -->
          <div class="mb-4 px-2">
            <label for="sliderFz" class="form-label">fz (mm/tooth)</label>
            <div class="slider-wrap">
              <input type="range" id="sliderFz" class="form-range"
                     min="<?= number_format($fzMinDb,4,'.','') ?>"
                     max="<?= number_format($fzMaxDb,4,'.','') ?>"
                     step="0.0001"
                     value="<?= number_format($baseFz,4,'.','') ?>">
              <span class="slider-bubble"></span>
            </div>
            <div class="text-end small text-secondary mt-1">
              <span><?= number_format($fzMinDb,4,'.','') ?></span> –
              <strong id="valFz"><?= number_format($baseFz,4,'.','') ?></strong> –
              <span><?= number_format($fzMaxDb,4,'.','') ?></span>
            </div>
          </div>
          <!-- Vc -->
          <div class="mb-4 px-2">
            <label for="sliderVc" class="form-label">Vc (m/min)</label>
            <div class="slider-wrap">
              <input type="range" id="sliderVc" class="form-range"
                     min="<?= number_format($vcMinDb,1,'.','') ?>"
                     max="<?= number_format($vcMaxDb,1,'.','') ?>"
                     step="0.1"
                     value="<?= number_format($baseVc,1,'.','') ?>">
              <span class="slider-bubble"></span>
            </div>
            <div class="text-end small text-secondary mt-1">
              <span><?= number_format($vcMinDb,1,'.','') ?></span> –
              <strong id="valVc"><?= number_format($baseVc,1,'.','') ?></strong> –
              <span><?= number_format($vcMaxDb,1,'.','') ?></span>
            </div>
          </div>
          <!-- ae -->
          <div class="mb-4 px-2">
            <label for="sliderAe" class="form-label">
              ae (mm) <small>(ancho de pasada)</small>
            </label>
            <div class="slider-wrap">
              <input type="range" id="sliderAe" class="form-range"
                     min="0.1"
                     max="<?= number_format($diameterMb,1,'.','') ?>"
                     step="0.1"
                     value="<?= number_format($diameterMb * 0.5,1,'.','') ?>">
              <span class="slider-bubble"></span>
            </div>
            <div class="text-end small text-secondary mt-1">
              <span>0.1</span> –
              <strong id="valAe"><?= number_format($diameterMb * 0.5,1,'.','') ?></strong> –
              <span><?= number_format($diameterMb,1,'.','') ?></span>
            </div>
          </div>
          <!-- Pasadas -->
          <div class="mb-4 px-2">
            <label for="sliderPasadas" class="form-label">Pasadas</label>
            <div class="slider-wrap">
              <input type="range" id="sliderPasadas" class="form-range"
                     min="1" max="1" step="1"
                     value="1"
                     data-thickness="<?= htmlspecialchars((string)$thickness, ENT_QUOTES) ?>">
              <span class="slider-bubble"></span>
            </div>
            <div id="textPasadasInfo" class="small text-secondary mt-1">
              1 pasada de <?= number_format($thickness, 2) ?> mm
            </div>
            <div id="errorMsg" class="text-danger mt-2 small"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resultados -->
    <div class="col-12 col-lg-4 mb-3 area-results">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Resultados</h5></div>
        <div class="card-body p-4">
          <div class="results-compact mb-4 d-flex gap-2">
            <div class="result-box text-center flex-fill">
              <div class="param-label">
                Feedrate<br><small>(<span class="param-unit">mm/min</span>)</small>
              </div>
              <div id="outVf" class="fw-bold display-6"><?= $outVf ?></div>
            </div>
            <div class="result-box text-center flex-fill">
              <div class="param-label">
                Cutting speed<br><small>(<span class="param-unit">RPM</span>)</small>
              </div>
              <div id="outN" class="fw-bold display-6"><?= $outN ?></div>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>Vc</small>
            <div><span id="outVc" class="fw-bold"><?= $outVc ?></span> <span class="param-unit">m/min</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>fz</small>
            <div><span id="outFz" class="fw-bold">--</span> <span class="param-unit">mm/tooth</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>Ap</small>
            <div><span id="outAp" class="fw-bold">--</span> <span class="param-unit">mm</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>Ae</small>
            <div><span id="outAe" class="fw-bold">--</span> <span class="param-unit">mm</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <small>hm</small>
            <div><span id="outHm" class="fw-bold">--</span> <span class="param-unit">mm</span></div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <small>Hp</small>
            <div><span id="outHp" class="fw-bold">--</span> <span class="param-unit">HP</span></div>
          </div>
          <!-- Métricas secundarias -->
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              MMR<br><small>(<span class="param-unit">mm³/min</span>)</small>
            </div>
            <div id="valueMrr" class="fw-bold">--</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              Fc<br><small>(<span class="param-unit">N</span>)</small>
            </div>
            <div id="valueFc" class="fw-bold">--</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              Potencia<br><small>(<span class="param-unit">W</span>)</small>
            </div>
            <div id="valueW" class="fw-bold">--</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="param-label">
              η<br><small>(<span class="param-unit">%</span>)</small>
            </div>
            <div id="valueEta" class="fw-bold">--</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Radar Chart -->
    <div class="col-12 col-lg-4 mb-3 area-radar">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Distribución Radar</h5></div>
        <div class="card-body p-4 d-flex justify-content-center align-items-center">
          <canvas id="radarChart" width="300" height="300"></canvas>
        </div>
      </div>
    </div>
  </div>


<!-- ===============================================================
     ESPECIFICACIONES  ·  CONFIGURACIÓN ·  NOTAS
     (tres tarjetas alineadas, idéntico formato de “Configuración
     de Usuario” para la sección Especificaciones Técnicas)
=============================================================== -->
<div class="row gx-3 mb-4 cards-grid"><!-- grilla flex→grid -->

  <!--─────────────────── 1) ESPECIFICACIONES TÉCNICAS ──────────────────-->
  <div class="col-12 col-lg-4 mb-3 area-specs">
    <div class="card h-100 shadow-sm">
      <div class="card-header text-center p-3"
           data-bs-toggle="collapse"
           data-bs-target="#specCollapse"
           aria-expanded="true">
        <h5 class="mb-0">Especificaciones Técnicas</h5>
      </div>

      <div id="specCollapse" class="collapse show">
        <div class="card-body p-4">

          <!-- ===== Filos ===== -->
          <div class="config-section mb-3">
            <div class="config-section-title">Filos</div>
            <div class="config-item">
              <div class="label-static">Número de filos (Z):</div>
              <div class="value-static"><?= $fluteCountMb ?></div>
            </div>
          </div>

          <div class="section-divider"></div>

          <!-- ===== Diámetros ===== -->
          <div class="config-section mb-3">
            <div class="config-section-title">Diámetros</div>

            <div class="config-item">
              <div class="label-static">Diám. de corte (d1):</div>
              <div class="value-static">
                <?= number_format($diameterMb,3,'.','') ?> <span class="param-unit">mm</span>
              </div>
            </div>

            <div class="config-item">
              <div class="label-static">Diám. del vástago:</div>
              <div class="value-static">
                <?= number_format($shankMb,3,'.','') ?> <span class="param-unit">mm</span>
              </div>
            </div>

            <div class="config-item">
              <div class="label-static">Tipo de punta:</div>
              <div class="value-static"><?= $toolType ?></div>
            </div>
          </div>

          <div class="section-divider"></div>

          <!-- ===== Longitudes ===== -->
          <div class="config-section mb-3">
            <div class="config-section-title">Longitudes</div>

            <div class="config-item">
              <div class="label-static">Longitud de corte:</div>
              <div class="value-static">
                <?= number_format($cutLenMb,3,'.','') ?> <span class="param-unit">mm</span>
              </div>
            </div>

            <div class="config-item">
              <div class="label-static">Longitud de filo:</div>
              <div class="value-static">
                <?= number_format($fluteLenMb,3,'.','') ?> <span class="param-unit">mm</span>
              </div>
            </div>

            <div class="config-item">
              <div class="label-static">Longitud total:</div>
              <div class="value-static">
                <?= number_format($fullLenMb,3,'.','') ?> <span class="param-unit">mm</span>
              </div>
            </div>
          </div>

          <div class="section-divider"></div>

          <!-- ===== Composición ===== -->
          <div class="config-section">
            <div class="config-section-title">Composición</div>

            <div class="config-item">
              <div class="label-static">Material fabricación:</div>
              <div class="value-static"><?= $materialMb ?></div>
            </div>

            <div class="config-item">
              <div class="label-static">Recubrimiento:</div>
              <div class="value-static"><?= $coatingMb ?></div>
            </div>

            <div class="config-item">
              <div class="label-static">Marca:</div>
              <div class="value-static"><?= $brandMb ?></div>
            </div>

            <div class="config-item">
              <div class="label-static">País de origen:</div>
              <div class="value-static"><?= $madeInMb ?></div>
            </div>
          </div>

        </div><!-- /.card-body -->
      </div><!-- /.collapse -->
    </div><!-- /.card -->
  </div><!-- /.area-specs -->


  <!--─────────────────── 2) CONFIGURACIÓN DE USUARIO ──────────────────-->
  <div class="col-12 col-lg-4 mb-3 area-config">
    <div class="card h-100 shadow-sm">
      <div class="card-header text-center p-3"
           data-bs-toggle="collapse"
           data-bs-target="#configCollapse"
           aria-expanded="true">
        <h5 class="mb-0">Configuración de Usuario</h5>
      </div>

      <div id="configCollapse" class="collapse show">
        <div class="card-body p-4">

          <div class="config-section mb-3">
            <div class="config-section-title">Material</div>
            <div class="config-item">
              <div class="label-static">Categoría padre:</div>
              <div class="value-static"><?= $materialParent ?></div>
            </div>
            <div class="config-item">
              <div class="label-static">Material a mecanizar:</div>
              <div class="value-static"><?= $materialName ?></div>
            </div>
          </div>

          <div class="section-divider"></div>

          <div class="config-section mb-3">
            <div class="config-section-title">Estrategia</div>
            <div class="config-item">
              <div class="label-static">Categoría padre estr.:</div>
              <div class="value-static"><?= $strategyParent ?></div>
            </div>
            <div class="config-item">
              <div class="label-static">Estrategia de corte:</div>
              <div class="value-static"><?= $strategyName ?></div>
            </div>
          </div>

          <div class="section-divider"></div>

          <div class="config-section">
            <div class="config-section-title">Máquina</div>
            <div class="config-item">
              <div class="label-static">Espesor del material:</div>
              <div class="value-static"><?= number_format($thickness,2) ?> <span class="param-unit">mm</span></div>
            </div>
            <div class="config-item">
              <div class="label-static">Tipo de transmisión:</div>
              <div class="value-static"><?= $transName ?></div>
            </div>
            <div class="config-item">
              <div class="label-static">Feedrate máximo:</div>
              <div class="value-static"><?= number_format($frMax,0) ?> <span class="param-unit">mm/min</span></div>
            </div>
            <div class="config-item">
              <div class="label-static">RPM mínima:</div>
              <div class="value-static"><?= number_format($rpmMin,0) ?> <span class="param-unit">rev/min</span></div>
            </div>
            <div class="config-item">
              <div class="label-static">RPM máxima:</div>
              <div class="value-static"><?= number_format($rpmMax,0) ?> <span class="param-unit">rev/min</span></div>
            </div>
            <div class="config-item">
              <div class="label-static">Potencia disponible:</div>
              <div class="value-static"><?= number_format($powerAvail,1) ?> <span class="param-unit">HP</span></div>
            </div>
          </div>

        </div><!-- /.card-body -->
      </div><!-- /.collapse -->
    </div><!-- /.card -->
  </div><!-- /.area-config -->


  <!--─────────────────── 3) NOTAS ADICIONALES ──────────────────-->
  <div class="col-12 col-lg-4 mb-3 area-notes">
    <div class="card h-100 shadow-sm">
      <div class="card-header text-center p-3">
        <h5 class="mb-0">Notas Adicionales</h5>
      </div>

      <div class="card-body p-4">
        <?php if ($notesArray): ?>
          <ul class="notes-list mb-0">
            <?php foreach ($notesArray as $note): ?>
              <li class="d-flex align-items-start mb-2">
                <i data-feather="file-text" class="me-2"></i>
                <div><?= htmlspecialchars($note, ENT_QUOTES) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-secondary">No hay notas adicionales para esta herramienta.</div>
        <?php endif; ?>
      </div>
    </div><!-- /.card -->
  </div><!-- /.area-notes -->

</div><!-- /.cards-grid -->


</div><!-- .content-main -->
<!-- ========== SCRIPTS (blindados) ========== -->
<script>
  /*-- Parámetros técnicos + CSRF (100 % seguro) --*/
  window.step6Params = <?= json_encode(
        $params,
        JSON_UNESCAPED_UNICODE |
        JSON_HEX_TAG  | JSON_HEX_AMP |
        JSON_HEX_APOS | JSON_HEX_QUOT
  ) ?>;
  window.step6Csrf   = <?= json_encode($csrfToken, JSON_HEX_TAG) ?>;
  window.step6AjaxUrl = <?= json_encode(asset('ajax/step6_ajax_legacy_minimal.php'), JSON_HEX_TAG) ?>;
</script>

<?php if (!$embedded): ?>

<?php
/*-----------------------------------------------------------------
 *  Función helper: inyecta <script defer> local ⇢ o CDN fallback
 *----------------------------------------------------------------*/
function safeScript(string $local, string $cdn = ''): void
{
    $root = dirname(__DIR__, 2) . '/';
    $path = $root . ltrim($local, '/');

    if (is_readable($path)) {
        echo '<script src="' . asset($local) . '" defer></script>' . PHP_EOL;
    } elseif ($cdn) {
        echo '<script src="' . $cdn . '" defer crossorigin="anonymous"></script>' . PHP_EOL;
    } else {
        echo '<!-- ⚠️  ' . htmlspecialchars($local) . ' no encontrado -->' . PHP_EOL;
    }
}

/*-----------------------------------------------------------------
 *  1) Bootstrap 5 bundle (local o CDN)
 *----------------------------------------------------------------*/
safeScript(
    'assets/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'
);

/*-----------------------------------------------------------------
 *  2) Feather-icons
 *----------------------------------------------------------------*/
safeScript(
    'node_modules/feather-icons/dist/feather.min.js',
    'https://cdn.jsdelivr.net/npm/feather-icons@4/dist/feather.min.js'
);

/*-----------------------------------------------------------------
 *  3) Chart.js
 *----------------------------------------------------------------*/
safeScript(
    'node_modules/chart.js/dist/chart.umd.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js'
);

/*-----------------------------------------------------------------
 *  4) CountUp.js
 *----------------------------------------------------------------*/
safeScript(
    'node_modules/countup.js/dist/countUp.umd.js',
    'https://cdn.jsdelivr.net/npm/countup.js@2.6.2/dist/countUp.umd.js'
);

/*-----------------------------------------------------------------
 *  5) Tu propio step6.js (ahora ES module, solo local; sin CDN)
 *----------------------------------------------------------------*/

?>
<!-- views/steps/step6.php  ── al final, justo antes de </body> -->

<!-- Script principal del paso 6  -->

<script 
  type="module" 
  defer 
  src="<?= asset('assets/js/step6.js') ?>"
  onload="console.info('[step6] module loaded 👍'); window.step6?.init?.();"
  onerror="console.error('❌ step6.js failed to load');">
</script>

<script>
/*-- Feather.replace() seguro: reintenta 10× cada 120 ms --*/
(function waitFeather(r = 10) {
  if (window.feather && typeof feather.replace === 'function') {
    requestAnimationFrame(() => feather.replace({ class: 'feather' }));
  } else if (r) {
    setTimeout(() => waitFeather(r - 1), 120);
  } else {
    console.warn('⚠️  Feather Icons no cargó: se omite replace()');
  }
})();
</script>

<?php endif; ?>



</body></html>
