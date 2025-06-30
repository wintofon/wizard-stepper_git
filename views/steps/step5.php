<?php
/**
 * File: step5.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 *
 * Paso 5 (Auto) – Configurar router
 *   • Protección CSRF
 *   • Control de flujo (wizard_progress)
 *   • Validación de campos:
 *       – rpm_min > 0
 *       – rpm_max > 0
 *       – rpm_min < rpm_max
 *       – feed_max > 0
 *       – hp       > 0
 *   • Guarda en sesión bajo las claves:
 *       trans_id, rpm_min, rpm_max, feed_max, hp, wizard_progress
 *   • Redirige a step6.php
 */
declare(strict_types=1);

// --------------------------------------------------
// 1) Sesión segura y control de flujo
// --------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,     // Solo HTTPS
        'cookie_httponly' => true,     // No JS
        'cookie_samesite' => 'Strict', // Evita CSRF
    ]);
}
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 4) {
    // Si no ha llegado al paso 4, redirige al inicio
    header('Location: step1.php');
    exit;
}

// --------------------------------------------------
// 2) Dependencias externas
// --------------------------------------------------
require_once __DIR__ . '/../../includes/db.php';     // Proporciona $pdo
require_once __DIR__ . '/../../includes/debug.php';  // Funciones de debug

// --------------------------------------------------
// 3) Generar/recuperar CSRF token
// --------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    // Nuevo token si no existe
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// --------------------------------------------------
// 4) Obtención de transmisiones desde la BD
//    Ordenadas por coef_deguriti DESC, luego por id ASC
// --------------------------------------------------
$txList = $pdo->query("
    SELECT id,
           name,
           rpm_min,
           rpm_max,
           feed_max,
           hp_default,
           coef_deguriti
      FROM transmissions
    ORDER BY coef_deguriti DESC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Mapeo rápido para validaciones posteriores
$validTx = [];
foreach ($txList as $t) {
    $validTx[(int)$t['id']] = [
        'rpm_min'  => (int)$t['rpm_min'],
        'rpm_max'  => (int)$t['rpm_max'],
        'feed_max' => (float)$t['feed_max'],
        'hp_def'   => (float)$t['hp_default'],
    ];
}

// --------------------------------------------------
// 5) Procesamiento del formulario POST
// --------------------------------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 5.1) Verificar CSRF
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
    }
    // 5.2) Verificar que venga del paso correcto
    if ((int)($_POST['step'] ?? 0) !== 5) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
    }

    // 5.3) Recoger valores del POST
    $id   = filter_input(INPUT_POST, 'trans_id',    FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min',     FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max',     FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max',    FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp',          FILTER_VALIDATE_FLOAT);

    // 5.4) Validaciones de negocio
    if (!isset($validTx[$id]))            $errors[] = 'Elegí una transmisión válida.';
    if (!$rpmn || $rpmn <= 0)             $errors[] = 'La RPM mínima debe ser > 0.';
    if (!$rpmm || $rpmm <= 0)             $errors[] = 'La RPM máxima debe ser > 0.';
    if ($rpmn && $rpmm && $rpmn >= $rpmm) $errors[] = 'La RPM mínima debe ser menor que la máxima.';
    if (!$feed || $feed <= 0)             $errors[] = 'El avance máximo debe ser > 0.';
    if (!$hp   || $hp   <= 0)             $errors[] = 'La potencia debe ser > 0.';

    // 5.5) Si no hay errores, guardar en sesión y avanzar
    if (empty($errors)) {
        $_SESSION += [
            'trans_id'        => $id,
            'rpm_min'         => $rpmn,
            'rpm_max'         => $rpmm,
            'feed_max'        => $feed,
            'hp'              => $hp,
            'wizard_progress' => 5,
        ];
        session_write_close();
        header('Location: step6.php');
        exit;
    }
}

// --------------------------------------------------
// 6) Preparar valores previos para mostrar en el form
// --------------------------------------------------
$prev = [
    'trans_id'   => $_SESSION['trans_id']   ?? '',
    'rpm_min'    => $_SESSION['rpm_min']    ?? '',
    'rpm_max'    => $_SESSION['rpm_max']    ?? '',
    'feed_max'   => $_SESSION['feed_max']   ?? '',
    'hp'         => $_SESSION['hp']         ?? '',
];
$hasPrev = (int)$prev['trans_id'] > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 5 – Configurá tu router</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
<?php
  // --------------------------------------------------
  // 7) Incluir hojas de estilo
  // --------------------------------------------------
  $styles = [
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'assets/css/objects/step-common.css',
    'assets/css/components/_step5.css',
  ];
  include __DIR__ . '/../partials/styles.php';
