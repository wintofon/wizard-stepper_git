<?php
/**
 * File: views/steps/step6.php
 * Descripción: Paso 6 – Resultados expertos del Wizard CNC
 *
 * 🔧 Ajustes clave (2025‑07‑13):
 *   1. En modo embebido sólo se imprime el <div class="step6"> y el script
 *      window.step6Params.
 *   2. Doctype, <html>, <head>, <body>, footer y parciales se encierran en
 *      `if (!$embedded)` para no contaminar el DOM.
 *   3. Los <script src> externos se cargan una única vez desde wizard_stepper.js.
 *   4. feather.replace() se ejecuta mediante requestAnimationFrame una sola vez.
 *   5. Cualquier parcial adicional debe sumarse al mismo condicional.
 *   6. Se eliminaron líneas en blanco extra para conservar el minidiff.
 *
 * 👉 Si necesitás debuggear, usá ?debug=1 en la URL y se activan trazas extra.
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

// ────────────────────────────────────────────────────────────────
// Sesión segura (siempre antes de imprimir cabeceras)
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
// Debug opcional
// ────────────────────────────────────────────────────────────────
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable(__DIR__ . '/../../includes/debug.php')) {
    require_once __DIR__ . '/../../includes/debug.php';
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
        respondError(200, 'Error CSRF: petición no autorizada.');
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
    respondError(200, 'ERROR – faltan claves en sesión: ' . implode(', ', $missing));
}

// ────────────────────────────────────────────────────────────────
// Conexión BD
// ────────────────────────────────────────────────────────────────
$dbFile = __DIR__ . '/../../includes/db.php';
if (!is_readable($dbFile)) {
    respondError(200, 'Error interno: falta el archivo de conexión a la BD.');
}
require_once $dbFile;           //-> $pdo
if (!isset($pdo) || !($pdo instanceof PDO)) {
    respondError(200, 'Error interno: no hay conexión a la base de datos.');
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
        respondError(200, "Error interno: falta {$rel}");
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
    respondError(200, 'Herramienta no encontrada.');
}

$params     = ExpertResultController::getResultData($pdo, $_SESSION);
$jsonParams = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonParams === false) {
    respondError(200, 'Error interno: no se pudo serializar parámetros técnicos.');
}

// ────────────────────────────────────────────────────────────────
// Variables de salida (HTML / JS)
// ────────────────────────────────────────────────────────────────
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
// Assets locales
// ────────────────────────────────────────────────────────────────
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
    include __DIR__ . '/../partials/styles.php';
  ?>
  <script>
    window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
  </script>
</head>
<body>
<?php endif; ?>

<?php if ($assetErrors): ?>
  <div class="alert alert-warning text-dark m-3">
    <strong>⚠️ Archivos faltantes (se usarán CDNs):</strong>
    <ul>
      <?php foreach ($assetErrors as $err): ?>
        <li><?= htmlspecialchars($err, ENT_QUOTES) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="step6">
<div class="content-main">
  <div class="container py-4">
    <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
    <p class="step-desc">Ajustá los parámetros y revisá los datos de corte.</p>
  <!-- BLOQUE CENTRAL -->
  <div class="row gx-3 mb-4 cards-grid">
    <div class="col-12 col-lg-4 mb-3 area-tool">
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

  <!-- ESPECIFICACIONES / CONFIGURACIÓN / NOTAS -->
  <div class="row gx-3 mb-4 cards-grid">
    <!-- Especificaciones -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"
             data-bs-toggle="collapse"
             data-bs-target="#specCollapse"
             aria-expanded="true">
          <h5 class="mb-0">Especificaciones Técnicas</h5>
        </div>
        <div id="specCollapse" class="collapse show">
          <div class="card-body p-4">
            <div class="row gx-0 align-items-center">
              <div class="col-12 col-lg-7 px-2 mb-4 mb-lg-0">
                <ul class="spec-list mb-0 px-2">
                  <li><span>Diámetro de corte (d1):</span>
                      <span><?= number_format($diameterMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Diámetro del vástago:</span>
                      <span><?= number_format($shankMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Longitud de corte:</span>
                      <span><?= number_format($cutLenMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Longitud de filo:</span>
                      <span><?= number_format($fluteLenMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Longitud total:</span>
                      <span><?= number_format($fullLenMb,3,'.','') ?>
                      <span class="param-unit">mm</span>
                      </span>
                  </li>
                  <li><span>Número de filos (Z):</span><span><?= $fluteCountMb ?></span></li>
                  <li><span>Tipo de punta:</span><span><?= $toolType ?></span></li>
                  <li><span>Recubrimiento:</span><span><?= $coatingMb ?></span></li>
                  <li><span>Material fabricación:</span><span><?= $materialMb ?></span></li>
                  <li><span>Marca:</span><span><?= $brandMb ?></span></li>
                  <li><span>País de origen:</span><span><?= $madeInMb ?></span></li>
                </ul>
              </div>
              <div class="col-12 col-lg-5 px-2 d-flex justify-content-center align-items-center">
                <?php if ($vectorURL): ?>
                  <img src="<?= htmlspecialchars($vectorURL, ENT_QUOTES) ?>"
                       alt="Imagen vectorial herramienta"
                       class="vector-image mx-auto d-block">
                <?php else: ?>
                  <div class="text-secondary">Sin imagen vectorial</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Configuración -->
    <div class="col-12 col-lg-4 mb-3">
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
          </div>
        </div>
      </div>
    </div>

    <!-- Notas -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center p-3"><h5 class="mb-0">Notas Adicionales</h5></div>
        <div class="card-body p-4">
          <?php if ($notesArray): ?>
            <ul class="notes-list mb-0">
              <?php foreach ($notesArray as $note): ?>
                <li class="mb-2 d-flex align-items-start">
                  <i data-feather="file-text" class="me-2"></i>
                  <div><?= htmlspecialchars($note, ENT_QUOTES) ?></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="text-secondary">No hay notas adicionales para esta herramienta.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</div><!-- .content-main -->
</div><!-- .step6 -->
<section id="wizard-dashboard"></section>

<!-- SCRIPTS -->
<script>window.step6Params = <?= $jsonParams ?>; window.step6Csrf = '<?= $csrfToken ?>';</script>
<?php if (!$embedded): ?>
<script src="<?= $bootstrapJsRel ?>" defer></script>
<script src="<?= asset('node_modules/feather-icons/dist/feather.min.js') ?>" defer></script>
<script src="<?= asset('node_modules/chart.js/dist/chart.umd.min.js') ?>" defer></script>
<script src="<?= asset('node_modules/countup.js/dist/countUp.umd.js') ?>" defer></script>
<script src="<?= $step6JsRel ?>" defer></script>
<script>requestAnimationFrame(() => feather.replace());</script>
</body>
</html>
<?php endif; ?>
