<?php
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
  <link rel="stylesheet" href="<?= asset('assets/css/global.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/pages/_step3.css') ?>">
  <script>window.BASE_URL = '<?= BASE_URL ?>';</script>
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
    <pre id="debug" class="bg-dark text-info p-2 mt-4"></pre>
  </main>
  <script type="module" src="<?= asset('assets/js/lazy_tools_scroll.js') ?>"></script>
</body>
</html>
