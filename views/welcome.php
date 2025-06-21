<?php require_once __DIR__ . '/../includes/init.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bienvenido – Wizard CNC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
  <link rel="stylesheet" href="<?= asset('assets/css/wizard.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/onboarding.css') ?>">
  <script>window.BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>
  <main class="wizard-welcome">
    <i data-lucide="wrench" class="icon-xl"></i>
    <h1 class="title">Bienvenido al Asistente CNC</h1>
    <p class="lead">
      Este asistente interactivo te guiará paso a paso para configurar tu herramienta de corte ideal, optimizando el mecanizado según el material, tipo de fresa y estrategia.
    </p>
    <div class="disclaimer">
      <p><strong>📌 Importante:</strong> Este sistema está pensado para routers CNC de gama media-baja. Si usás maquinaria industrial o fresas especiales, validá los parámetros con el fabricante.</p>
    </div>
    <button id="btn-start" class="btn btn-primary btn-lg mt-4">Iniciar</button>
  </main>
  <script src="<?= asset('assets/js/welcome_init.js') ?>"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>lucide.createIcons();</script>
</body>
</html>
