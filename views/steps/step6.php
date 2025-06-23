<?php declare(strict_types=1);
/**
 * Paso 6 – Resultados (mini & embebible) — versión **clean**
 * ----------------------------------------------------------------
 * Replica la estructura exacta del paso 5 (grid Bootstrap en filas
 * de 4 columnas) para que el stepper no se deforme.
 *   • Si WIZARD_EMBEDDED está definido, simplemente escupe el <main>
 *     sin scripts globales y el wizard lo incrusta.
 *   • El bloque de métricas usa el mismo patrón visual que el paso 5
 *     (input-group con span valores).
 */

/* 1) Sesión y flujo */
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
require_once __DIR__ . '/../../includes/db.php'; // → $pdo
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Controller/ExpertResultController.php';

/* 3) CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

/* 4) Datos */
$tool = ToolModel::getTool($pdo, $_SESSION['tool_table'], $_SESSION['tool_id']) ?? [];
$par  = ExpertResultController::getResultData($pdo, $_SESSION) ?? [];

$code = htmlspecialchars($tool['tool_code'] ?? '—');
$name = htmlspecialchars($tool['name']      ?? '—');

$rpm  = number_format($par['rpm0']  ?? 0, 0, '.', '');
$feed = number_format($par['feed0'] ?? 0, 0, '.', '');
$vc   = number_format($par['vc0']   ?? 0, 1, '.', '');
$fz   = number_format($par['fz0']   ?? 0, 4, '.', '');

$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 6 – Resultados</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php
  $styles = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'assets/css/objects/step-common.css',
    'assets/css/components/_step6.css',
  ];
  include __DIR__ . '/../partials/styles.php';
?>
<?php if (!$embedded): ?>
<script>
  window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
  window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
</script>
<?php endif; ?>
</head><body>
<main class="container py-4">
  <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
  <p class="step-desc">Revisá los parámetros calculados y continuá.</p>

  <!-- Resumen herramienta → mismo formato que el paso 5  -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body text-center">
      <h5 class="mb-1 fw-bold"><?= $code ?></h5>
      <small class="text-muted"><?= $name ?></small>
    </div>
  </div>

  <!-- Grid con 4 métricas clave (estructura exacta paso 5) -->
  <form method="post" id="resultsForm" class="needs-validation" novalidate>
    <input type="hidden" name="step" value="6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

    <div class="row g-3">
      <?php $metrics=[
        ['rpm','RPM',      $rpm,  'rev/min'],
        ['feed','Feedrate',$feed, 'mm/min'],
        ['vc','Vc',        $vc,   'm/min'],
        ['fz','fz',        $fz,   'mm/z'],
      ];
      foreach($metrics as [$id,$label,$val,$unit]): ?>
        <div class="col-md-3">
          <label class="form-label" for="<?= $id ?>_show"><?= $label ?></label>
          <div class="input-group">
            <span id="<?= $id ?>_show" class="form-control bg-light fw-bold text-end">
              <?= $val ?>
            </span>
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
