<?php
/**
 * File: views/select_mode.php
 * Vista para elegir “Manual” o “Automático”.
 * Se asume que $csrfToken ha sido definido por index.php antes de incluir.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Seleccionar Modo – Wizard CNC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/wizard.css">
  <link rel="stylesheet" href="assets/css/onboarding.css">
</head>
<body>
<main class="wizard-welcome">
  <h2 class="mb-4">¿Cómo querés operar?</h2>
  <p class="explanation">
    Elegí uno de los dos modos según tu experiencia previa:
  </p>
  <form method="post" action="index.php">
    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
    <div class="mode-options">
      <label class="mode-option">
        <input type="radio" name="tool_mode" value="manual" required>
        <i data-lucide="wrench" class="icon-sm"></i>
        <strong>Modo Manual</strong>
        <span>Elegilo si ya sabés qué herramienta vas a usar y querés configurarla directamente.</span>
      </label>
      <label class="mode-option">
        <input type="radio" name="tool_mode" value="auto" required>
        <i data-lucide="bot" class="icon-sm"></i>
        <strong>Modo Automático</strong>
        <span>Te guiamos paso a paso para elegir la herramienta ideal según tu experiencia, material y tipo de corte.</span>
      </label>
    </div>
    <button class="btn btn-primary btn-lg mt-4">Continuar</button>
  </form>
</main>
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
