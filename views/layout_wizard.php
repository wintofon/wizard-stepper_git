<?php
/* File: wizard/views/layout_wizard.php */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wizard CNC</title>
  <link rel="stylesheet" href="assets/css/base/reset.css">
  <link rel="stylesheet" href="assets/css/base/variables.css">
  <link rel="stylesheet" href="assets/css/base/layout.css">
  <link rel="stylesheet" href="assets/css/base/theme.css">
  <link rel="stylesheet" href="assets/css/components/wizard-stepper.css">
</head>
<body>

  <!-- Barra de pasos -->
  <nav class="stepper-container">
    <ul class="stepper">
      <?php foreach ($flow as $n): ?>
        <li
          data-step="<?= $n ?>"
          data-label="<?= htmlspecialchars($labels[$n], ENT_QUOTES) ?>"></li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <!-- Botón reset -->
  <div style="text-align:right; padding:.5rem 1rem;">
    <a href="public/reset.php" class="btn btn-outline-light">
      🔄 Volver al inicio
    </a>
  </div>

  <!-- Contenido dinámico -->
  <main id="step-content" class="wizard-body"></main>

  <!-- Dashboard (opcional) -->
  <section id="wizard-dashboard"></section>

  <!-- Consola interna -->
  <pre id="debug" class="debug-box"></pre>

  <!-- Scripts -->
  <?php if (!empty($_SESSION['csrf_token'])): ?>
  <script>
    window.csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';
  </script>
  <?php endif; ?>
  <script src="assets/js/stepper.js" defer></script>
  <script src="assets/js/dashboard.js" defer></script>
<link rel="stylesheet" href="assets/css/components/wizard-stepper.css">
<link rel="stylesheet" href="assets/css/step6.css"><!-- <---- AGREGALO ACÁ -->

</body>
</html>
