<?php declare(strict_types=1);
/**
 * Paso 6 – Resultados (versión mínima y embebible)
 * ------------------------------------------------
 *   • Mantiene la MISMA estructura visual que el step5.php
 *     (doctype, <head>, 3 hojas de estilo, <main class="container">,
 *     h2 + p.step-desc, grid Bootstrap y botón Siguiente).
 *   • Si se carga desde load-step.php con WIZARD_EMBEDDED definida,
 *     styles.php generará sólo los <link> base, pero se conserva el
 *     mismo markup que usa el paso 5 (tal como se hace allí).
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
require_once __DIR__ . '/../../includes/db.php';               // → $pdo
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Controller/ExpertResultController.php';

/* 3) CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* 4) Datos herramienta + parámetros */
$tool  = ToolModel::getTool($pdo, $_SESSION['tool_table'], $_SESSION['tool_id']) ?? [];
$par   = ExpertResultController::getResultData($pdo, $_SESSION) ?? [];

$code  = htmlspecialchars($tool['tool_code'] ?? '—');
$name  = htmlspecialchars($tool['name']      ?? '—');
$rpm   = number_format($par['rpm0']  ?? 0, 0, '.', '');
$feed  = number_format($par['feed0'] ?? 0, 0, '.', '');
$vc    = number_format($par['vc0']   ?? 0, 1, '.', '');
fz    = number_format($par['fz0']   ?? 0, 4, '.', '');

/* 5) Estado embebido (igual que step5) */
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 6 – Resultados</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php
  /* styles exactamente como en el paso 5 */
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

  <!-- Tarjeta resumen -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body text-center">
      <h5 class="mb-1"><?= $code ?></h5>
      <small class="text-muted"><?= $name ?></small>
    </div>
  </div>

  <!-- Grid de métricas -->
  <form method="post" class="needs-validation" novalidate>
    <input type="hidden" name="step" value="6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="row g-3">
      <?php $arr=[ ['RPM',$rpm,'rpm'], ['Feedrate',$feed,'mm/min'], ['Vc',$vc,'m/min'], ['fz',$fz,'mm/z'] ];
      foreach($arr as [$lbl,$val,$unit]): ?>
        <div class="col-md-3">
          <label class="form-label"><?= $lbl ?></label>
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
