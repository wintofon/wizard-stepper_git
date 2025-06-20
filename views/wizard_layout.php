<?php
/* File: wizard/views/wizard_layout.php */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wizard CNC</title>
  <link rel="stylesheet" href="<?= asset('assets/css/main.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/wizard.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/stepper.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/footer-schneider.css') ?>">
  <script>
    window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
  </script>
</head>
<body>

  <!-- Barra de pasos -->
  <header class="stepper-header d-flex align-items-center">
    <img
      src="<?= asset('assets/img/logos/logo_stepper.png') ?>"
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
    <a href="public/reset.php" class="btn btn-outline-light" onclick="localStorage.clear()">
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
  <script src="<?= asset('assets/js/wizard_stepper.js') ?>" defer></script>
  <script src="<?= asset('assets/js/dashboard.js') ?>" defer></script>
<link rel="stylesheet" href="<?= asset('assets/css/wizard.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/pages/_step6.css') ?>"><!-- <---- AGREGALO ACÁ -->

  <footer class="footer-schneider text-white mt-5">
    <div class="container py-4">
      <div class="row align-items-center">

        <div class="col-md-6 mb-4 mb-md-0">
          <img src="<?= asset('assets/img/logos/logo_stepper.png') ?>" alt="SchneiderCNC logo" class="footer-logo">
          <h4 class="fw-bold mb-2">SchneiderCNC</h4>
          <p class="mb-1">Alejandro Martín Schneider</p>
          <p class="mb-1">Córdoba, Argentina</p>
          <p class="mb-1">📞 +54 9 351 000 0000</p>
          <p class="mb-1">📧 contacto@schneidercnc.com</p>
          <p class="small text-secondary mt-2">© SchneiderCNC <?= date('Y') ?> · Todos los derechos reservados</p>
        </div>

        <div class="col-md-3 mb-4 mb-md-0">
          <h6 class="text-uppercase fw-semibold">Productos</h6>
          <ul class="list-unstyled small">
            <li><a href="/libros" class="footer-link">📘 Mis libros</a></li>
            <li><a href="/cursos-presenciales" class="footer-link">🏫 Cursos presenciales</a></li>
            <li><a href="/cursos-online" class="footer-link">💻 Cursos online</a></li>
            <li><a href="/herramental" class="footer-link">🛠️ Venta de herramental</a></li>
          </ul>
        </div>

        <div class="col-md-3">
          <h6 class="text-uppercase fw-semibold">Sitio</h6>
          <ul class="list-unstyled small">
            <li><a href="/contacto" class="footer-link">Contacto</a></li>
            <li><a href="/terminos" class="footer-link">Términos y condiciones</a></li>
            <li><a href="/cookies" class="footer-link">Cookies</a></li>
            <li><a href="/privacidad" class="footer-link">Política de privacidad</a></li>
          </ul>
        </div>

      </div>
    </div>
  </footer>

</body>
</html>
