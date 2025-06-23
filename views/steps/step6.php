<?php
/**
 * File: views/steps/auto/step5.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Paso 5 (Auto) – Configuración del router CNC
 * ─────────────────────────────────────────────────────────────────────────────
 * RESPONSABILIDAD
 *   • Mostrar un formulario para elegir la transmisión y fijar límites de la máquina
 *   • Validar servidor↔cliente (CSRF + reglas de negocio)
 *   • Guardar la configuración en sesión y derivar al Paso 6
 *
 * DISEÑO
 *   ▸ Esta vista copia la misma estructura “blindada” del Step 6:
 *       – Detección WIZARD_EMBEDDED  → imprime sólo el bloque <div.step5>
 *       – Cabeceras CSP / HSTS / cache-killer cuando se carga completa
 *       – Loader de assets centralizado + feather.replace() mediante rAF
 *   ▸ Se reutiliza partials/styles.php para gestionar hojas de estilo.
 *
 * ESTILO CÓDIGO
 *   · Comentarios en español argentino, tono técnico-relajado.
 *   · dbg() disponible con ?debug=1
 */

declare(strict_types=1);

/*───────────────────────────────────────────────────────────────────────────────
 | 0)   BASE_URL y constantes globales (idéntico a Step 6)
 ───────────────────────────────────────────────────────────────────────────────*/
if (!getenv('BASE_URL')) {
    // /views/steps/auto/step5.php → sube 3 niveles → /wizard-stepper_git
    putenv(
        'BASE_URL=' . rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/')
    );
}
require_once __DIR__ . '/../../src/Config/AppConfig.php';

/*───────────────────────────────────────────────────────────────────────────────
 | 1)   SESIÓN SEGURA + CONTROL DE FLUJO
 ───────────────────────────────────────────────────────────────────────────────*/
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 5) {
    header('Location: step1.php');
    exit;
}

/*───────────────────────────────────────────────────────────────────────────────
 | 2)   ¿Vista embebida dentro de load-step.php?
 ───────────────────────────────────────────────────────────────────────────────*/
$embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;

/*───────────────────────────────────────────────────────────────────────────────
 | 3)   CABECERAS SECURITY – solamente en modo full-page
 ───────────────────────────────────────────────────────────────────────────────*/
if (!$embedded) {
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
        . " style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;"
    );
}

/*───────────────────────────────────────────────────────────────────────────────
 | 4)   DEBUG opcional
 ───────────────────────────────────────────────────────────────────────────────*/
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG && is_readable(__DIR__ . '/../../includes/debug.php')) {
    require_once __DIR__ . '/../../includes/debug.php';
    dbg('👋 Entrando a Step 5');
}

/*───────────────────────────────────────────────────────────────────────────────
 | 5)   TOKEN CSRF
 ───────────────────────────────────────────────────────────────────────────────*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/*───────────────────────────────────────────────────────────────────────────────
 | 6)   CONEXIÓN BD y carga de transmisiones
 ───────────────────────────────────────────────────────────────────────────────*/
$dbFile = __DIR__ . '/../../includes/db.php';
if (!is_readable($dbFile)) {
    http_response_code(500);
    exit('Error interno: falta archivo de conexión BD.');
}
require_once $dbFile;     // → $pdo
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Error interno: conexión BD no disponible.');
}

$txList = $pdo->query(
    'SELECT id, name, rpm_min, rpm_max, feed_max, hp_default
       FROM transmissions
   ORDER BY name'
)->fetchAll(PDO::FETCH_ASSOC) ?: [];

$validTx = [];
foreach ($txList as $t) {
    $validTx[(int)$t['id']] = [
        'rpm_min'  => (int)$t['rpm_min'],
        'rpm_max'  => (int)$t['rpm_max'],
        'feed_max' => (float)$t['feed_max'],
        'hp_def'   => (float)$t['hp_default'],
    ];
}

dbg('⚙️ Transmisiones cargadas: ' . count($txList));

/*───────────────────────────────────────────────────────────────────────────────
 | 7)   PROCESAR POST
 ───────────────────────────────────────────────────────────────────────────────*/
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* 7-1) CSRF */
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido. Actualizá la página.';
    }

    /* 7-2) Campo oculto step */
    if ((int)($_POST['step'] ?? 0) !== 5) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
    }

    /* 7-3) Sanitizar input */
    $id   = filter_input(INPUT_POST, 'transmission_id', FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min',         FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max',         FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max',        FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp',              FILTER_VALIDATE_FLOAT);

    /* 7-4) Reglas de negocio */
    if (!isset($validTx[$id]))                  $errors[] = 'Elegí una transmisión válida.';
    if (!$rpmn || $rpmn <= 0)                   $errors[] = 'La RPM mínima debe ser > 0.';
    if (!$rpmm || $rpmm <= 0)                   $errors[] = 'La RPM máxima debe ser > 0.';
    if ($rpmn && $rpmm && $rpmn >= $rpmm)       $errors[] = 'La RPM mínima debe ser menor que la máxima.';
    if (!$feed || $feed <= 0)                   $errors[] = 'El avance máximo debe ser > 0.';
    if (!$hp   || $hp   <= 0)                   $errors[] = 'La potencia debe ser > 0.';

    /* 7-5) Persistencia OK */
    if (!$errors) {
        $_SESSION += [
            'transmission_id' => $id,
            'rpm_min'         => $rpmn,
            'rpm_max'         => $rpmm,
            'feed_max'        => $feed,
            'hp'              => $hp,
            'wizard_progress' => 5,
        ];
        session_write_close();
        dbg('✅ Parámetros guardados → Step 6');
        header('Location: step6.php');
        exit;
    }
}

