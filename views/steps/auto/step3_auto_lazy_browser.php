<?php
/**
 * File: step3_auto_lazy_browser.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../src/Utils/Session.php';
require_once __DIR__ . '/../../../includes/wizard_helpers.php';

startSecureSession();
$csrf = generateCsrfToken();

$materialId = $_SESSION['material_id'] ?? null;
$strategyId = $_SESSION['strategy_id'] ?? null;
$thickness  = $_SESSION['thickness']  ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Herramientas compatibles</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
  <?php
    $styles = [
      'assets/css/components/_step3.css',
    ];
    $embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
    include __DIR__ . '/../../partials/styles.php';
  ?>
  <?php if (!$embedded): ?>
  <script>
    window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
  </script>
  <?php endif; ?>
</head>
<body>
  <main class="container py-4">
    <h2>Herramientas compatibles</h2>
    <div class="mb-3">
      <label for="diaFilter" class="form-label">Filtrar por diámetro</label>
      <select id="diaFilter" class="form-select">
        <option value="">— Todos —</option>
      </select>
    </div>
    <div id="toolContainer"
         data-material="<?= (int)$materialId ?>"
         data-strategy="<?= (int)$strategyId ?>"
         data-thickness="<?= htmlspecialchars((string)$thickness) ?>">
    </div>
    <div id="scrollSentinel"></div>
  </main>
  <script type="module" src="<?= asset('assets/js/step3_auto_lazy_loader.js') ?>"></script>
</body>
</html>
