<?php
// File: C:\xampp\htdocs\wizard-stepper_git\views\steps\step6.php
declare(strict_types=1);

/* ──────────────────────────────────────────────────────────────
 * ¿Se carga directo o embebido en load-step.php?
 * Si index.php incluyó esta vista           → define('WIZARD_EMBEDDED', true)
 * Si se abre en el navegador directamente  → la constante NO existe
 * ────────────────────────────────────────────────────────────── */
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

/* [A] CABECERAS DE SEGURIDAD Y NO-CACHING  (solo si NO embebido) */
if (!$embedded) {
  header('Content-Type: text/html; charset=UTF-8');
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
  header('X-Frame-Options: DENY');
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: no-referrer');
  header('Permissions-Policy: geolocation=(), microphone=()');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
}

/* Stub de dbg()  */
if (!function_exists('dbg')) {
    function dbg(...$args) { /* stub vacío si no hay debug.php */ }
}

/* [B] SESIÓN SEGURA */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

/* [C] DEBUG OPCIONAL */
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable('C:\xampp\htdocs\wizard-stepper_git\includes\debug.php')) {
    require_once 'C:\xampp\htdocs\wizard-stepper_git\includes\debug.php';
}

/* [D] INCLUIR CONEXIÓN A LA BD */
$dbFile = 'C:\xampp\htdocs\wizard-stepper_git\includes\db.php';
if (!is_readable($dbFile)) {
    http_response_code(500);
    exit('Error interno: falta el archivo de conexión a la BD.');
}
require_once $dbFile;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Error interno: no hay conexión a la base de datos.');
}

/* [E] NORMALIZAR CLAVES EN $_SESSION */
if (isset($_SESSION['material_id']))    $_SESSION['material']   = $_SESSION['material_id'];
if (isset($_SESSION['transmission_id']))$_SESSION['trans_id']   = $_SESSION['transmission_id'];
if (isset($_SESSION['feed_max']))       $_SESSION['fr_max']     = $_SESSION['feed_max'];
if (isset($_SESSION['strategy_id']))    $_SESSION['strategy']   = $_SESSION['strategy_id'];

/* [F] VALIDAR “wizard_state” Y “wizard_progress” */
if (($_SESSION['wizard_state'] ?? '') !== 'wizard') {
    http_response_code(403);
    exit('Acceso prohibido: no completaste los pasos previos.');
}
$currentProgress = (int)($_SESSION['wizard_progress'] ?? 0);
if ($currentProgress < 5) {
    http_response_code(403);
    exit('Acceso prohibido: no completaste los pasos previos.');
}

/* [G] GESTIÓN DE CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, (string)$postedToken)) {
        http_response_code(403);
        exit('Error CSRF: petición no autorizada.');
    }
}

/* [H] VALIDAR CLAVES OBLIGATORIAS EN $_SESSION */
$requiredKeys = [
    'tool_table',
    'tool_id',
    'material',
    'trans_id',
    'rpm_min',
    'rpm_max',
    'fr_max',
    'thickness',
    'strategy',
    'hp'
];
$missingKeys = [];
foreach ($requiredKeys as $k) {
    if (!isset($_SESSION[$k]) || $_SESSION[$k] === '') {
        $missingKeys[] = $k;
    }
}
if (!empty($missingKeys)) {
    // ------ BLOQUE DEBUG VISUAL ------
    echo "<div style='color:white;background:#900;padding:1rem;font-size:1.2rem;z-index:99999'>";
    echo "❌ <b>ERROR: Faltan datos en la sesión</b><br>";
    echo "<b>Claves faltantes:</b> <span style='color: #FFD700;'>" . implode(', ', $missingKeys) . "</span><br>";
    echo "<b>Session actual:</b><pre style='color:#fff;background:#222;padding:1em;max-height:300px;overflow:auto;'>" 
         . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";
    echo "<br>Recargá el Wizard desde el <b>Paso 1</b> o revisá el flujo de carga.<br>";
    echo "</div>";
    http_response_code(400);
    exit;
}

