<?php
/**
 * File: wizard/views/wizard_layout.php
 * --------------------------------------------------------------------------
 * Layout principal del CNC Wizard Stepper
 * --------------------------------------------------------------------------
 * • Emite un contenedor <main id="step-content"> para vistas embebidas.
 * • Carga CSS/JS global solo una vez, sin duplicar <html>/<head>/<body> en fetch.
 * • Exposición segura de DEBUG y CSRF, evitando romper el DOM.
 */

declare(strict_types=1);

// Modo debug desde constante definida en bootstrap
$debugMode = defined('DEBUG') && DEBUG;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wizard CNC</title>

  <!-- CSS globales -->
  <link rel="stylesheet" href="<?= asset('assets/css/settings/settings.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/generic/generic.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/wizard.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/stepper.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/objects/step-common.css') ?>">

  <!-- Estilos específicos de pasos dinámicos -->
  <link rel="stylesheet" href="<?= asset('assets/css/objects/step6.css') ?>">

  <!-- Componentes y utilidades -->
  <link rel="stylesheet" href="<?= asset('assets/css/components/main.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/utilities/utilities.css') ?>">

  <script>
    window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
    window.DEBUG     = <?= $debugMode ? 'true' : 'false' ?>;
  </script>
</head>
<body>

  <!-- Header con logo y barra de pasos -->
  <header class="d-flex align-items-center p-2 bg-dark text-white">
    <img src="<?= asset('assets/img/logos/logo_stepper.png') ?>" 
         alt="Logo Wizard" class="me-3" height="40">
    <nav class="flex-grow-1">
      <ul class="stepper list-unstyled d-flex mb-0">
        <?php foreach ($flow as $step): ?>
          <li data-step="<?= (int)$step ?>"
              data-label="<?= htmlspecialchars($labels[$step] ?? '', ENT_QUOTES) ?>"></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <button class="btn btn-outline-light ms-3" onclick="localStorage.clear(); loadStep(1)">
      <i data-feather="refresh-ccw"></i>
    </button>
  </header>

  <!-- Contenedor para contenido cargado vía AJAX -->
  <main id="step-content" class="w-100"></main>

  <!-- Debug console -->
  <?php if ($debugMode): ?>
    <div class="debug-box bg-dark text-light p-2">
      <pre id="debug"></pre>
    </div>
  <?php endif; ?>

  <!-- CSRF token expuesto para JS -->
  <?php if (!empty($_SESSION['csrf_token'])): ?>
    <script>window.csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>';</script>
  <?php endif; ?>

  <!-- JS global (solo se incluyen una vez) -->
  <script src="<?= asset('node_modules/feather-icons/dist/feather.min.js') ?>" defer></script>
  <script>document.addEventListener('DOMContentLoaded', () => feather.replace());</script>
  <script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>" defer></script>
  <script src="<?= asset('assets/js/wizard_stepper.js') ?>" defer></script>
  <script src="<?= asset('assets/js/dashboard.js') ?>" defer></script>

  <!-- Footer minimalista -->
  <footer class="text-center py-3 mt-auto bg-light">
    <small class="text-muted">© SchneiderCNC <?= date('Y') ?> · Todos los derechos reservados</small>
  </footer>

</body>
</html>
