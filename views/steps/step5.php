<?php
/**
 * File: step5.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
/**
 * Paso 5 (Auto) – Configurar router
 * Protegido con CSRF, controla flujo y valida:
 *   – rpm_min > 0
 *   – rpm_max > 0
 *   – rpm_min < rpm_max
 *   – feed_max > 0
 *   – hp       > 0
 * Después guarda en sesión y avanza a step6.php
 */
declare(strict_types=1);

/* 1) Sesión segura y flujo */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 4) {
    header('Location: step1.php');
    exit;
}

/* 2) Dependencias */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/debug.php';

/* 3) CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* 4) Transmisiones desde BD */
$txList = $pdo->query("
    SELECT id, name, rpm_min, rpm_max, feed_max, hp_default
      FROM transmissions
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$validTx = [];
foreach ($txList as $t) {
    $validTx[(int)$t['id']] = [
        'rpm_min'  => (int)$t['rpm_min'],
        'rpm_max'  => (int)$t['rpm_max'],
        'feed_max' => (float)$t['feed_max'],
        'hp_def'   => (float)$t['hp_default'],
    ];
}

/* 5) Procesar POST */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
    }
    if ((int)($_POST['step'] ?? 0) !== 5) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
    }

    $id   = filter_input(INPUT_POST, 'transmission_id', FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min',         FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max',         FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max',        FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp',              FILTER_VALIDATE_FLOAT);

    if (!isset($validTx[$id]))             $errors[] = 'Elegí una transmisión válida.';
    if (!$rpmn || $rpmn <= 0)              $errors[] = 'La RPM mínima debe ser > 0.';
    if (!$rpmm || $rpmm <= 0)              $errors[] = 'La RPM máxima debe ser > 0.';
    if ($rpmn && $rpmm && $rpmn >= $rpmm)  $errors[] = 'La RPM mínima debe ser menor que la máxima.';
    if (!$feed || $feed <= 0)              $errors[] = 'El avance máximo debe ser > 0.';
    if (!$hp   || $hp   <= 0)              $errors[] = 'La potencia debe ser > 0.';

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
        header('Location: step6.php');
        exit;
    }
}

/* 6) Valores previos */
$prev = [
    'transmission_id' => $_SESSION['transmission_id'] ?? '',
    'rpm_min'         => $_SESSION['rpm_min']        ?? '',
    'rpm_max'         => $_SESSION['rpm_max']        ?? '',
    'feed_max'        => $_SESSION['feed_max']       ?? '',
    'hp'              => $_SESSION['hp']             ?? '',
];
$hasPrev = (int)$prev['transmission_id'] > 0;
?>
<?php
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
  <!-- Título principal -->
  <h2 class="step-title"><i data-feather="cpu"></i> Configurá tu router CNC</h2>
  <p class="step-desc">Ingresá los datos de tu máquina para calcular parámetros.</p>

  <!-- Errores de validación -->
  <?php if ($errors): ?>
    <div class="alert alert-danger mb-4">
      <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err, ENT_QUOTES) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form id="routerForm" method="post" novalidate>
    <input type="hidden" name="step" value="5">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

    <!-- Bloque Transmisiones como botones estilo categoría -->
    <div class="mb-4">
      <h5 class="step-subtitle">Seleccione la Transmisión</h5>
      <div id="txRow" class="d-flex flex-wrap">
        <?php foreach ($txList as $tx):
          $tid = (int)$tx['id'];
          $active = $tid === $prev['transmission_id'];
        ?>
        <button
          type="button"
          class="btn-cat<?= $active ? ' active' : '' ?> me-2 mb-2"
          data-id="<?= $tid ?>"
        >
          <?= htmlspecialchars($tx['name'], ENT_QUOTES) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Parámetros del router (oculto hasta selección) -->
    <div id="paramSection" style="display:none;">
      <h5 class="step-subtitle">Seleccione los parámetros</h5>
      <div class="row g-3">
        <?php
          $fields = [
            ['rpm_min',   'RPM mínima',         1,   'rpm'],
            ['rpm_max',   'RPM máxima',         1,   'rpm'],
            ['feed_max',  'Avance máximo',      0.1, 'mm/min'],
            ['hp',        'Potencia (HP)',      0.1, 'HP'],
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
              value="<?= htmlspecialchars($prev[$key]) ?>"
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

    <!-- Botón Siguiente -->
    <div id="nextWrap" class="text-start mt-4" style="display:none;">
      <button type="submit" class="btn btn-primary btn-lg">
        Siguiente <i data-feather="arrow-right" class="ms-1"></i>
      </button>
    </div>
  </form>
</main>

<script>
(function() {
  // Pasar PHP txList a JS
  const txList = <?= json_encode($txList, JSON_UNESCAPED_UNICODE) ?>;
  const txRow = document.getElementById('txRow');
  const paramSection = document.getElementById('paramSection');
  const nextWrap = document.getElementById('nextWrap');
  const inputs = {
    rpm_min:  document.getElementById('rpm_min'),
    rpm_max:  document.getElementById('rpm_max'),
    feed_max: document.getElementById('feed_max'),
    hp:       document.getElementById('hp'),
  };

  // Scroll suave
  const smoothTo = el => el?.scrollIntoView({ behavior: 'smooth', block: 'start' });

  // Inicializar: ocultar sección y deshabilitar inputs
  disableAllInputs();
  function disableAllInputs() {
    Object.values(inputs).forEach(i => i.disabled = true);
  }

  // Manejar clic en cada botón de transmisión
  txRow.querySelectorAll('.btn-cat').forEach(btn => {
    btn.addEventListener('click', () => {
      // Marcar activo
      txRow.querySelectorAll('.btn-cat').forEach(x => x.classList.remove('active'));
      btn.classList.add('active');

      // Recuperar datos y poblar inputs
      const tx = txList.find(t => t.id == btn.dataset.id);
      inputs.rpm_min.value  = tx.rpm_min;
      inputs.rpm_max.value  = tx.rpm_max;
      inputs.feed_max.value = tx.feed_max;
      inputs.hp.value       = tx.hp_default;

      // Mostrar sección y habilitar
      disableAllInputs();
      Object.values(inputs).forEach(i => i.disabled = false);
      paramSection.style.display = 'block';
      nextWrap.style.display = 'block';
      smoothTo(paramSection);
    });
  });
})();
</script>
</body>
</html>
