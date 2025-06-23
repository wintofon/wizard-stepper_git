<?php declare(strict_types=1);

/**
 * Paso 6 – Resultados
 * Versión “mini”: igual al paso 5 en estructura, pero mostrando todo el dashboard del 6.
 *  – Requiere que el paso 5 haya guardado los datos en sesión.
 *  – Sin modo embebido ni helpers asset().
 */

# ───────────────── Sesión & flujo ─────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if ((int)($_SESSION['wizard_progress'] ?? 0) < 5) {
    header('Location: step1.php');
    exit;
}

# ───────────────── CSRF ─────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

# ───────────────── Conexión BD + modelos ─────────────────
require_once __DIR__.'/../../includes/db.php';      // → $pdo
require_once __DIR__.'/../../src/Model/ToolModel.php';
require_once __DIR__.'/../../src/Controller/ExpertResultController.php';

# ───────────────── Recuperar datos herramienta ─────────────────
$toolData = ToolModel::getTool(
    $pdo,
    (string)$_SESSION['tool_table'],
    (int)$_SESSION['tool_id']
) ?: [];

$params = ExpertResultController::getResultData($pdo, $_SESSION);
$jsonParams = json_encode($params, JSON_UNESCAPED_UNICODE);

# ───────────────── Variables para la vista ─────────────────
$serial   = htmlspecialchars($toolData['serie']      ?? '', ENT_QUOTES);
$code     = htmlspecialchars($toolData['tool_code']  ?? '', ENT_QUOTES);
$name     = htmlspecialchars($toolData['name']       ?? 'N/D', ENT_QUOTES);
$type     = htmlspecialchars($toolData['tool_type']  ?? 'N/D', ENT_QUOTES);
$img      = htmlspecialchars($toolData['image']      ?? '', ENT_QUOTES);
$vector   = htmlspecialchars($toolData['image_dimensions'] ?? '', ENT_QUOTES);
$diameter = (float)($toolData['diameter_mm'] ?? 0);

# Valores base
$outN  = number_format((float)$params['rpm0'], 0, '.', '');
$outVf = number_format((float)$params['feed0'], 0, '.', '');
$outVc = number_format((float)$params['vc0'],   1, '.', '');

?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 6 – Resultados</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/objects/step-common.css">
<link rel="stylesheet" href="assets/css/objects/step6.css">
<script>
  window.step6Params = <?= $jsonParams ?>;
  window.step6Csrf   = '<?= $csrf ?>';
</script>
</head><body>
<main class="container py-4">

<h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
<p class="step-desc">Ajustá los parámetros y revisá los datos de corte.</p>

<!-- │ TOOL CARD │ -->
<div class="card mb-4 shadow-sm">
  <div class="card-header text-center"><span>#<?= $serial ?> – <?= $code ?></span></div>
  <div class="card-body text-center">
    <?php if ($img) :?>
      <img src="<?= $img ?>" class="tool-image mb-3" alt="Herramienta">
    <?php endif;?>
    <h5 class="tool-name mb-0"><?= $name ?></h5>
    <small class="text-muted"><?= $type ?></small>
  </div>
</div>

<!-- │ AJUSTES + RESULTADOS (layout compacto parecido al paso 5) │ -->
<form id="step6Form" method="post" class="needs-validation" novalidate>
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

  <div class="row g-3">

    <!-- Ajustes rápidos -->
    <div class="col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center"><h6 class="mb-0">Ajustes</h6></div>
        <div class="card-body">
          <label class="form-label">Vc (m/min)</label>
          <input type="range" class="form-range" id="sliderVc"
           min="<?= $params['vc_min0'] ?>" max="<?= $params['vc_max0'] ?>" step="0.1"
           value="<?= $params['vc0'] ?>">
          <label class="form-label mt-3">fz (mm/tooth)</label>
          <input type="range" class="form-range" id="sliderFz"
           min="<?= $params['fz_min0'] ?>" max="<?= $params['fz_max0'] ?>" step="0.0001"
           value="<?= $params['fz0'] ?>">
        </div>
      </div>
    </div>

    <!-- Resultados compactos -->
    <div class="col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center"><h6 class="mb-0">Resultados</h6></div>
        <div class="card-body">
          <div class="text-center mb-3">
            <div class="display-6 fw-bold" id="outN"><?= $outN ?></div>
            <small class="text-muted">RPM</small>
          </div>
          <div class="text-center">
            <div class="display-6 fw-bold" id="outVf"><?= $outVf ?></div>
            <small class="text-muted">mm/min</small>
          </div>
          <hr>
          <div class="d-flex justify-content-between">
            <span>Vc</span><strong id="outVc"><?= $outVc ?></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span>fz</span><strong id="outFz"><?= $params['fz0'] ?></strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Radar chart -->
    <div class="col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-header text-center"><h6 class="mb-0">Radar</h6></div>
        <div class="card-body d-flex justify-content-center align-items-center">
          <canvas id="radarChart" width="300" height="300"></canvas>
        </div>
      </div>
    </div>

  </div><!--/row-->

  <!-- Botón Siguiente -->
  <div class="text-end mt-4">
    <button class="btn btn-primary btn-lg">Siguiente <i data-feather="arrow-right"></i></button>
  </div>
</form>

</main>

<!-- Libs JS (idénticas a paso 5) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/countup.js@2.8.0/dist/countUp.umd.js"></script>
<script>
feather.replace();
/* Event listeners simples para actualizar etiquetas en vivo */
const fmt=v=>(+v).toLocaleString('en-US',{maximumFractionDigits:3});
const fz = document.getElementById('sliderFz');
const vc = document.getElementById('sliderVc');
fz.addEventListener('input',()=>outFz.textContent=fmt(fz.value));
vc.addEventListener('input',()=>outVc.textContent=fmt(vc.value));
</script>
</body></html>
