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
  <link rel="stylesheet" href="assets/css/wizard.css">
  <link rel="stylesheet" href="assets/css/stepper.css">
</head>
<body>

  <!-- Barra de pasos -->
  <div class="stepper-header d-flex align-items-center">
    <img src="assets/img/logos/logo_stepper.png" alt="Logo Stepper" class="logo-stepper">
    <div class="stepper-bar flex-grow-1">
      <nav class="stepper-container">
        <ul class="stepper">
          <?php foreach ($flow as $n): ?>
            <li
              data-step="<?= $n ?>"
              data-label="<?= htmlspecialchars($labels[$n], ENT_QUOTES) ?>"></li>
          <?php endforeach; ?>
        </ul>
      </nav>
    </div>
  </div>

  <!-- Botón reset -->
  <div style="text-align:right; padding:.5rem 1rem;">
    <a href="public/reset.php" class="btn btn-outline-light">
      <i data-feather="refresh-ccw" class="me-1"></i>
      Volver al inicio
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
  <script src="node_modules/feather-icons/dist/feather.min.js"></script>
  <script src="assets/js/stepper.js" defer></script>
  <script src="assets/js/dashboard.js" defer></script>
<link rel="stylesheet" href="assets/css/wizard.css">
<link rel="stylesheet" href="assets/css/step6.css"><!-- <---- AGREGALO ACÁ -->

</body>
</html>