/* [I] INCLUIR DEPENDENCIAS (RUTAS ABSOLUTAS) */
$rootDir = 'C:\xampp\htdocs\wizard-stepper_git';
$srcDir  = $rootDir . '\src';
foreach ([
    $srcDir . '\Controller\ExpertResultController.php',
    $srcDir . '\Model\ToolModel.php',
    $srcDir . '\Model\ConfigModel.php',
    $srcDir . '\Utils\CNCCalculator.php'
] as $path) {
    if (is_readable($path)) require_once $path;
    else { http_response_code(500); exit("Error interno: falta el archivo PHP: {$path}"); }
}

/* [J] OBTENER DATOS DE HERRAMIENTA Y PARÁMETROS TÉCNICOS */
$toolTable = (string)$_SESSION['tool_table'];
$toolId    = (int)$_SESSION['tool_id'];
$toolData  = ToolModel::getTool($pdo, $toolTable, $toolId);
if (!$toolData) {
    http_response_code(404);
    exit('Error: herramienta no encontrada.');
}
$params = ExpertResultController::getResultData($pdo, $_SESSION);
$jsonParams = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonParams === false) {
    http_response_code(500);
    exit('Error interno: no se pudo serializar parámetros técnicos.');
}

/* [K] PREPARAR VARIABLES PARA LA VISTA */
// ...[acá las variables $serialNumber, $toolCode, etc, como ya lo hacías]...
$serialNumber = htmlspecialchars($toolData['serie']      ?? '', ENT_QUOTES);
$toolCode     = htmlspecialchars($toolData['tool_code']  ?? '', ENT_QUOTES);
$imageRel     = $toolData['image'] ?? '';
$imageURL     = $imageRel !== '' ? ('/wizard-stepper_git/' . ltrim($imageRel, '/\\')) : '';
$vectorRel    = $toolData['image_url2'] ?? '';
$vectorURL    = $vectorRel !== '' ? ('/wizard-stepper_git/' . ltrim($vectorRel, '/\\')) : '';
$toolName     = htmlspecialchars($toolData['name'] ?? 'N/A', ENT_QUOTES);
$toolType     = htmlspecialchars($toolData['tool_type'] ?? 'N/A', ENT_QUOTES);
$diameterMb   = (float)($toolData['diameter_mm']       ?? 0);
$shankMb      = (float)($toolData['shank_diameter_mm'] ?? 0);
$fluteLenMb   = (float)($toolData['flute_length_mm']   ?? 0);
$cutLenMb     = (float)($toolData['cut_length_mm']     ?? 0);
$fullLenMb    = (float)($toolData['full_length_mm']    ?? 0);
$fluteCountMb = (int)($toolData['flute_count']         ?? 0);
$coatingMb    = htmlspecialchars($toolData['coated']    ?? 'N/A', ENT_QUOTES);
$materialMb   = htmlspecialchars($toolData['material']  ?? 'N/A', ENT_QUOTES);
$brandMb      = htmlspecialchars($toolData['brand']     ?? 'N/A', ENT_QUOTES);
$madeInMb     = htmlspecialchars($toolData['made_in']   ?? 'N/A', ENT_QUOTES);

$diameter   = (float)$params['diameter'];
$fluteCount = (int)$params['flute_count'];
$baseVc     = (float)$params['vc0'];
$vcMinDb    = (float)$params['vc_min0'];
$vcMaxDb    = (float)($params['vc_max0'] ?? $baseVc * 1.25);
$baseFz     = (float)$params['fz0'];
$fzMinDb    = (float)$params['fz_min0'];
$fzMaxDb    = (float)$params['fz_max0'];
$apSlot     = (float)$params['ap_slot'];
$aeSlot     = (float)$params['ae_slot'];
$rpmMin     = (float)$params['rpm_min'];
$rpmMax     = (float)$params['rpm_max'];
$frMax      = (float)$params['fr_max'];
$baseRpm    = (int)$params['rpm0'];
$baseFeed   = (float)$params['feed0'];
$baseMmr    = (float)$params['mmr_base'];