/*───────────────────────────────────────────────────────────────────────────────
 | 8)   VALORES PREVIOS para repoblar el form
 ───────────────────────────────────────────────────────────────────────────────*/
$prev = [
    'transmission_id' => $_SESSION['transmission_id'] ?? '',
    'rpm_min'         => $_SESSION['rpm_min']        ?? '',
    'rpm_max'         => $_SESSION['rpm_max']        ?? '',
    'feed_max'        => $_SESSION['feed_max']       ?? '',
    'hp'              => $_SESSION['hp']             ?? '',
];
$hasPrev = (int)$prev['transmission_id'] > 0;

/*───────────────────────────────────────────────────────────────────────────────
 | 9)   ASSETS locales + verificación
 ───────────────────────────────────────────────────────────────────────────────*/
$root = dirname(__DIR__, 2) . '/';   // /wizard-stepper_git/
$cssBootstrapRel = asset('assets/css/generic/bootstrap.min.css');
$bootstrapJsRel  = asset('assets/js/bootstrap.bundle.min.js');

$assetErrors = [];
if (!is_readable($root.'assets/css/generic/bootstrap.min.css'))
    $assetErrors[] = 'Bootstrap CSS no encontrado localmente.';
if (!is_readable($root.'assets/js/bootstrap.bundle.min.js'))
    $assetErrors[] = 'Bootstrap JS no encontrado localmente.';

/*───────────────────────────────────────────────────────────────────────────────
 | 10)  SALIDA HTML
 ───────────────────────────────────────────────────────────────────────────────*/
?>
<?php if (!$embedded): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Paso 5 – Configurá tu router</title>
  <?php
    $styles = [
      $cssBootstrapRel,
      'assets/css/settings/settings.css',
      'assets/css/objects/step-common.css',
      'assets/css/objects/step5.css',
    ];
    include __DIR__ . '/../partials/styles.php';
  ?>
  <script>
    window.BASE_URL  = <?= json_encode(BASE_URL) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
  </script>
</head>
<body>
<?php endif; ?>

<?php if ($assetErrors): ?>
  <div class="alert alert-warning text-dark m-3">
    <strong>⚠️ Archivos faltantes (se usarán CDNs):</strong>
    <ul class="mb-0">
      <?php foreach ($assetErrors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="step5">
  <div class="container py-4">
    <h2 class="step-title"><i data-feather="settings"></i> Configurá tu router</h2>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" class="row g-3 needs-validation" novalidate>
      <!-- Campos ocultos -->
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <input type="hidden" name="step"       value="5">

      <!-- Transmisión -->
      <div class="col-12">
        <label for="transmission" class="form-label">Transmisión</label>
        <select id="transmission" name="transmission_id" class="form-select" required>
          <option value="">Elegí…</option>
          <?php foreach ($txList as $tx): ?>
            <option value="<?= $tx['id'] ?>"
              <?= $hasPrev && $prev['transmission_id'] == $tx['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($tx['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Seleccioná una transmisión válida.</div>
      </div>

      <!-- RPM mínima -->
      <div class="col-md-6">
        <label for="rpmMin" class="form-label">RPM mínima</label>
        <input type="number" id="rpmMin" name="rpm_min" class="form-control"
               min="1" step="1" required
               value="<?= htmlspecialchars((string)$prev['rpm_min']) ?>">
        <div class="invalid-feedback">Debe ser > 0.</div>
      </div>

      <!-- RPM máxima -->
      <div class="col-md-6">
        <label for="rpmMax" class="form-label">RPM máxima</label>
        <input type="number" id="rpmMax" name="rpm_max" class="form-control"
               min="1" step="1" required
               value="<?= htmlspecialchars((string)$prev['rpm_max']) ?>">
        <div class="invalid-feedback">Debe ser > 0 y mayor que la mínima.</div>
      </div>

      <!-- Feedrate máximo -->
      <div class="col-md-6">
        <label for="feedMax" class="form-label">Feedrate máximo (mm/min)</label>
        <input type="number" id="feedMax" name="feed_max" class="form-control"
               min="1" step="1" required
               value="<?= htmlspecialchars((string)$prev['feed_max']) ?>">
        <div class="invalid-feedback">Debe ser > 0.</div>
      </div>

      <!-- Potencia -->
      <div class="col-md-6">
        <label for="hp" class="form-label">Potencia disponible (HP)</label>
        <input type="number" id="hp" name="hp" class="form-control"
               min="0.1" step="0.1" required
               value="<?= htmlspecialchars((string)$prev['hp']) ?>">
        <div class="invalid-feedback">Debe ser > 0.</div>
      </div>

      <!-- Botón siguiente -->
      <div class="col-12 text-end">
        <button class="btn btn-primary" type="submit">
          Siguiente&nbsp;<i data-feather="arrow-right"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<?php if (!$embedded): ?>
<script src="<?= $bootstrapJsRel ?>" defer></script>
<script src="<?= asset('node_modules/feather-icons/dist/feather.min.js') ?>" defer></script>
<script>
  // Bootstrap validation + feather icons
  requestAnimationFrame(() => feather.replace());

  (() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
      form.addEventListener('submit', ev => {
        if (!form.checkValidity()) {
          ev.preventDefault();
          ev.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();
</script>
</body>
</html>
<?php endif;
