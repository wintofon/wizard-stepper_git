<?php declare(strict_types=1);
/**
 * Paso 6 (mini, embebible) — mismo layout que step5
 * --------------------------------------------------
 * - 100 % la misma estructura visual: <main class="container py-4">
 * - Usa el mismo id="routerForm" para evitar que el stepper JS se rompa.
 * - Muestra 4 métricas (RPM, Feedrate, Vc, fz) en la misma grilla «row g-3»
 *   con los mismos input-group e input-group-text del paso 5.
 * - No hay controles editables — sólo span con datos calculados.
 * - Sigue respetando WIZARD_EMBEDDED: si está definido, sólo se imprime
 *   el <main> sin los scripts globales.
 */

/* 1) Sesión + flujo */
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

/* 2) Dependencias mínimas */
require_once __DIR__ . '/../../includes/db.php';                    // → $pdo
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Controller/ExpertResultController.php';

/* 3) CSRF */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* 4) Datos básicos 
/* $tool   = ToolModel::getTool($pdo, $_SESSION['tool_table'] ?? '', $_SESSION['tool_id'] ?? 0) ?? [];
$params = ExpertResultController::getResultData($pdo, $_SESSION) ?? [];

$code = htmlspecialchars($tool['tool_code'] ?? '—');
$name = htmlspecialchars($tool['name']      ?? '—');

$rpm  = number_format($params['rpm0']  ?? 0, 0, '.', '');
$feed = number_format($params['feed0'] ?? 0, 0, '.', '');
$vc   = number_format($params['vc0']   ?? 0, 1, '.', '');
fz    = number_format($params['fz0']   ?? 0, 4, '.', '');
*/
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
  include __DIR__ . '/../partials/styles.php';
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
    'assets/css/components/_step5.css',   // mismo css que usa el step 5
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
  <p class="step-desc">Parámetros calculados para tu combinación.</p>

  <form id="routerForm" method="post">
    <input type="hidden" name="step" value="6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Nombre herramienta (igual estilo que selección en step5) -->
    <div class="mb-4">
      <label class="form-label d-block">Herramienta seleccionada</label>
      <div class="btn btn-outline-secondary w-100 text-start disabled">
        <strong><?= $code ?></strong> &nbsp; <small class="text-muted"><?= $name ?></small>
      </div>
    </div>

    <!-- Grilla 4 métricas → misma estructura visual que step5 -->
    <div class="row g-3">
      <?php $rows=[
        ['rpm_show',  'RPM'      , $rpm , 'rev/min'],
        ['feed_show', 'Feedrate' , $feed, 'mm/min'],
        ['vc_show',   'Vc'       , $vc  , 'm/min'],
        ['fz_show',   'fz'       , $fz  , 'mm/z'],
      ];
      foreach($rows as [$id,$label,$value,$unit]): ?>
        <div class="col-md-3">
          <label class="form-label" for="<?= $id ?>"><?= $label ?></label>
          <div class="input-group">
            <span id="<?= $id ?>" class="form-control bg-light fw-bold text-end">
              <?= $value ?>
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