$materialName   = (string)($_SESSION['material_name']   ?? 'Genérico Fibrofácil (MDF)');
$materialParent = (string)($_SESSION['material_parent'] ?? 'Maderas Naturales');
$strategyName   = (string)($_SESSION['strategy_name']   ?? 'Grabado en V / 2.5D');
$strategyParent = (string)($_SESSION['strategy_parent'] ?? 'Fresado');
$thickness  = (float)$_SESSION['thickness'];
$powerAvail = (float)$_SESSION['hp'];

try {
    $stmt = $pdo->prepare("SELECT name FROM transmissions WHERE id = ?");
    $stmt->execute([ (int)$_SESSION['trans_id'] ]);
    $transName = $stmt->fetchColumn() ?: 'N/D';
} catch (\Throwable $e) { $transName = 'N/D'; }

$notesArray = $params['notes'] ?? [];

$cssBootstrapRel = file_exists('C:\xampp\htdocs\wizard-stepper_git\assets\css\bootstrap.min.css')
    ? '/wizard-stepper_git/assets/css/bootstrap.min.css' : '';
$bootstrapJsRel = file_exists('C:\xampp\htdocs\wizard-stepper_git\assets\js\bootstrap.bundle.min.js')
    ? '/wizard-stepper_git/assets/js/bootstrap.bundle.min.js' : '';
$featherLocal   = 'C:\xampp\htdocs\wizard-stepper_git\node_modules\feather-icons\dist\feather.min.js';
$cdnFeather     = 'https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js';
$chartJsLocal   = 'C:\xampp\htdocs\wizard-stepper_git\node_modules\chart.js\dist\chart.umd.min.js';
$cdnChartJs     = 'https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js';
$countUpLocal   = 'C:\xampp\htdocs\wizard-stepper_git\node_modules\countup.js\dist\countUp.umd.js';
$cdnCountUp     = 'https://cdn.jsdelivr.net/npm/countup.js/dist/countUp.umd.js';
$step6JsRel = file_exists('C:\xampp\htdocs\wizard-stepper_git\assets\js\step6.js')
    ? '/wizard-stepper_git/assets/js/step6.js' : '';

$assetErrors = [];
if ($cssBootstrapRel === '') $assetErrors[] = 'Bootstrap CSS no encontrado en local; se cargará desde CDN.';
if ($bootstrapJsRel === '') $assetErrors[] = 'Bootstrap JS no encontrado en local; se cargará desde CDN.';
if (!file_exists($featherLocal)) $assetErrors[] = 'Feather Icons JS no encontrado en local; se cargará desde CDN.';
if (!file_exists($chartJsLocal)) $assetErrors[] = 'Chart.js no encontrado en local; se cargará desde CDN.';
if (!file_exists($countUpLocal)) $assetErrors[] = 'CountUp.js no encontrado en local; se cargará desde CDN.';

?>

<?php if (!$embedded): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cutting Data Épico – Paso 6</title>
  <?php if ($cssBootstrapRel !== ''): ?>
    <link rel="stylesheet" href="<?= $cssBootstrapRel ?>">
  <?php else: ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <div class="alert alert-warning text-dark m-3">
      ⚠️ Bootstrap CSS no encontrado en local; se cargó desde CDN.
    </div>
  <?php endif; ?>
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/step6.css">
</head>
<body>
<?php endif; ?>

<!-- ALERTA DE ASSETS FALTANTES -->
<?php if (!empty($assetErrors)): ?>
  <div class="alert alert-warning text-dark m-3">
    <strong>⚠️ Archivos faltantes (se usarán CDNs):</strong>
    <ul>
      <?php foreach ($assetErrors as $err): ?>
        <li><?= htmlspecialchars($err ?? '', ENT_QUOTES) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- BLOQUE CENTRAL HTML (idéntico al que venías usando; si necesitás el contenido te lo pego, avisá) -->
