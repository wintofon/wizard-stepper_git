<?php
// File: C:\xampp\htdocs\wizard-stepper\views\steps\step6.php
declare(strict_types=1);

// ──────────────────────────────────────────────────────────────
// ¿Se carga directo o embebido en load-step.php?
// Si index.php incluyó esta vista → define('WIZARD_EMBEDDED', true)
// Si se abre en el navegador directamente → la constante NO existe
// ──────────────────────────────────────────────────────────────
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

// [A] CABECERAS DE SEGURIDAD Y NO-CACHING (solo si NO embebido)
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

// Stub de dbg()
if (!function_exists('dbg')) {
    function dbg(...$args) { /* stub vacío si no hay debug.php */ }
}

// [B] SESIÓN SEGURA
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

// [C] DEBUG OPCIONAL
detectDebug:
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable(__DIR__ . '/../../includes/debug.php')) {
    require_once __DIR__ . '/../../includes/debug.php';
}

// [D] INCLUIR CONEXIÓN A LA BD
$dbFile = __DIR__ . '/../../includes/db.php';
if (!is_readable($dbFile)) {
    http_response_code(500);
    exit('Error interno: falta el archivo de conexión a la BD.');
}
require_once $dbFile;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Error interno: no hay conexión a la base de datos.');
}

// [E] Normalizar session
if (isset($_SESSION['material_id']))     $_SESSION['material']   = $_SESSION['material_id'];
if (isset($_SESSION['transmission_id'])) $_SESSION['trans_id']   = $_SESSION['transmission_id'];
if (isset($_SESSION['feed_max']))        $_SESSION['fr_max']     = $_SESSION['feed_max'];
if (isset($_SESSION['strategy_id']))     $_SESSION['strategy']   = $_SESSION['strategy_id'];

// [F] GESTIÓN DE CSRF
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

// [G] VALIDAR CLAVES OBLIGATORIAS EN SESSIÓN
$requiredKeys = [
    'tool_table','tool_id','material','trans_id',
    'rpm_min','rpm_max','fr_max','thickness',
    'strategy','hp'
];
$missing = [];
foreach ($requiredKeys as $k) {
    if (!isset($_SESSION[$k]) || $_SESSION[$k] === '') {
        $missing[] = $k;
    }
}
if ($missing) {
    echo "<div style='color:white;background:#900;padding:1rem;'>"
       . "<b>ERROR: Faltan datos en la sesión</b><br>"
       . "Claves faltantes: <span style='color:#FFD700;'>" . implode(', ', $missing) . "</span><br>"
       . "<pre style='background:#222;color:#fff;padding:1rem;max-height:300px;overflow:auto;'>"
       . htmlspecialchars(print_r($_SESSION, true)) . "</pre>"
       . "</div>";
    http_response_code(400);
    exit;
}

// [H] INCLUIR DEPENDENCIAS
$root = __DIR__ . '/../../';
foreach ([
    'src/Controller/ExpertResultController.php',
    'src/Model/ToolModel.php',
    'src/Model/ConfigModel.php',
    'src/Utils/CNCCalculator.php'
] as $file) {
    $path = $root . $file;
    if (is_readable($path)) {
        require_once $path;
    } else {
        http_response_code(500);
        exit("Error interno: falta $file");
    }
}

// [I] OBTENER DATOS HERRAMIENTA Y PARÁMETROS
$toolTable = (string)$_SESSION['tool_table'];
$toolId    = (int)$_SESSION['tool_id'];
$toolData  = ToolModel::getTool($pdo, $toolTable, $toolId);
if (!$toolData) {
    http_response_code(404);
    exit('Herramienta no encontrada.');
}
$params    = ExpertResultController::getResultData($pdo, $_SESSION);
$jsonParams = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($jsonParams === false) {
    http_response_code(500);
    exit('Error interno: no se pudo serializar parámetros técnicos.');
}

// [J] PREPARAR VARIABLES PARA LA VISTA
// Datos herramienta
$serialNumber  = htmlspecialchars($toolData['serie']       ?? '', ENT_QUOTES);
$toolCode      = htmlspecialchars($toolData['tool_code']   ?? '', ENT_QUOTES);
$toolName      = htmlspecialchars($toolData['name']        ?? 'N/A', ENT_QUOTES);
$toolType      = htmlspecialchars($toolData['tool_type']   ?? 'N/A', ENT_QUOTES);

