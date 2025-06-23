<?php
/**
 * File: views/steps/auto/step6.php
 * Iteración 0 – Skeleton ultrabásico
 *
 * ▶ Propósito: validar que el fragmento embebido no rompa el DOM.
 * ▶ Sólo imprime un div y el objeto window.step6Params vacío.
 */

declare(strict_types=1);

/* Detectar modo embebido */
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

/* Datos vacíos por ahora */
$jsonParams = '{}';
$csrfToken   = '';

/* ----- MODO EMBEBIDO (llamado via load-step.php) ----- */
if ($embedded) {
    ?>
    <div class="step6 p-4">
        <h2 class="step-title">Step 6 – Skeleton OK ✅</h2>
        <p class="step-desc">Esta es la versión mínima embebida.</p>
    </div>
    <script>
        window.step6Params = <?= $jsonParams ?>;
        window.step6Csrf   = '<?= $csrfToken ?>';
    </script>
    <?php
    return;         // ¡Nada más!
}

/* ----- MODO STANDALONE (acceso directo) ----- */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Step 6 Skeleton</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:2rem;background:#f4f4f4}
        .step6{max-width:600px;margin:auto;background:#fff;padding:2rem;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
    </style>
</head>
<body>
    <div class="step6">
        <h2>Step 6 – Skeleton OK ✅</h2>
        <p>Versión mínima standalone. Si la ves completa, el wrapper funciona.</p>
    </div>
    <script>
        window.step6Params = <?= $jsonParams ?>;
        window.step6Csrf   = '<?= $csrfToken ?>';
    </script>
</body>
</html>
