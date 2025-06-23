<?php declare(strict_types=1);

/**
 * Paso 6 – Resultados (versión ultra-livi ana, gemela al paso 5).
 */

# ── Sesión, flujo & CSRF ───────────────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if ((int)($_SESSION['wizard_progress'] ?? 0) < 5) {
    header('Location: step1.php'); exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

# ── Modelos & datos ────────────────────────────────────────────


$tool = ToolModel::getTool($pdo, $_SESSION['tool_table'], $_SESSION['tool_id']) ?? [];
$par  = ExpertResultController::getResultData($pdo, $_SESSION);
$json = json_encode($par, JSON_UNESCAPED_UNICODE);

# ── Datos mostrados ────────────────────────────────────────────
$name  = htmlspecialchars($tool['name'] ?? '—');
$code  = htmlspecialchars($tool['tool_code'] ?? '—');
$rpm   = number_format($par['rpm0'], 0, '.', '');
$feed  = number_format($par['feed0'], 0, '.', '');
$vc    = number_format($par['vc0'],   1, '.', '');
$fz    = number_format($par['fz0'],   4, '.', '');
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 6 – Resultados</title>

<script>
  window.step6Params = <?= $json ?>;
  window.step6Csrf   = '<?= $csrf ?>';
</script>
</head><body>
<main class="container py-4">

<h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
<p class="step-desc">Revisá y, si querés, retocá los parámetros.</p>

<?php /* ── Tarjeta resumen herramienta ── */ ?>
<div class="card mb-4 shadow-sm">
  <div class="card-body text-center">
    <h5 class="mb-1"><?= $code ?></h5>
    <small class="text-muted"><?= $name ?></small>
  </div>
</div>

<?php /* ── Form como en el paso 5: sliders a la izquierda + métricas a la derecha ── */ ?>
<form id="step6Form" method="post" class="needs-validation" novalidate>
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

  <div class="row g-3">

    <!-- Ajustes -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <label class="form-label">Vc (m/min)</label>
          <input type="range" id="sliderVc" class="form-range"
                 min="<?= $par['vc_min0'] ?>" max="<?= $par['vc_max0'] ?>"
                 value="<?= $par['vc0'] ?>" step="0.1">
          <label class="form-label mt-3">fz (mm/tooth)</label>
          <input type="range" id="sliderFz" class="form-range"
                 min="<?= $par['fz_min0'] ?>" max="<?= $par['fz_max0'] ?>"
                 value="<?= $par['fz0'] ?>" step="0.0001">
        </div>
      </div>
    </div>

    <!-- Resultados -->
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <span>RPM</span><strong id="outN"><?= $rpm ?></strong>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span>Feedrate (mm/min)</span><strong id="outVf"><?= $feed ?></strong>
          </div>
          <hr>
          <div class="d-flex justify-content-between mb-2">
            <span>Vc</span><strong id="outVc"><?= $vc ?></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span>fz</span><strong id="outFz"><?= $fz ?></strong>
          </div>
        </div>
      </div>
    </div>

  </div><!--/row-->

  <div class="text-end mt-4">
    <button class="btn btn-primary btn-lg">
      Siguiente <i data-feather="arrow-right"></i>
    </button>
  </div>
</form>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>feather.replace();
const fmt = v=>(+v).toLocaleString('en-US',{maximumFractionDigits:3});
sliderVc.oninput = ()=> outVc.textContent = fmt(sliderVc.value);
sliderFz.oninput = ()=> outFz.textContent = fmt(sliderFz.value);
</script>
</body></html>
