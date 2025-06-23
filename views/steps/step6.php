<?php
/**
 * File: views/steps/step6.php
 * Paso 6 – Resultados expertos del Wizard CNC
 * ------------------------------------------------------------------
 * 2025‑06‑23 – FIX visual barra sticky
 *   • El bloque principal ahora se envuelve con
 *       <div class="content-main pt-stepper">
 *     para que el título no quede tapado por el header pegajoso.
 *   • No se modificó ninguna otra lógica ni HTML.
 *   • Recordatorio CSS: asegurate de tener
 *       .pt-stepper{padding-top:var(--stepper-h);}  
 *     o body.has-stepper en wizard.css.
 * ------------------------------------------------------------------ */

declare(strict_types=1);

// ─────────────────────────── 1) BOOTSTRAP ────────────────────────────
if (!getenv('BASE_URL')) {
    // Sube 3 niveles: /views/steps/step6.php → /wizard-stepper_git
    putenv('BASE_URL=' . rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/'));
}
require_once __DIR__ . '/../../src/Config/AppConfig.php';

use App\Controller\ExpertResultController;
require_once __DIR__ . '/../../includes/wizard_helpers.php';

// ¿Vista embebida por load-step.php? ----------------------------------
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

// ────────────────────────── 2) SESIÓN + HEADERS ──────────────────────
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
if (!$embedded) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header("Permissions-Policy: geolocation=(), microphone=()");
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// ────────────────────────── 3) LÓGICA PHP ← SIN CAMBIOS ──────────────
//  Todo el bloque de validaciones, consultas BD y asignación de
//  variables ($serialNumber, $toolCode, etc.) permanece idéntico.
//  ⋮
//  (Para mantener este archivo en tamaño manejable se omite aquí,
//   pero debes conservar dicho bloque tal cual lo tenías.)
// --------------------------------------------------------------------

// ====================================================================
// =========================  COMIENZA SALIDA  =========================
// ====================================================================
?>
<?php if (!$embedded): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cutting Data – Paso 6</title>
  <?php /* include styles heredados /$styles */ ?>
</head>
<body>
<?php endif; ?>

<?php if ($assetErrors): ?>
  <div class="alert alert-warning text-dark m-3">
    <strong>⚠️ Archivos faltantes (se usarán CDNs):</strong>
    <ul>
      <?php foreach ($assetErrors as $err): ?>
        <li><?= htmlspecialchars($err, ENT_QUOTES) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="step6">
  <!-- AÑADIDO pt-stepper para margen superior automático -->
  <div class="content-main pt-stepper">
    <div class="container py-4">
      <h2 class="step-title"><i data-feather="bar-chart-2"></i> Resultados</h2>
      <p class="step-desc">Ajustá los parámetros y revisá los datos de corte.</p>

      <!-- ⚠️ A partir de aquí todo tu HTML original de Step 6 -->
      <!-- BLOQUE CENTRAL -->
      <!-- ……………………………………………………………………………………………………… -->
      <!-- Copia íntegra de tu markup existente (tarjetas, sliders, etc.) -->
      <!-- NO se altera nada salvo el padding-top agregado arriba. -->
      <!-- ……………………………………………………………………………………………………… -->

    </div><!-- /.container -->
  </div><!-- /.content-main.pt-stepper -->
</div><!-- /.step6 -->
<section id="wizard-dashboard"></section>

<!-- SCRIPTS (sin cambios) -------------------------------------------- -->
<script>window.step6Params = <?= $jsonParams ?>; window.step6Csrf = '<?= $csrfToken ?>';</script>
<script src="<?= $bootstrapJsRel ?>"></script>
<script src="<?= asset('node_modules/feather-icons/dist/feather.min.js') ?>"></script>
<script src="<?= asset('node_modules/chart.js/dist/chart.umd.min.js') ?>"></script>
<script src="<?= asset('node_modules/countup.js/dist/countUp.umd.js') ?>"></script>
<?php if (!$embedded): ?><script src="<?= $step6JsRel ?>"></script><?php endif; ?>
<script>feather.replace();</script>

<?php if (!$embedded): ?>
</body>
</html>
<?php endif; ?>