?>
</head>
<body>
<main class="container py-4">
  <!-- 8) Título y descripción -->
  <h2 class="step-title"><i data-feather="cpu"></i> Configurá tu router CNC</h2>
  <p class="step-desc">Ingresá los datos de tu máquina para calcular parámetros.</p>

  <!-- 9) Mostrar errores de validación -->
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
      <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err, ENT_QUOTES) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- 10) Formulario principal -->
  <form id="routerForm" method="post" novalidate>
    <!-- Paso y CSRF -->
    <input type="hidden" name="step" value="5">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
    <!-- Hidden para trans_id -->
    <input type="hidden" name="trans_id" id="trans_id" value="<?= htmlspecialchars($prev['trans_id'], ENT_QUOTES) ?>">

    <!-- 11) Selección de transmisión -->
    <div class="mb-4">
      <h5 class="step-subtitle">Seleccione la Transmisión (ordenadas por coef_deguriti)</h5>
      <div id="txRow" class="d-flex flex-wrap">
        <?php foreach ($txList as $tx):
          $tid    = (int)$tx['id'];
          $active = $tid === (int)$prev['trans_id'];
        ?>
        <button
          type="button"
          class="btn-cat<?= $active ? ' active' : '' ?> me-2 mb-2"
          data-id="<?= $tid ?>"
          data-rpmmin="<?= $tx['rpm_min'] ?>"
          data-rpmmax="<?= $tx['rpm_max'] ?>"
          data-feedmax="<?= $tx['feed_max'] ?>"
          data-hpdef="<?= $tx['hp_default'] ?>"
        >
          <?= htmlspecialchars($tx['name'], ENT_QUOTES) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 12) Parámetros (oculto hasta selección) -->
    <div id="paramSection" style="display:none;">
      <h5 class="step-subtitle">Seleccione los parámetros</h5>
      <div class="row g-3">
        <?php
          $fields = [
            ['rpm_min',   'RPM mínima',    1,   'rpm'],
            ['rpm_max',   'RPM máxima',    1,   'rpm'],
            ['feed_max',  'Avance máximo', 0.1, 'mm/min'],
            ['hp',        'Potencia (HP)', 0.1, 'HP'],
          ];
          foreach ($fields as [$key, $label, $step, $unit]):
        ?>
        <div class="col-md-3">
          <label for="<?= $key ?>" class="form-label"><?= $label ?></label>
          <div class="input-group has-validation">
            <input
              type="number"
              id="<?= $key ?>"
              name="<?= $key ?>"
              class="form-control"
              step="<?= $step ?>"
              min="1"
              value="<?= htmlspecialchars($prev[$key] ?? '', ENT_QUOTES) ?>"
              disabled
              required
            >
            <span class="input-group-text"><?= $unit ?></span>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 13) Botón Siguiente (se muestra solo si hay selección válida) -->
    <div id="nextWrap" class="text-start mt-4" style="display:<?= $hasPrev ? 'block' : 'none' ?>">
      <button type="submit" class="btn btn-primary btn-lg">
        Siguiente <i data-feather="arrow-right" class="ms-1"></i>
      </button>
    </div>
  </form>
</main>

<script>
(function() {
  // Pasamos la lista de transmisiones a JS
  const txList       = <?= json_encode($txList, JSON_UNESCAPED_UNICODE) ?>;
  const txRow        = document.getElementById('txRow');
  const paramSection = document.getElementById('paramSection');
  const nextWrap     = document.getElementById('nextWrap');
  const inputs       = {
    rpm_min : document.getElementById('rpm_min'),
    rpm_max : document.getElementById('rpm_max'),
    feed_max: document.getElementById('feed_max'),
    hp      : document.getElementById('hp'),
  };
  const hiddenTrans  = document.getElementById('trans_id');

  // Función para scroll suave
  const smoothTo = el => el?.scrollIntoView({ behavior: 'smooth', block: 'start' });

  // Deshabilitamos inputs al cargar
  Object.values(inputs).forEach(i => i.disabled = true);

  // Cada botón de transmisión recibe su listener
  txRow.querySelectorAll('.btn-cat').forEach(btn => {
    btn.addEventListener('click', () => {
      // 1) Marcar activo el botón
      txRow.querySelectorAll('.btn-cat').forEach(x => x.classList.remove('active'));
      btn.classList.add('active');

      // 2) Extraer datos del data-attribute
      const { rpmmin, rpmmax, feedmax, hpdef, id } = btn.dataset;

      // 3) Poblar inputs
      inputs.rpm_min.value  = rpmmin;
      inputs.rpm_max.value  = rpmmax;
      inputs.feed_max.value = feedmax;
      inputs.hp.value       = hpdef;

      // 4) Guardar id en hidden
      hiddenTrans.value = btn.dataset.id;

      // 5) Mostrar sección y habilitar inputs
      Object.values(inputs).forEach(i => i.disabled = false);
      paramSection.style.display = 'block';
      nextWrap.style.display     = 'block';
      smoothTo(paramSection);
    });
  });
})();
</script>
</body>
</html>