// Imágenes
$imageRel      = $toolData['image'] ?? '';
$imageURL      = $imageRel !== '' ? ('/wizard-stepper/' . ltrim($imageRel, '/\\')) : '';
$vectorRel     = $toolData['image_url2'] ?? '';
$vectorURL     = $vectorRel !== '' ? ('/wizard-stepper/' . ltrim($vectorRel, '/\\')) : '';

// Especificaciones
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

// Parámetros técnicos base
$baseVc        = (float)$params['vc0'];
$vcMinDb       = (float)$params['vc_min0'];
$vcMaxDb       = (float)($params['vc_max0'] ?? $baseVc * 1.25);
$baseFz        = (float)$params['fz0'];
$fzMinDb       = (float)$params['fz_min0'];
$fzMaxDb       = (float)$params['fz_max0'];
$apSlot        = (float)$params['ap_slot'];
$aeSlot        = (float)$params['ae_slot'];
$rpmMin        = (float)$params['rpm_min'];
$rpmMax        = (float)$params['rpm_max'];
$frMax         = (float)$params['fr_max'];
$baseRpm       = (int)  $params['rpm0'];
$baseFeed      = (float)$params['feed0'];
$baseMmr       = (float)$params['mmr_base'];

// Configuración usuario
$materialName   = (string)($_SESSION['material_name']   ?? 'Genérico Fibrofácil (MDF)');
$materialParent = (string)($_SESSION['material_parent'] ?? 'Maderas Naturales');
$strategyName   = (string)($_SESSION['strategy_name']   ?? 'Grabado en V / 2.5D');
$strategyParent = (string)($_SESSION['strategy_parent'] ?? 'Fresado');
$thickness      = (float)$_SESSION['thickness'];
$powerAvail     = (float)$_SESSION['hp'];

// Nombre de transmisión
try {
    $stmt = $pdo->prepare("SELECT name FROM transmissions WHERE id = ?");
    $stmt->execute([(int)$_SESSION['trans_id']]);
    $transName = $stmt->fetchColumn() ?: 'N/D';
} catch (\Throwable $e) {
    $transName = 'N/D';
}

// Notas
$notesArray = $params['notes'] ?? [];

// Rutas de assets locales vs CDN
$cssBootstrapRel = file_exists($root . 'assets/css/bootstrap.min.css')
    ? '/wizard-stepper/assets/css/bootstrap.min.css' : '';
$bootstrapJsRel  = file_exists($root . 'assets/js/bootstrap.bundle.min.js')
    ? '/wizard-stepper/assets/js/bootstrap.bundle.min.js' : '';
$featherLocal    = $root . 'node_modules/feather-icons/dist/feather.min.js';
$cdnFeather      = 'https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js';
$chartJsLocal    = $root . 'node_modules/chart.js/dist/chart.umd.min.js';
$cdnChartJs      = 'https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js';
$countUpLocal    = $root . 'node_modules/countup.js/dist/countUp.umd.js';
$cdnCountUp      = 'https://cdn.jsdelivr.net/npm/countup.js/dist/countUp.umd.min.js';
$step6JsRel      = file_exists($root . 'assets/js/step6.js')
    ? '/wizard-stepper/assets/js/step6.js' : '';

$assetErrors = [];
if (!$cssBootstrapRel)             $assetErrors[] = 'Bootstrap CSS no encontrado localmente.';
if (!$bootstrapJsRel)               $assetErrors[] = 'Bootstrap JS no encontrado localmente.';
if (!file_exists($featherLocal))    $assetErrors[] = 'Feather Icons JS faltante.';
if (!file_exists($chartJsLocal))    $assetErrors[] = 'Chart.js faltante.';
if (!file_exists($countUpLocal))    $assetErrors[] = 'CountUp.js faltante.';
?>

<?php if (!$embedded): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cutting Data Épico – Paso 6</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/wizard-stepper/assets/css/step6.css">
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
                    <li><span>Longitud de filo:</span><span><?= number_format($fluteLenMb, 3, '.', '') ?> mm</span></li>
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

