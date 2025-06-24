<?php
/**
 * File: wizard/views/wizard_layout.php
 * --------------------------------------------------------------------------
 * Layout principal del CNC Wizard Stepper
 * --------------------------------------------------------------------------
 * • Renderiza un contenedor <main id="step-content"> para vistas AJAX.
 * • Carga CSS/JS global SOLO una vez; evita duplicar <html>/<body> en fetch.
 * • Exposición segura de DEBUG y CSRF para JavaScript.
 * • Utiliza sendSecurityHeaders() y startSecureSession() en bootstrap.
 */
declare(strict_types=1);

// Estado de debug (definido en bootstrap)
$debugMode = defined('DEBUG') && DEBUG;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php sendSecurityHeaders(); ?>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wizard CNC</title>

  <!-- CSS Globales -->
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
<body class="d-flex flex-column min-vh-100">

  <!-- Header con logo y barra de pasos -->
  <header class="navbar navbar-dark bg-dark p-2">
    <div class="container-fluid d-flex align-items-center">
      <img src="<?= asset('assets/img/logos/logo_stepper.png') ?>" alt="Logo" height="40" class="me-3">
      <nav class="flex-fill">
        <ul class="stepper list-unstyled d-flex mb-0 justify-content-between">
          <?php foreach ($flow as $step): ?>
            <li data-step="<?= (int)$step ?>"
                data-label="<?= htmlspecialchars($labels[$step] ?? '', ENT_QUOTES) ?>"></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <button type="button" class="btn btn-outline-light ms-3" onclick="localStorage.clear(); loadStep(1);">
        <i data-feather="refresh-ccw"></i>
      </button>
    </div>
  </header>

  <!-- Contenedor dinámico (vistas embebidas) -->
  <main id="step-content" class="flex-fill container py-4"></main>

  <!-- Debug console -->
  <?php if ($debugMode): ?>
    <div class="debug-box bg-secondary text-white p-2">
      <pre id="debug"></pre>
    </div>
  <?php endif; ?>

  <!-- CSRF para JS -->
  <?php if (!empty($_SESSION['csrf_token'])): ?>
    <script>window.csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>';</script>
  <?php endif; ?>

  <!-- JS Globales (defer) -->
  <script src="<?= asset('node_modules/feather-icons/dist/feather.min.js') ?>" defer></script>
  <script defer>document.addEventListener('DOMContentLoaded', () => feather.replace());</script>
  <script src="<?= asset('assets/js/bootstrap.bundle.min.js') ?>" defer></script>
  <script src="<?= asset('assets/js/wizard_stepper.js') ?>" defer></script>
  <script src="<?= asset('assets/js/dashboard.js') ?>" defer></script>

  <!-- Footer opcional: siempre al fondo -->
  <footer class="mt-auto bg-light py-3">
    <div class="text-center small text-muted">
      © SchneiderCNC <?= date('Y') ?> · Todos los derechos reservados
    </div>
  </footer>

</body>
</html>
