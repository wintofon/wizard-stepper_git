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
  <link rel="stylesheet" href="assets/css/footer-schneider.css">
</head>
<body>

  <!-- Barra de pasos -->
  <header class="stepper-header d-flex align-items-center">
    <img
      src="assets/img/logos/logo_stepper.png"
      alt="Logo Stepper"
      class="logo-stepper">
    <nav class="stepper-bar flex-grow-1">
      <ul class="stepper">
        <?php foreach ($flow as $n): ?>
          <li
            data-step="<?= $n ?>"
            data-label="<?= htmlspecialchars($labels[$n], ENT_QUOTES) ?>"></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </header>

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
  <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
  <script>feather.replace();</script>
  <script src="assets/js/stepper.js" defer></script>
  <script src="assets/js/dashboard.js" defer></script>
<link rel="stylesheet" href="assets/css/wizard.css">
<link rel="stylesheet" href="assets/css/step6.css"><!-- <---- AGREGALO ACÁ -->

  <footer class="footer-schneider mt-5">
    <div class="container py-4">
      <div class="row text-center text-md-start">
        <div class="col-md-4 mb-3 mb-md-0">
          <h4 class="footer-title mb-1">
            <i data-feather="cpu" class="me-1"></i>SchneiderCNC
          </h4>
          <p class="mb-0">Alejandro Martín Schneider</p>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
          <h6 class="footer-subtitle text-uppercase mb-2">Recursos</h6>
          <ul class="list-unstyled">
            <li><a href="/libros" class="footer-link"><i data-feather="book" class="me-1"></i>Libros</a></li>
            <li><a href="/cursos-presenciales" class="footer-link"><i data-feather="users" class="me-1"></i>Cursos presenciales</a></li>
            <li><a href="/cursos-online" class="footer-link"><i data-feather="monitor" class="me-1"></i>Cursos online</a></li>
            <li><a href="/herramental" class="footer-link"><i data-feather="shopping-cart" class="me-1"></i>Venta de herramental</a></li>
          </ul>
        </div>
        <div class="col-md-4 d-flex align-items-md-end justify-content-md-end">
          <p class="small text-secondary mb-0">© SchneiderCNC <?= date('Y') ?></p>
        </div>
      </div>
    </div>
  </footer>

</body>
</html>
