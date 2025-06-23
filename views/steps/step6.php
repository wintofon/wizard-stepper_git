<?php declare(strict_types=1);

/**
 * Paso 6 (mini) – Resultados calculados
 * Requiere que el paso 5 haya guardado los datos en sesión.
 */

/* 1) Sesión segura y flujo */
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

/* 2) Dependencias */
require_once __DIR__ . '/../../includes/db.php';                 // → $pdo
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Controller/ExpertResultController.php';

/* 3) CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* 4) Datos calculados */
$tool = ToolModel::getTool($pdo, $_SESSION['tool_table'], $_SESSION['tool_id']) ?? [];
$par  = ExpertResultController::getResultData($pdo, $_SESSION);

$code = htmlspecialchars($tool['tool_code'] ?? '—');
$name = htmlspecialchars($tool['name']      ?? '—');
$rpm  = number_format($par['rpm0'],  0, '.', '');
$vf   = number_format($par['feed0'], 0, '.', '');
$vc   = number_format($par['vc0'],   1, '.', '');
$fz   = number_format($par['fz0'],   4, '.', '');
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 6 – Resultados</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/objects/step-common.css">
<link rel="stylesheet" href="assets/css/components/_step6.css"><!-- tu hoja mínima -->
</head><body>
<main class="container py-4">

<h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
<p class="step-desc">Revisá los parámetros calculados y continuá.</p>

<!-- Tarjeta resumen -->
<div class="card mb-4 shadow-sm">
  <div class="card-body text-center">
    <h5 class="mb-1"><?= $code ?></h5>
    <small class="text-muted"><?= $name ?></small>
  </div>
</div>

<!-- Resultados en mismo grid que el paso 5 -->
<form class="needs-validation" method="post" novalidate>
  <input type="hidden" name="step" value="6">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

  <div class="row g-3">
    <?php
      $fields = [
        ['RPM',        $rpm, 'rpm'],
        ['Feedrate',   $vf , 'mm/min'],
        ['Vc',         $vc , 'm/min'],
        ['fz',         $fz , 'mm/z'],
      ];
      foreach ($fields as [$label,$val,$unit]): ?>
      <div class="col-md-3">
        <label class="form-label"><?= $label ?></label>
        <div class="input-group">
          <span class="form-control bg-light fw-bold text-end"><?= $val ?></span>
          <span class="input-group-text"><?= $unit ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

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