<div class="container-fluid py-3 content-main">
  <!-- ... (TODO EL HTML CENTRAL DE TU PASO 6) ... -->
  <!-- PONÉ ACÁ TODA TU MAQUETA Y VISUAL -->
  <!-- ... [como ya tenés armado: encabezado, sliders, tablas, radar, etc.] ... -->
<!-- ENCABEZADO DE HERRAMIENTA -->
<div class="row mb-3">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <span>#<?= htmlspecialchars($serialNumber ?? '', ENT_QUOTES) ?> – <?= htmlspecialchars($toolCode ?? '', ENT_QUOTES) ?></span>
      </div>
      <div class="card-body text-center">
        <?php if ($imageURL): ?>
          <img src="<?= htmlspecialchars($imageURL, ENT_QUOTES) ?>" alt="Imagen principal de la herramienta" class="tool-image">
        <?php else: ?>
          <div class="text-secondary">Sin imagen disponible</div>
        <?php endif; ?>
        <div class="tool-name"><?= htmlspecialchars($toolName ?? '', ENT_QUOTES) ?></div>
        <div class="tool-type"><?= htmlspecialchars($toolType ?? '', ENT_QUOTES) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- FILA: Especificaciones y Vectorial -->
<div class="row gx-3 mb-4">
  <div class="col-12 col-lg-6 mb-3">
    <div class="card">
      <div class="card-header"><i data-feather="tool" class="me-1"></i> Especificaciones Técnicas</div>
      <div class="card-body">
        <ul class="spec-list mb-0">
          <li><span>Diámetro de corte (d1):</span><span><?= number_format($diameterMb, 3, '.', '') ?> mm</span></li>
          <li><span>Diámetro del vástago:</span><span><?= number_format($shankMb, 3, '.', '') ?> mm</span></li>
          <li><span>Longitud de corte:</span><span><?= number_format($cutLenMb, 3, '.', '') ?> mm</span></li>
          <li><span>Longitud total:</span><span><?= number_format($fullLenMb, 3, '.', '') ?> mm</span></li>
          <li><span>Número de filos (Z):</span><span><?= htmlspecialchars((string)$fluteCountMb, ENT_QUOTES) ?></span></li>
          <li><span>Tipo de punta:</span><span><?= htmlspecialchars($toolType ?? '', ENT_QUOTES) ?></span></li>
          <li><span>Recubrimiento:</span><span><?= htmlspecialchars($coatingMb ?? '', ENT_QUOTES) ?></span></li>
          <li><span>Material fabricación:</span><span><?= htmlspecialchars($materialMb ?? '', ENT_QUOTES) ?></span></li>
          <li><span>Marca:</span><span><?= htmlspecialchars($brandMb ?? '', ENT_QUOTES) ?></span></li>
          <li><span>País de origen:</span><span><?= htmlspecialchars($madeInMb ?? '', ENT_QUOTES) ?></span></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-6 mb-3">
    <div class="card">
      <div class="card-header"><i data-feather="image" class="me-1"></i> Imagen Vectorial</div>
      <div class="card-body text-center">
        <?php if ($vectorURL !== ''): ?>
          <img src="<?= htmlspecialchars($vectorURL, ENT_QUOTES) ?>" alt="Imagen vectorial vertical" class="vector-image">
        <?php else: ?>
          <div class="text-secondary" style="margin-top: 2rem;">Sin imagen vectorial</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- FILA: Sliders + Parámetros de Corte / Resultados -->