<!-- FILA: Sliders / Resultados / Radar -->
<div class="container-fluid py-3 content-main">
  <div class="row gx-3 mb-4">

    <!-- 1) Ajustes (Sliders) -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header"><h5 class="mb-0">Ajustes</h5></div>
        <div class="card-body">

          <!-- fz -->
          <div class="mb-4">
            <label for="sliderFz" class="form-label">fz (mm/tooth)</label>
            <input type="range" id="sliderFz" class="form-range"
                   min="<?= number_format($fzMinDb,4,'.','') ?>"
                   max="<?= number_format($fzMaxDb,4,'.','') ?>"
                   step="0.0001"
                   value="<?= number_format($baseFz,4,'.','') ?>">
            <div class="text-end small text-secondary mt-1">
              <span><?= number_format($fzMinDb,4,'.','') ?></span>
               – 
              <strong id="valFz"><?= number_format($baseFz,4,'.','') ?></strong>
               – 
              <span><?= number_format($fzMaxDb,4,'.','') ?></span>
            </div>
          </div>

          <!-- Vc -->
          <div class="mb-4">
            <label for="sliderVc" class="form-label">Vc (m/min)</label>
            <input type="range" id="sliderVc" class="form-range"
                   min="<?= number_format($vcMinDb,1,'.','') ?>"
                   max="<?= number_format($vcMaxDb,1,'.','') ?>"
                   step="0.1"
                   value="<?= number_format($baseVc,1,'.','') ?>">
            <div class="text-end small text-secondary mt-1">
              <span><?= number_format($vcMinDb,1,'.','') ?></span>
               – 
              <strong id="valVc"><?= number_format($baseVc,1,'.','') ?></strong>
               – 
              <span><?= number_format($vcMaxDb,1,'.','') ?></span>
            </div>
          </div>

          <!-- ae -->
          <div class="mb-4">
            <label for="sliderAe" class="form-label">
              ae (mm) <small>(ancho de pasada)</small>
            </label>
            <input type="range" id="sliderAe" class="form-range"
                   min="0.1"
                   max="<?= number_format($diameterMb,1,'.','') ?>"
                   step="0.1"
                   value="<?= number_format($diameterMb*0.5,1,'.','') ?>">
            <div class="text-end small text-secondary mt-1">
              <span>0.1</span>
               – 
              <strong id="valAe"><?= number_format($diameterMb*0.5,1,'.','') ?></strong>
               – 
              <span><?= number_format($diameterMb,1,'.','') ?></span>
            </div>
          </div>

          <!-- Pasadas -->
          <div class="mb-4">
            <label for="sliderPasadas" class="form-label">Pasadas</label>
            <input type="range" id="sliderPasadas" class="form-range"
                   data-thickness="<?= htmlspecialchars($_SESSION['thickness'], ENT_QUOTES) ?>">
            <div id="textPasadasInfo" class="small text-secondary mt-1"></div>
            <div id="errorMsg" class="text-danger mt-2 small" style="display:none"></div>
          </div>

        </div>
      </div>
    </div>

    <!-- 2) Resultados -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header"><h5 class="mb-0">Resultados</h5></div>
        <div class="card-body">
          <div class="mb-2"><strong>Vc:</strong> <span id="outVc">--</span></div>
          <div class="mb-2"><strong>fz:</strong> <span id="outFz">--</span></div>
          <div class="mb-2"><strong>hm:</strong> <span id="outHm">--</span></div>
          <div class="mb-2"><strong>n (RPM):</strong> <span id="outN">--</span></div>
          <div class="mb-2"><strong>Vf:</strong> <span id="outVf">--</span></div>
          <div class="mb-3"><strong>Hp:</strong> <span id="outHp">--</span></div>

          <div class="d-grid gap-2 results-compact mt-3">
            <div class="result-box p-2 text-center border rounded">
              <div class="param-label">MMR<br><small>(mm³/min)</small></div>
              <div id="valueMrr" class="fw-bold">--</div>
            </div>
            <div class="result-box p-2 text-center border rounded">
              <div class="param-label">Fc<br><small>(N)</small></div>
              <div id="valueFc" class="fw-bold">--</div>
            </div>
            <div class="result-box p-2 text-center border rounded">
              <div class="param-label">Potencia<br><small>(W)</small></div>
              <div id="valueW" class="fw-bold">--</div>
            </div>
            <div class="result-box p-2 text-center border rounded">
              <div class="param-label">η<br><small>(%)</small></div>
              <div id="valueEta" class="fw-bold">--</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3) Radar Chart -->
    <div class="col-12 col-lg-4 mb-3">
      <div class="card h-100 shadow-sm">
        <div class="card-header"><h5 class="mb-0">Distribución Radar</h5></div>
        <div class="card-body d-flex justify-content-center align-items-center">
          <canvas id="radarChart" width="100%" height="200"></canvas>
        </div>
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




<script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js"></script>
<script src="/wizard-stepper/assets/js/step6.js"></script>

<?php if (!$embedded): ?>
</body>
</html>
<?php endif; ?>






