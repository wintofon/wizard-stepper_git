<?php declare(strict_types=1);
/**
 * Paso 6 (mini + embebible) – Resultados calculados
 * --------------------------------------------------
 * ▸ Si se llama vía load-step.php define WIZARD_EMBEDDED y sólo se
 *   genera el fragmento interior (igual que los pasos 1-4).
 * ▸ Si se accede directo, renderiza la página completa con <head>,
 *   tres <link> y el mismo layout Bootstrap que el paso 5.
 */

// ───────────────── Sesión & flujo ─────────────────
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

// ¿Llamado embebido?
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

// ───────────────── Dependencias ───────────────────
require_once __DIR__ . '/../../includes/db.php';              // → $pdo
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Controller/ExpertResultController.php';

// ───────────────── CSRF ───────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ───────────────── Datos calculados ───────────────
$tool = ToolModel::getTool($pdo, $_SESSION['tool_table'], $_SESSION['tool_id']) ?? [];
$par  = ExpertResultController::getResultData($pdo, $_SESSION);

$code = htmlspecialchars($tool['tool_code'] ?? '—');
$name = htmlspecialchars($tool['name']      ?? '—');
$rpm  = number_format($par['rpm0'],  0, '.', '');
$vf   = number_format($par['feed0'], 0, '.', '');
$vc   = number_format($par['vc0'],   1, '.', '');
$fz   = number_format($par['fz0'],   4, '.', '');

// ───────────────── Salida HTML ────────────────────
if (!$embedded): ?>
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
<?php endif; ?>

<?php if (!$embedded): ?>
  <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
  <p class="step-desc">Revisá los parámetros calculados y continuá.</p>
<?php endif; ?>

<!-- Tarjeta resumen (visible tanto embebido como full) -->
<div class="card mb-4 shadow-sm">
  <div class="card-body text-center">
    <h5 class="mb-1"><?= $code ?></h5>
    <small class="text-muted"><?= $name ?></small>
  </div>
</div>

<!-- Grid de resultados (idéntico al paso 5) -->
<form method="post" class="needs-validation" novalidate>
  <input type="hidden" name="step" value="6">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

  <div class="row g-3">
    <?php $f=[ ['RPM',$rpm,'rpm'], ['Feedrate',$vf,'mm/min'], ['Vc',$vc,'m/min'], ['fz',$fz,'mm/z'] ];
    foreach($f as [$l,$v,$u]): ?>
      <div class="col-md-3">
        <label class="form-label"><?= $l ?></label>
        <div class="input-group">
          <span class="form-control bg-light fw-bold text-end"><?= $v ?></span>
          <span class="input-group-text"><?= $u ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (!$embedded): ?>
    <div class="text-end mt-4">
      <button class="btn btn-primary btn-lg">
        Siguiente <i data-feather="arrow-right" class="ms-1"></i>
      </button>
    </div>
  <?php endif; ?>
</form>

<?php if (!$embedded): ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>feather.replace();</script>
</body></html>
<?php endif; ?>
