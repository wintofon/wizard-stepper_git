<?php declare(strict_types=1);

/**
 * File: views/steps/step6_min.php
 *
 * Paso 6 (Auto) – Resumen de configuración antes de calcular datos
 *  – Requiere que el paso 5 haya completado y guardado las claves en sesión.
 *  – Muestra un resumen rápido de la máquina + material + herramienta.
 *  – Permite volver a editar (link a step5) o avanzar (POST → step7.php).
 *
 * ⚠️ No depende de modelos ni de la BD; todo sale de $_SESSION.
 */

/* ───── 1) Sesión segura y control de flujo ───── */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if ((int)($_SESSION['wizard_progress'] ?? 0) < 5) {
    header('Location: step1.php');
    exit;
}

/* ───── 2) CSRF token ───── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

/* ───── 3) Resumen de datos guardados ───── */
$toolName  = $_SESSION['tool_code']     ?? '—';
$material  = $_SESSION['material_name'] ?? '—';
$strategy  = $_SESSION['strategy_name'] ?? '—';
$txName    = $_SESSION['trans_name']    ?? '—';   // guardala en step5
$rpmMin    = $_SESSION['rpm_min']       ?? '—';
$rpmMax    = $_SESSION['rpm_max']       ?? '—';
$feedMax   = $_SESSION['feed_max']      ?? '—';
$hp        = $_SESSION['hp']            ?? '—';

/* ───── 4) Procesar POST (Avanzar) ───── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        die('CSRF inválido');
    }
    $_SESSION['wizard_progress'] = 6;
    session_write_close();
    header('Location: step7.php');
    exit;
}

/* ───── 5) Salida HTML ───── */
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Paso 6 – Revisá y continuá</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/objects/step-common.css">
<link rel="stylesheet" href="assets/css/components/_step6.css"><!-- opcional -->
</head>
<body>
<main class="container py-4">
  <h2 class="step-title"><i data-feather="check-circle"></i> Revisá tu configuración</h2>
  <p class="step-desc">Si todo está correcto, continuá al cálculo de parámetros.</p>

  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <h5 class="mb-3">Resumen</h5>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span>Herramienta:</span><strong><?= htmlspecialchars($toolName) ?></strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span>Material:</span><strong><?= htmlspecialchars($material) ?></strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span>Estrategia:</span><strong><?= htmlspecialchars($strategy) ?></strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span>Transmisión:</span><strong><?= htmlspecialchars($txName) ?></strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span>RPM mín – máx:</span>
          <strong><?= htmlspecialchars($rpmMin) ?> – <?= htmlspecialchars($rpmMax) ?> rpm</strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span>Feedrate máx:</span><strong><?= htmlspecialchars($feedMax) ?> mm/min</strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span>Potencia disponible:</span><strong><?= htmlspecialchars($hp) ?> HP</strong>
        </li>
      </ul>
    </div>
  </div>

  <div class="d-flex justify-content-between">
    <a href="step5.php" class="btn btn-outline-secondary">
      <i data-feather="arrow-left"></i> Volver a editar
    </a>

    <form method="post" class="mb-0">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <button class="btn btn-primary btn-lg">
        Siguiente <i data-feather="arrow-right"></i>
      </button>
    </form>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>feather.replace();</script>
</body>
</html>