<div class="row gx-3 mb-4">
  <div class="col-12 col-lg-6 mb-3">
    <div class="slider-box">
      <h5 class="mb-3"><i data-feather="sliders" class="me-1"></i> Ajustes</h5>
      <!-- fz -->
      <div class="slider-row">
        <div class="slider-limit"><?= number_format($fzMinDb, 4, '.', '') ?></div>
        <div class="slider-container">
          <span id="badgeFz" class="badge-value"><?= number_format($baseFz, 4, '.', '') ?></span>
          <input type="range" class="form-range" id="sliderFz"
            min="<?= number_format($fzMinDb, 4, '.', '') ?>"
            max="<?= number_format($fzMaxDb, 4, '.', '') ?>"
            step="0.0001"
            value="<?= number_format($baseFz, 4, '.', '') ?>">
        </div>
        <div class="slider-limit"><?= number_format($fzMaxDb, 4, '.', '') ?></div>
      </div>
      <div class="text-center text-secondary mb-3" style="font-size: 0.8rem;">
        <em>fz min</em> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <em>fz max</em>
      </div>
      <!-- Vc -->
      <div class="slider-row">
        <div class="slider-limit"><?= number_format($vcMinDb, 1, '.', '') ?></div>
        <div class="slider-container">
          <span id="badgeVc" class="badge-value"><?= number_format($baseVc, 1, '.', '') ?></span>
          <input type="range" class="form-range" id="sliderVc"
            min="<?= number_format($vcMinDb, 1, '.', '') ?>"
            max="<?= number_format($vcMaxDb, 1, '.', '') ?>"
            step="0.1"
            value="<?= number_format($baseVc, 1, '.', '') ?>">
        </div>
        <div class="slider-limit"><?= number_format($vcMaxDb, 1, '.', '') ?></div>
      </div>
      <div class="text-center text-secondary mb-3" style="font-size: 0.8rem;">
        <em>−25%</em> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <em>+25%</em>
      </div>
      <!-- Número de Pasadas -->
      <?php
        $realThickness  = (float)$_SESSION['thickness'];
        $maxPasadas     = (int)ceil($realThickness / $apSlot);
        $initialPasadas = 1;
      ?>
      <div class="mb-3">
        <label class="slider-label">Número de Pasadas</label>
        <div class="d-flex align-items-center">
          <button id="btnPasadasMenos" class="btn-pasadas">–</button>
          <div id="countPasadas" class="count-pasadas"><?= $initialPasadas ?></div>
          <button id="btnPasadasMas" class="btn-pasadas">+</button>
          <div id="textPasadasDetalle" class="text-pasadas-detalle">
            <?= $initialPasadas ?> pasadas de <?= number_format($realThickness / $initialPasadas, 2, '.', '') ?> mm
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-6 mb-3">
    <div class="param-card">
      <h5 class="param-title"><i data-feather="cpu" class="me-1"></i> Parámetros de Corte</h5>
      <div id="paramSpinner" class="spinner-overlay">
        <div class="spinner-border spinner-border-sm text-primary" role="status">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>
      <div class="param-row">
        <div class="param-label"><i data-feather="trending-up" data-bs-toggle="tooltip" data-bs-title="Velocidad de corte ajustada"></i>Vc</div>
        <div class="param-value"><span id="valueVc"><?= number_format($baseVc, 1, '.', '') ?></span> <span class="param-unit">m/min</span></div>
      </div>
      <div class="param-row">
        <div class="param-label"><i data-feather="slash" data-bs-toggle="tooltip" data-bs-title="Avance por diente ajustado"></i>fz</div>
        <div class="param-value"><span id="valueFz"><?= number_format($baseFz, 4, '.', '') ?></span> <span class="param-unit">mm/tooth</span></div>
      </div>
      <div class="param-row">
        <div class="param-label"><i data-feather="rotate-cw" data-bs-toggle="tooltip" data-bs-title="RPM efectiva"></i>n</div>
        <div class="param-value"><span id="valueN"><?= number_format($baseRpm, 0, '.', '') ?></span> <span class="param-unit">rev/min</span></div>
      </div>
      <div class="param-row">
        <div class="param-label"><i data-feather="fast-forward" data-bs-toggle="tooltip" data-bs-title="Avance herramienta"></i>Vf</div>
        <div class="param-value"><span id="valueVf"><?= number_format($baseFeed, 0, '.', '') ?></span> <span class="param-unit">mm/min</span></div>
      </div>
      <div class="param-row mb-3">
        <div class="param-label"><i data-feather="zap" data-bs-toggle="tooltip" data-bs-title="Potencia requerida"></i>Hp</div>
        <div class="param-value"><span id="valueHp">--</span> <span class="param-unit">HP</span></div>
      </div>
      <div class="results-compact">
        <div class="result-box">
          <div class="result-icon"><i data-feather="rotate-ccw"></i></div>
          <div class="param-label">MMR</div>
          <div id="valueMrr" class="result-value">--</div>
          <div class="result-unit">mm³/min</div>
        </div>
        <div class="result-box">
          <div class="result-icon"><i data-feather="corner-down-left"></i></div>
          <div class="param-label">Fc</div>
          <div id="valueFc" class="result-value">--</div>
          <div class="result-unit">N</div>
        </div>
        <div class="result-box">
          <div class="result-icon"><i data-feather="zap"></i></div>
          <div class="param-label">Potencia</div>
          <div id="valueW" class="result-value">--</div>
          <div class="result-unit">W</div>
        </div>
        <div class="result-box">
          <div class="result-icon"><i data-feather="cpu"></i></div>
          <div class="param-label">η (%)</div>
          <div id="valueEta" class="result-value">--</div>
        </div>
      </div>
      <div class="radar-container">
        <canvas id="radarChart"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- CONFIGURACIÓN DE USUARIO (3 BLOQUES) -->
