<?php declare(strict_types=1);

/**
 * Paso 6 (mini) – Mostrar resultados calculados
 *  - Usa la misma maquetación que step5.php.
 *  - Requiere que el paso 5 haya dejado todo en sesión.
 */

# 1) Sesión segura y flujo
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

# 2) Dependencias
require_once __DIR__.'/../../includes/db.php';                     // → $pdo
require_once __DIR__.'/../../src/Model/ToolModel.php';
require_once __DIR__.'/../../src/Controller/ExpertResultController.php';

# 3) CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

# 4) Datos herramienta + parámetros calculados
$tool   = ToolModel::getTool($pdo, $_SESSION['tool_table'], $_SESSION['tool_id']) ?? [];
$par    = ExpertResultController::getResultData($pdo, $_SESSION);
$jsonPar= json_encode($par, JSON_UNESCAPED_UNICODE);

# Resumen principales
$code = htmlspecialchars($tool['tool_code'] ?? '—');
$name = htmlspecialchars($tool['name']      ?? '—');
$rpm  = number_format($par['rpm0'],   0, '.', '');
$feed = number_format($par['feed0'],  0, '.', '');
$vc   = number_format($par['vc0'],    1, '.', '');
$fz   = number_format($par['fz0'],    4, '.', '');
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 6 – Resultados</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/objects/step-common.css">
<link rel="stylesheet" href="assets/css/components/_step6.css">
</head><body>
<main class="container py-4">
  <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
  <p class="step-desc">Revisá los parámetros calculados y continuá.</p>

  <!-- Alert de error genérico (muy raro que llegue aquí) -->
  <?php if (!$tool || !$par): ?>
    <div class="alert alert-danger">Error: datos incompletos.</div>
  <?php endif; ?>

  <!-- Tarjeta resumen de herramienta -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body text-center">
      <h5 class="mb-1"><?= $code ?></h5>
      <small class="text-muted"><?= $name ?></small>
    </div>
  </div>

  <!-- Resultados + “Siguiente” con misma disposición que el paso 5 -->
  <form method="post" class="needs-validation" novalidate>
    <input type="hidden" name="step" value="6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="row g-3">

      <!-- Métricas -->
      <div class="col-md-3">
        <label class="form-label">RPM</label>
        <div class="input-group">
          <span class="form-control bg-light fw-bold text-end"><?= $rpm ?></span>
          <span class="input-group-text">rpm</span>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Feedrate</label>
        <div class="input-group">
          <span class="form-control bg-light fw-bold text-end"><?= $feed ?></span>
          <span class="input-group-text">mm/min</span>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label">Vc</label>
        <div class="input-group">
          <span class="form-control bg-light fw-bold text-end"><?= $vc ?></span>
          <span class="input-group-text">m/min</span>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label">fz</label>
        <div class="input-group">
          <span class="form-control bg-light fw-bold text-end"><?= $fz ?></span>
          <span class="input-group-text">mm/z</span>
        </div>
      </div>

    </div><!-- /row -->

    <!-- Botón Siguiente -->
    <div class="text-end mt-4">
      <button class="btn btn-primary btn-lg">
        Siguiente <i data-feather="arrow-right" class="ms-1"></i>
      </button>
    </div>
  </form>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>feather.replace();</script>
</body></html>
