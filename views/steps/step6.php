<?php
/**
 * File: views/steps/auto/step6.php
 * Iteración 0-bis – Versión ultra-mínima (solo para test de DOM)
 *
 * ▶ Sin cabeceras extra, sin sesión, sin JSON dinámico.
 * ▶ Imprime un <div class="step6"> con texto fijo.
 * ▶ Permite ver si el fragmento embebido rompe el stepper o no.
 */

declare(strict_types=1);

$embedded  = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
$jsonParams = '{}';
$csrfToken  = '';

/* ------------------- MODO EMBEBIDO ------------------- */
if ($embedded) { ?>
<div class="step6" style="padding:2rem;">
  <h2 style="margin:0;font-family:Arial,Helvetica,sans-serif;">Step 6 – Ultra-mínimo ✅</h2>
  <p style="margin:0;font-family:Arial,Helvetica,sans-serif;">Fragmento embebido limpio.</p>
</div>
<script>
  window.step6Params = <?= $jsonParams ?>;
  window.step6Csrf   = '<?= $csrfToken ?>';
</script>
<?php return; }

/* ------------------- MODO STANDALONE ----------------- */
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="utf-8">
<title>Step 6 Ultra-mínimo</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
</head><body style="font-family:Arial,Helvetica,sans-serif;">
  <div class="step6" style="padding:2rem;">
    <h2 style="margin:0;">Step 6 – Ultra-mínimo ✅</h2>
    <p style="margin:0;">Versión standalone para test.</p>
  </div>
  <script>
    window.step6Params = <?= $jsonParams ?>;
    window.step6Csrf   = '<?= $csrfToken ?>';
  </script>
</body></html>
