<?php declare(strict_types=1);

/**
 * File: views/steps/step6.php
 *
 * Paso 6 (Auto) – Resumen previo al cálculo de parámetros.
 * Versión liviana: NO accede a BD ni a modelos; todo sale de $_SESSION.
 * Sin modo embebido: siempre genera página completa.
 */

/* ───── 0) BASE_URL y constantes ───── */
if (!getenv('BASE_URL')) {
    putenv('BASE_URL=' . rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/'));
}
define('BASE_URL', getenv('BASE_URL'));
define('BASE_HOST', $_SERVER['HTTP_HOST'] ?? 'localhost');

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

/* ───── 2) Cabeceras de seguridad ───── */
header('Content-Type: text/html; charset=UTF-8');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Permissions-Policy: geolocation=(), microphone=()");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header(
    "Content-Security-Policy: default-src 'self';"
  . " script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;"
  . " style-src  'self' 'unsafe-inline' https://cdn.jsdelivr.net;"
);

/* ───── 3) Debug opcional ───── */
if (filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN)) {
    error_reporting(E_ALL);
}

/* ───── 4) CSRF token ───── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

/* ───── 5) Resumen de datos guardados ───── */
$toolName = $_SESSION['tool_code']     ?? '—';
$material = $_SESSION['material_name'] ?? '—';
$strategy = $_SESSION['strategy_name'] ?? '—';
$txName   = $_SESSION['trans_name']    ?? '—';
$rpmMin   = $_SESSION['rpm_min']       ?? '—';
$rpmMax   = $_SESSION['rpm_max']       ?? '—';
$feedMax  = $_SESSION['feed_max']      ?? '—';
$hp       = $_SESSION['hp']            ?? '—';

/* ───── 6) Procesar POST (Avanzar) ───── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('CSRF inválido');
    }
    $_SESSION['wizard_progress'] = 6;
    session_write_close();
    header('Location: step7.php');
    exit;
}

/* ───── 7) Estilos (misma lista que otros pasos) ───── */
$styles = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'assets/css/settings/settings.css',
    'assets/css/generic/generic.css',
    'assets/css/objects/step-common.css',
    'assets/css/components/_step6.css',
];

/* ───── 8) Salida HTML ───── */
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Paso 6 – Revisá y continuá</title>
<?php
  include __DIR__ . '/../partials/styles.php';
?>
<script>
  window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
  window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
</script>
</head>
<body>
<main class="container py-4">
  <h2 class="step-title"><i data-feather="check-circle"></i> Revisá tu configuración</h2>
  <p class="step-desc">Si todo está correcto, seguí al cálculo de parámetros.</p>

  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <h5 class="mb-3">Resumen</h5>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item d-flex justify-content-between">
          <span><i data-feather="tool"></i> Herramienta:</span>
          <strong><?= htmlspecialchars($toolName) ?></strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i data-feather="layers"></i> Material:</span>
          <strong><?= htmlspecialchars($material) ?></strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i data-feather="activity"></i> Estrategia:</span>
          <strong><?= htmlspecialchars($strategy) ?></strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i data-feather="cpu"></i> Transmisión:</span>
          <strong><?= htmlspecialchars($txName) ?></strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i data-feather="refresh-ccw"></i> RPM mín – máx:</span>
          <strong><?= htmlspecialchars($rpmMin) ?> – <?= htmlspecialchars($rpmMax) ?></strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i data-feather="truck"></i> Feedrate máx:</span>
          <strong><?= htmlspecialchars($feedMax) ?> mm/min</strong>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i data-feather="zap"></i> Potencia disponible:</span>
          <strong><?= htmlspecialchars($hp) ?> HP</strong>
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
