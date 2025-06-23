<?php
/**
 * File: views/wizard_layout.php
 * CNC Wizard – Layout maestro que envuelve todos los pasos.
 * ------------------------------------------------------------------
 * Cambio mínimo (jun-23-2025):
 *   • Se añadió la clase CSS "has-stepper" al <body> para que
 *     wizard.css pueda aplicar automáticamente el padding-top y
 *     evitar que el contenido quede oculto bajo la barra sticky.
 *     ─ No afecta a ninguna otra lógica ni rompe sesiones.
 * ------------------------------------------------------------------
 * Tip: Si alguna otra vista incluye su propio header sticky, 
 *      reaprovechá esta misma clase para mantener consistencia.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wizard CNC</title>

  <!-- ====== CSS base & componentes ====== -->
  <link rel="stylesheet" href="<?= asset('assets/css/settings/settings.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/generic/generic.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/elements/elements.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/objects.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/wizard.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/stepper.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/step-common.css') ?>">
  <!-- Estilos extra del Paso 6 (se mantienen por compatibilidad) -->
  <link rel="stylesheet" href="<?= asset('assets/css/objects/step6.css') ?>">
  <!-- Bootstrap sólo para vistas que lo requieran -->
  <link rel="stylesheet" href="<?= asset('assets/css/generic/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/components/components.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/components/main.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/components/footer-schneider.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/utilities/utilities.css') ?>">

  <script>
    window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
  </script>
</head>
<!-- 👇 NUEVA CLASE "has-stepper"  -->
<body class="has-stepper">

  <!-- Barra de pasos (sticky) -->
  <header class="stepper-header d-flex align-items-center">
    <img src="<?= asset('assets/img/logos/logo_stepper.png') ?>" alt="Logo Stepper" class="logo-stepper">
    <nav class="stepper-bar flex-grow-1">
      <ul class="stepper">
        <?php foreach ($flow as $n): ?>
          <li data-step="<?= $n ?>" data-label="<?= htmlspecialchars($labels[$n], ENT_QUOTES) ?>"></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </header>

  <!-- Botón reset (limpia sesión + localStorage) -->
  <div class="reset-wrap">
    <a href="public/reset.php" class="btn btn-outline-light" onclick="localStorage.clear()">
      <i data-feather="refresh-ccw" class="me-1"></i> Volver al inicio
    </a>
  </div>

  <!-- Contenido dinámico de cada paso -->
  <main id="step-content" class="wizard-body"></main>

  <!-- Dashboard oculto (estado en consola) -->

  <!-- =================== JS =================== -->
  <?php if (!empty($_SESSION['csrf_token'])): ?>
  <script>window.csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';</script>
  <?php endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
  <script>feather.replace();</script>
  <script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script src="<?= asset('assets/js/wizard_stepper.js') ?>" defer></script>
  <script src="<?= asset('assets/js/dashboard.js') ?>" defer></script>

  <!-- Footer -->
  <footer class="footer-schneider text-white mt-5">
    <div class="container py-4">
      <div class="row align-items-center">
        <!-- Col 1: datos del autor -->
        <div class="col-md-6 mb-4 mb-md-0">
          <img src="<?= asset('assets/img/logos/logo_stepper.png') ?>" alt="SchneiderCNC logo" class="footer-logo">
          <h4 class="fw-bold mb-2">SchneiderCNC</h4>
          <p class="mb-1">Alejandro Martín Schneider</p>
          <p class="mb-1">Argentina</p>
          <p class="mb-1">📞 +54 9 351 000 0000</p>
          <p class="mb-1">📧 contacto@schneidercnc.com</p>
          <p class="small text-secondary mt-2">© SchneiderCNC <?= date('Y') ?> · Todos los derechos reservados</p>
        </div>
        <!-- Col 2: productos -->
        <div class="col-md-3 mb-4 mb-md-0">
          <h6 class="text-uppercase fw-semibold">Productos</h6>
          <ul class="list-unstyled small">
            <li><a href="/libros"            class="footer-link">📘 Mis libros</a></li>
            <li><a href="/cursos-presenciales" class="footer-link">🏫 Cursos presenciales</a></li>
            <li><a href="/cursos-online"       class="footer-link">💻 Cursos online</a></li>
            <li><a href="/herramental"         class="footer-link">🛠️ Venta de herramental</a></li>
          </ul>
        </div>
        <!-- Col 3: links legales -->
        <div class="col-md-3">
          <h6 class="text-uppercase fw-semibold">Sitio</h6>
          <ul class="list-unstyled small">
            <li><a href="/contacto"   class="footer-link">Contacto</a></li>
            <li><a href="/terminos"    class="footer-link">Términos y condiciones</a></li>
            <li><a href="/cookies"    class="footer-link">Cookies</a></li>
            <li><a href="/privacidad" class="footer-link">Política de privacidad</a></li>
          </ul>
        </div>
      </div>
    </div>
  </footer>

</body>
</html>
