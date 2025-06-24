<?php
/**
 * File: wizard_layout.php
 * -----------------------------------------------------------------------------
 * RESPONSABILIDAD:
 *   • Plantilla principal del CNC Wizard (modo Stepper)
 *   • Define HEAD, layout general, header fijo, contenedor de pasos dinámico,
 *     footer corporativo, y lógica JS base (feather, tokens, scripts globales)
 *
 * CONEXIONES:
 *   – Requiere que esté definida BASE_URL y BASE_HOST en entorno PHP
 *   – Usa asset() para vincular recursos con ruta absoluta y segura
 *   – $flow → array con los pasos activos (int)
 *   – $labels → etiquetas legibles para cada paso
 *   – $_SESSION['csrf_token'] → protección contra CSRF
 *   – $DEBUG → muestra consola interna si está activo
 *
 * PUNTOS CLAVE:
 *   – No rompe DOM (estructura limpia y consistente)
 *   – Scripts en orden lógico, sin conflictos de carga
 *   – Totalmente compatible con `load-step.php` (DOM embebido)
 * -----------------------------------------------------------------------------
 */

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <!-- META ESTÁNDAR -->
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wizard CNC</title>

  <!-- ESTILOS MODULARES -->
  <link rel="stylesheet" href="<?= asset('assets/css/settings/settings.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/generic/generic.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/elements/elements.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/objects.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/wizard.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/stepper.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/step-common.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/step6.css') ?>"> <!-- ⚠️ solo afecta paso 6 -->
  <link rel="stylesheet" href="<?= asset('assets/css/components/components.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/components/main.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/components/footer-schneider.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/utilities/utilities.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/generic/bootstrap.min.css') ?>">

  <!-- VARIABLES GLOBALES JS (inyectadas desde PHP con encoding seguro) -->
  <script>
    window.BASE_URL  = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST, JSON_UNESCAPED_SLASHES) ?>;
    window.DEBUG     = <?= $DEBUG ? 'true' : 'false' ?>;
  </script>
</head>

<body>

  <!-- BARRA DE PASOS SUPERIOR -->
  <header class="stepper-header d-flex align-items-center">
    <img src="<?= asset('assets/img/logos/logo_stepper.png') ?>" alt="Logo Stepper" class="logo-stepper">
    <nav class="stepper-bar flex-grow-1">
      <ul class="stepper">
        <?php foreach ($flow as $n): ?>
          <li data-step="<?= (int)$n ?>" data-label="<?= htmlspecialchars($labels[$n], ENT_QUOTES, 'UTF-8') ?>"></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </header>

  <!-- BOTÓN DE RESET -->
  <div class="reset-wrap">
    <a href="public/reset.php" class="btn btn-outline-light" onclick="localStorage.clear()">
      <i data-feather="refresh-ccw" class="me-1"></i>
      Volver al inicio
    </a>
  </div>

  <!-- CONTENIDO INYECTADO POR JS -->
  <main id="step-content" class="wizard-body"></main>

  <!-- CONSOLA DEBUG OPCIONAL -->
  <?php if ($DEBUG): ?>
    <div class="debug-box">
      <pre id="debug"></pre>
    </div>
  <?php endif; ?>

  <!-- TOKEN CSRF GLOBAL -->
  <?php if (!empty($_SESSION['csrf_token'])): ?>
    <script>
      window.csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>';
    </script>
  <?php endif; ?>

  <!-- ICONOS VECTORIALES -->
  <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
  <script>requestAnimationFrame(() => feather.replace());</script>

  <!-- BOOTSTRAP Y JS CORE -->
  <script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>" defer></script>

  <!-- LIBRERÍAS EXTRAS SOLO PARA GRÁFICAS (Paso 6 u otros) -->
  <script src="<?= asset('node_modules/chart.js/dist/chart.umd.min.js') ?>" defer></script>
  <script src="<?= asset('node_modules/countup.js/dist/countUp.umd.js') ?>" defer></script>

  <!-- SCRIPT GLOBAL DEL STEPPER -->
  <script src="<?= asset('assets/js/wizard_stepper.js') ?>" defer></script>
  <script src="<?= asset('assets/js/dashboard.js') ?>" defer></script>

  <!-- FOOTER CORPORATIVO (NO INTERFIERE CON EL MAIN) -->
  <footer class="footer-schneider text-white mt-5">
    <div class="container py-4">
      <div class="row align-items-center">

        <!-- COLUMNA 1: IDENTIDAD -->
        <div class="col-md-6 mb-4 mb-md-0">
          <img src="<?= asset('assets/img/logos/logo_stepper.png') ?>" alt="SchneiderCNC logo" class="footer-logo">
          <h4 class="fw-bold mb-2">SchneiderCNC</h4>
          <p class="mb-1">Alejandro Martín Schneider</p>
          <p class="mb-1">Córdoba, Argentina</p>
          <p class="mb-1">📞 +54 9 351 000 0000</p>
          <p class="mb-1">📧 contacto@schneidercnc.com</p>
          <p class="small text-secondary mt-2">© SchneiderCNC <?= date('Y') ?> · Todos los derechos reservados</p>
        </div>

        <!-- COLUMNA 2: PRODUCTOS -->
        <div class="col-md-3 mb-4 mb-md-0">
          <h6 class="text-uppercase fw-semibold">Productos</h6>
          <ul class="list-unstyled small">
            <li><a href="/libros" class="footer-link">📘 Mis libros</a></li>
            <li><a href="/cursos-presenciales" class="footer-link">🏫 Cursos presenciales</a></li>
            <li><a href="/cursos-online" class="footer-link">💻 Cursos online</a></li>
            <li><a href="/herramental" class="footer-link">🛠️ Venta de herramental</a></li>
          </ul>
        </div>

        <!-- COLUMNA 3: ENLACES LEGALES -->
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
