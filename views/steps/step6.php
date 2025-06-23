<?php declare(strict_types=1);
/**
 * Paso 6 (mini, embebible) — versión todavía más simple
 * ------------------------------------------------------
 * - Mantiene la misma estructura visual y los mismos IDs/classes clave que
 *   usa *step5* para que el stepper JS no se rompa.
 * - Sin tarjetas, sin colapsables ni radar-chart: solo los cuatro valores
 *   principales listos para copiar.
 * - Compatible con modo embebido: si se define WIZARD_EMBEDDED solo se imprime
 *   el <main>, sin <html>, <head> ni scripts globales.
 */

/* ── 1) Sesión & flujo ─────────────────────────────────────────────── */
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

/* ── 2) Dependencias mínimas ───────────────────────────────────────── */
require_once __DIR__ . '/../../includes/db.php';                    // → $pdo
require_once __DIR__ . '/../../src/Model/ToolModel.php';
require_once __DIR__ . '/../../src/Controller/ExpertResultController.php';

/* ── 3) CSRF ───────────────────────────────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

/* ── 4) Datos calculados ──────────────────────────────────────────── */
$tool   = ToolModel::getTool($pdo, $_SESSION['tool_table'] ?? '', (int)($_SESSION['tool_id'] ?? 0)) ?? [];
$params = ExpertResultController::getResultData($pdo, $_SESSION) ?? [];

$code = htmlspecialchars($tool['tool_code'] ?? '—');
$name = htmlspecialchars($tool['name']      ?? '—');

$rpm  = number_format((float)($params['rpm0']  ?? 0), 0, '.', '');
$feed = number_format((float)($params['feed0'] ?? 0), 0, '.', '');
$vc   = number_format((float)($params['vc0']   ?? 0), 1, '.', '');
$fz   = number_format((float)($params['fz0']   ?? 0), 4, '.', '');

$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
?>
<?php if (!$embedded): ?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Paso 6 – Resultados</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php
  $styles = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'assets/css/objects/step-common.css',
    'assets/css/components/_step5.css',  // reutilizamos el CSS del paso 5
  ];
  include __DIR__ . '/../partials/styles.php';
?>
</head><body>
<?php endif; ?>

<main class="container py-4">
  <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
  <p class="step-desc">Parámetros calculados para tu combinación.</p>

  <form id="routerForm" method="post">
    <input type="hidden" name="step" value="6">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

    <!-- Herramienta seleccionada (mismo look que radios del paso 5) -->
    <div class="mb-4">
      <label class="form-label d-block">Herramienta</label>
      <div class="btn btn-outline-secondary w-100 text-start disabled">
        <strong><?= $code ?></strong>
        <small class="text-muted ms-2"><?= $name ?></small>
      </div>
    </div>

    <!-- 4 métricas principales (mismo grid) -->
    <div class="row g-3">
<?php foreach ([
  ['rpm_show',  'RPM',       $rpm,  'rev/min'],
  ['feed_show', 'Feedrate',  $feed, 'mm/min'],
  ['vc_show',   'Vc',        $vc,   'm/min'],
  ['fz_show',   'fz',        $fz,   'mm/z'],
] as [$id,$label,$val,$unit]): ?>
      <div class="col-md-3">
        <label for="<?= $id ?>" class="form-label"><?= $label ?></label>
        <div class="input-group">
          <span id="<?= $id ?>" class="form-control bg-light fw-bold text-end">
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

<?php if (!$embedded): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>feather.replace();</script>
</body></html>
<?php endif; ?>