<div class="config-card-static mb-4">
  <h5 class="mb-2"><i data-feather="settings" class="me-1"></i> Configuración de Usuario</h5>
  <div class="config-section mb-2">
    <div class="config-section-title">Material</div>
    <div class="config-item">
      <div class="label-static">Categoría padre:</div>
      <div class="value-static"><?= htmlspecialchars($materialParent ?? '', ENT_QUOTES) ?></div>
    </div>
    <div class="config-item">
      <div class="label-static">Material a mecanizar:</div>
      <div class="value-static"><?= htmlspecialchars($materialName ?? '', ENT_QUOTES) ?></div>
    </div>
  </div>
  <div class="section-divider"></div>
  <div class="config-section mb-2">
    <div class="config-section-title">Estrategia</div>
    <div class="config-item">
      <div class="label-static">Categoría padre estr.:</div>
      <div class="value-static"><?= htmlspecialchars($strategyParent ?? '', ENT_QUOTES) ?></div>
    </div>
    <div class="config-item">
      <div class="label-static">Estrategia de corte:</div>
      <div class="value-static"><?= htmlspecialchars($strategyName ?? '', ENT_QUOTES) ?></div>
    </div>
  </div>
  <div class="section-divider"></div>
  <div class="config-section mb-2">
    <div class="config-section-title">Máquina</div>
    <div class="config-item">
      <div class="label-static">Espesor del material:</div>
      <div class="value-static"><?= htmlspecialchars((string)($thickness ?? ''), ENT_QUOTES) ?> <span class="param-unit">mm</span></div>
    </div>
    <div class="config-item">
      <div class="label-static">Tipo de transmisión:</div>
      <div class="value-static"><?= htmlspecialchars($transName ?? '', ENT_QUOTES) ?></div>
    </div>
    <div class="config-item">
      <div class="label-static">Feedrate máximo:</div>
      <div class="value-static"><?= htmlspecialchars((string)($frMax ?? ''), ENT_QUOTES) ?> <span class="param-unit">mm/min</span></div>
    </div>
    <div class="config-item">
      <div class="label-static">RPM mínima:</div>
      <div class="value-static"><?= htmlspecialchars((string)($rpmMin ?? ''), ENT_QUOTES) ?> <span class="param-unit">rev/min</span></div>
    </div>
    <div class="config-item">
      <div class="label-static">RPM máxima:</div>
      <div class="value-static"><?= htmlspecialchars((string)($rpmMax ?? ''), ENT_QUOTES) ?> <span class="param-unit">rev/min</span></div>
    </div>
    <div class="config-item">
      <div class="label-static">Potencia disponible:</div>
      <div class="value-static"><?= htmlspecialchars((string)($powerAvail ?? ''), ENT_QUOTES) ?> <span class="param-unit">HP</span></div>
    </div>
  </div>
