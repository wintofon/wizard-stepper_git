<?php
/**
 * File: step5.php
 *
 * Main responsibility: Presenta el paso “Configurar router” en el asistente CNC.
 * Se encarga de:
 *   - Cargar listado de transmisiones ordenadas por coeficiente de seguridad.
 *   - Mostrar transmisiones como botones estilo categoría.
 *   - Pre-cargar valores al seleccionar una transmisión.
 *   - Validar campos de RPM, avance y potencia.
 *   - Mantener flujo seguro con sesión y CSRF.
 */
declare(strict_types=1);

// --------------------------------------------------
// 1) Sesión segura y control de flujo
// --------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
if (empty($_SESSION['wizard_progress']) || $_SESSION['wizard_progress'] < 4) {
    header('Location: step1.php');
    exit;
}

// --------------------------------------------------
// 2) Dependencias
// --------------------------------------------------
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/debug.php';

// --------------------------------------------------
// 3) Token CSRF
// --------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// --------------------------------------------------
// 4) Carga de transmisiones
//    Orden: coef_security DESC, id ASC
// --------------------------------------------------
$txList = $pdo->query(
    "SELECT id, name, rpm_min, rpm_max, feed_max, hp_default
     FROM transmissions
     ORDER BY coef_security DESC, id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Construir mapa válido para validaciones
$validTx = [];
foreach ($txList as $tx) {
    $validTx[(int)$tx['id']] = [
        'rpm_min'  => (int)$tx['rpm_min'],
        'rpm_max'  => (int)$tx['rpm_max'],
        'feed_max' => (float)$tx['feed_max'],
        'hp'       => (float)$tx['hp_default'],
    ];
}

// --------------------------------------------------
// 5) Procesar envío de formulario
// --------------------------------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido.';
    }
    // Paso esperado
    if (($_POST['step'] ?? '') !== '5') {
        $errors[] = 'Paso inválido.';
    }
    // Leer valores
    $id   = filter_input(INPUT_POST, 'transmission_id', FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min', FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max', FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max', FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp', FILTER_VALIDATE_FLOAT);

    // Validar
    if (!isset($validTx[$id]))             $errors[] = 'Selección de transmisión inválida.';
    if (!$rpmn || $rpmn <= 0)              $errors[] = 'RPM mínima debe ser > 0.';
    if (!$rpmm || $rpmm <= 0)              $errors[] = 'RPM máxima debe ser > 0.';
    if ($rpmn >= $rpmm)                    $errors[] = 'RPM mínima debe ser menor que la máxima.';
    if (!$feed || $feed <= 0)              $errors[] = 'Avance máximo debe ser > 0.';
    if (!$hp   || $hp   <= 0)              $errors[] = 'Potencia debe ser > 0.';

    // Guardar y avanzar si OK
    if (empty($errors)) {
        $_SESSION['transmission_id'] = $id;
        $_SESSION['rpm_min']         = $rpmn;
        $_SESSION['rpm_max']         = $rpmm;
        $_SESSION['feed_max']        = $feed;
        $_SESSION['hp']              = $hp;
        $_SESSION['wizard_progress'] = 5;
        session_write_close();
        header('Location: step6.php');
        exit;
    }
}

// Valores previos para repoblar formulario
$prev = [
    'id'       => $_SESSION['transmission_id'] ?? null,
    'rpm_min'  => $_SESSION['rpm_min'] ?? '',
    'rpm_max'  => $_SESSION['rpm_max'] ?? '',
    'feed_max' => $_SESSION['feed_max'] ?? '',
    'hp'       => $_SESSION['hp'] ?? '',
];
$resume = (bool)$prev['id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Paso 5 – Configurá tu router CNC</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
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
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">

    <!-- Bloque Transmisiones como botones estilo categoría -->
    <div class="mb-4">
      <h5 class="step-subtitle">Seleccione la Transmisión</h5>
      <div id="txRow" class="d-flex flex-wrap">
        <?php foreach ($txList as $tx):
          $tid = (int)$tx['id'];
          $active = $tid === $prev['id'];
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