</div>

<!-- NOTAS ADICIONALES -->
<div class="notes-card mb-4">
  <h5 class="mb-2"><i data-feather="info" class="me-1"></i> Notas Adicionales</h5>
  <?php if (!empty($notesArray)): ?>
    <ul class="notes-list mb-0">
      <?php foreach ($notesArray as $note): ?>
        <li>
          <i data-feather="file-text"></i>
          <div><?= htmlspecialchars($note ?? '', ENT_QUOTES) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <div class="text-secondary" style="font-size: 0.9rem;">
      No hay notas adicionales para esta herramienta.
    </div>
  <?php endif; ?>
</div>

<!-- JSON OCULTO -->
<div id="expertParamsHolder" style="display:none" data-params='<?= htmlspecialchars($jsonParams, ENT_QUOTES) ?>'></div>
<!-- CSRF HIDDEN -->
<form id="hiddenCsrfForm" method="POST" style="display: none;">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES) ?>">
</form>

  <!-- JSON OCULTO -->
  <div id="expertParamsHolder" style="display: none;" data-params='<?= htmlspecialchars($jsonParams, ENT_QUOTES) ?>'></div>
  <!-- CSRF HIDDEN -->
  <form id="hiddenCsrfForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES) ?>">
  </form>
</div>

<?php if (!$embedded): ?>
  <?php if (file_exists($featherLocal)): ?>
    <script src="/wizard-stepper_git/node_modules/feather-icons/dist/feather.min.js" defer></script>
  <?php else: ?>
    <script src="<?= $cdnFeather ?>" defer></script>
  <?php endif; ?>
  <?php if (file_exists($chartJsLocal)): ?>
    <script src="/wizard-stepper_git/node_modules/chart.js/dist/chart.umd.min.js" defer></script>
  <?php else: ?>
    <script src="<?= $cdnChartJs ?>" defer></script>
  <?php endif; ?>
  <?php if (file_exists($countUpLocal)): ?>
    <script src="/wizard-stepper_git/node_modules/countup.js/dist/countUp.umd.js" defer></script>
  <?php else: ?>
    <script src="<?= $cdnCountUp ?>" defer></script>
  <?php endif; ?>
  <?php if ($bootstrapJsRel !== ''): ?>
    <script src="<?= $bootstrapJsRel ?>" defer></script>
  <?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
  <?php endif; ?>
<?php endif; ?>

<!-- INICIALIZADOR UNIVERSAL DE STEP 6 -->
<script>
// --- INICIALIZADOR UNIVERSAL DE STEP 6 (llama SIEMPRE initStep6) ---
(function() {
  function runStep6Init() {
    if (typeof window.initStep6 === 'function') {
      console.debug('[step6] Ejecutando window.initStep6()');
      window.initStep6();
    } else {
      console.warn('[step6] JS step6.js no estaba cargado. Lo cargo ahora...');
      var s = document.createElement('script');
      s.src = '/wizard-stepper_git/assets/js/step6.js';
      s.defer = true;
      s.onload = function() {
        console.debug('[step6] JS cargado dinámicamente. Llamando initStep6().');
        if (typeof window.initStep6 === 'function') window.initStep6();
        else console.error('[step6] initStep6 sigue sin existir!');
      };
      document.body.appendChild(s);
    }
  }
  runStep6Init();
})();
</script>

<script>
window.expertParams = <?= $jsonParams ?>;
document.addEventListener('DOMContentLoaded', () => {
  if (typeof feather !== 'undefined') feather.replace();
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }
});


</script>
<?php if (!$embedded): ?>
</body>
</html>
<?php endif; ?>
