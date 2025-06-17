<?php
/**
 * Paso 5 (Auto) – Configurar router
 * ---------------------------------------------------------------
 * • Lee transmisiones disponibles, con valores por defecto.
 * • Protegido contra CSRF y validación de flujo (wizard_progress ≥ 4).
 * • Valida que transmission_id provenga de la lista disponible.
 * • Valida rangos numéricos y que RPM mínima < RPM máxima.
 * • Guarda transmisión y parámetros en sesión y avanza al Paso 6.
 */
declare(strict_types=1);

// 1) Sesión segura y control de flujo
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

// 2) Incluimos dependencias
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/debug.php';

// 3) Generar/verificar CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// 4) Leer transmisiones desde la BD
$stmt = $pdo->query("
    SELECT id, name, rpm_min, rpm_max, feed_max, hp_default
      FROM transmissions
    ORDER BY name
");
$txList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construir array plano para validación en POST
$validTransmissions = [];
foreach ($txList as $t) {
    $validTransmissions[(int)$t['id']] = [
        'rpm_min'  => (int)$t['rpm_min'],
        'rpm_max'  => (int)$t['rpm_max'],
        'feed_max' => (float)$t['feed_max'],
        'hp_def'   => (float)$t['hp_default'],
    ];
}

// 5) Procesar POST (validación CSRF + campos)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 5.1) Verificar CSRF
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, (string)$postedToken)) {
        $errors[] = "Token de seguridad inválido. Recargá la página e intentá de nuevo.";
    }

    // 5.2) Verificar “step”
    $postedStep = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    if ($postedStep !== 5) {
        $errors[] = "Paso inválido. Reiniciá el wizard.";
    }

    // 5.3) Filtrar inputs
    $id   = filter_input(INPUT_POST, 'transmission_id', FILTER_VALIDATE_INT);
    $rpmn = filter_input(INPUT_POST, 'rpm_min',         FILTER_VALIDATE_INT);
    $rpmm = filter_input(INPUT_POST, 'rpm_max',         FILTER_VALIDATE_INT);
    $feed = filter_input(INPUT_POST, 'feed_max',        FILTER_VALIDATE_FLOAT);
    $hp   = filter_input(INPUT_POST, 'hp',              FILTER_VALIDATE_FLOAT);

    // 5.4) Validaciones básicas
    if ($id === false || $id === null || !array_key_exists($id, $validTransmissions)) {
        $errors[] = "Elegí una transmisión válida.";
    }
    if ($rpmn === false || $rpmn === null) {
        $errors[] = "Completá RPM mínima.";
    }
    if ($rpmm === false || $rpmm === null) {
        $errors[] = "Completá RPM máxima.";
    }
    if ($rpmn !== false && $rpmm !== false && $rpmn >= $rpmm) {
        $errors[] = "La RPM mínima debe ser menor que la RPM máxima.";
    }
    if ($feed === false || $feed === null) {
        $errors[] = "Completá avance máximo.";
    }
    if ($hp === false || $hp === null) {
        $errors[] = "Completá potencia.";
    }

    // 5.5) Solo si no hay errores previos, guardamos en sesión y avanzamos
    if (empty($errors)) {
        $_SESSION['transmission_id'] = $id;
        $_SESSION['rpm_min']         = $rpmn;
        $_SESSION['rpm_max']         = $rpmm;
        $_SESSION['feed_max']        = $feed;
        $_SESSION['hp']              = $hp;
        $_SESSION['wizard_progress']= 5;
        session_write_close();
        header('Location: step6.php');
        exit;
    }
}

// 6) Cargar valores previos de sesión (si los hay)
$prev = [
    'transmission_id' => $_SESSION['transmission_id'] ?? '',
    'rpm_min'         => $_SESSION['rpm_min']        ?? '',
    'rpm_max'         => $_SESSION['rpm_max']        ?? '',
    'feed_max'        => $_SESSION['feed_max']       ?? '',
    'hp'              => $_SESSION['hp']             ?? '',
];
$hasPrev = is_int($prev['transmission_id']) && $prev['transmission_id'] > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 5 – Configurá tu router</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <style>
    body {
      --bs-body-bg: #0d1117;
      --bs-body-color: #e0e0e0;
      background-color: var(--bs-body-bg);
      color: var(--bs-body-color);
      font-family: 'Segoe UI', Roboto, sans-serif;
    }
    h2 {
      margin-bottom: 1.5rem;
      color: #cbd5e0;
    }
    .form-label {
      font-weight: 600;
      color: #a7b1bb;
    }
    .btn-outline-primary {
      margin: .25rem;
      border-color: #4fc3f7;
      color: #4fc3f7;
    }
    .btn-outline-primary:hover {
      background-color: #4fc3f7;
      color: #0d1117;
    }
    .btn-check:checked + label.btn-outline-primary {
      background-color: #0d6efd !important;
      color: #fff !important;
      border-color: #0d6efd !important;
    }
    .form-control {
      background-color: #1e293b;
      color: #e0e0e0;
      border-color: #334156;
    }
    .form-control:disabled {
      background-color: #334156;
      color: #a7b1bb;
    }
    .alert-danger {
      background-color: #4c1d1d;
      color: #f8d7da;
      border-color: #f5c2c7;
    }
    #machineForm {
      max-width: 800px;
      margin: 0 auto;
      background: #132330;
      padding: 2rem;
      border-radius: 0.75rem;
      box-shadow: 0 0 24px rgba(0, 0, 0, 0.5);
      border: 1px solid #264b63;
    }
    #paramSection {
      display: <?= $hasPrev ? 'block' : 'none' ?>;
      margin-bottom: 1.5rem;
    }
    .row.g-3 .col-md-3 {
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <h2>Paso 5 – Configurá tu router</h2>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" id="machineForm" novalidate>
      <!-- Campos ocultos: step y CSRF -->
      <input type="hidden" name="step"       value="5">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

      <!-- 1) Selección de transmisión -->
      <div class="mb-4">
        <label class="form-label d-block">Transmisión</label>
        <div class="btn-group" role="group">
          <?php foreach ($txList as $t):
              $tid     = (int)$t['id'];
              $checked = ($tid === $prev['transmission_id']);
          ?>
            <input
              class="btn-check"
              type="radio"
              name="transmission_id"
              id="tx<?= $tid ?>"
              value="<?= $tid ?>"
              <?= $checked ? 'checked' : '' ?>
            >
            <label
              class="btn btn-outline-primary"
              for="tx<?= $tid ?>"
              data-rpmmin="<?= (int)$t['rpm_min'] ?>"
              data-rpmmax="<?= (int)$t['rpm_max'] ?>"
              data-feedmax="<?= (float)$t['feed_max'] ?>"
              data-hpdef="<?= (float)$t['hp_default'] ?>"
            >
              <?= htmlspecialchars($t['name'], ENT_QUOTES) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 2) Parámetros de la transmisión (ocultos hasta seleccionar) -->
      <div id="paramSection">
        <div class="row g-3">
          <?php
            $fields = [
              ['rpm_min',   'RPM mínima',         1],
              ['rpm_max',   'RPM máxima',         1],
              ['feed_max',  'Avance máx (mm/min)',0.1],
              ['hp',        'Potencia (HP)',      0.1],
            ];
            foreach ($fields as list($id, $label, $stepSize)):
              $value   = htmlspecialchars((string)$prev[$id], ENT_QUOTES);
          ?>
            <div class="col-md-3">
              <label for="<?= $id ?>" class="form-label"><?= $label ?></label>
              <input
                type="number"
                class="form-control"
                id="<?= $id ?>"
                name="<?= $id ?>"
                step="<?= $stepSize ?>"
                min="0"
                value="<?= $value ?>"
                required
              >
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 3) Botón “Siguiente” -->
      <div class="text-end mt-4">
        <button type="submit"
                class="btn btn-primary btn-lg"
                id="nextBtn"
                <?= $hasPrev ? '' : 'disabled' ?>>
          Siguiente →
        </button>
      </div>
    </form>
  </div>

  <script>
  (function() {
    const radios    = document.querySelectorAll('.btn-check');
    const paramSection = document.getElementById('paramSection');
    const nextBtn   = document.getElementById('nextBtn');
    const getById   = id => document.getElementById(id);
    const inputs    = {
      rpm_min:  getById('rpm_min'),
      rpm_max:  getById('rpm_max'),
      feed_max: getById('feed_max'),
      hp:       getById('hp')
    };

    // Ocultar inputs hasta que se elija una transmisión
    function hideParams() {
      paramSection.style.display = 'none';
      nextBtn.disabled = true;
      for (const key in inputs) {
        inputs[key].value = '';
        inputs[key].disabled = true;
      }
    }

    // Mostrar inputs y precargar datos al cambiar la transmisión
    radios.forEach(radio => {
      radio.addEventListener('change', () => {
        const label = document.querySelector(`label[for="${radio.id}"]`);
        const data  = label.dataset;

        // Mostrar sección de parámetros
        paramSection.style.display = 'block';

        // Rellenar campos según datos en el dataset del label
        inputs.rpm_min.value  = data.rpmmin;
        inputs.rpm_max.value  = data.rpmmax;
        inputs.feed_max.value = data.feedmax;
        // Solo sobre-escribimos HP si el usuario no lo tocó antes
        if (!inputs.hp.value) {
          inputs.hp.value = data.hpdef;
        }

        // Habilitar todos los inputs
        for (const key in inputs) {
          inputs[key].disabled = false;
        }
        nextBtn.disabled = false;
      });
    });

    // Si ya había una transmisión seleccionada, mostrar parámetros
    <?php if (! $hasPrev): ?>
      hideParams();
    <?php endif; ?>
  })();
  </script>
  </div>
</body>
</html>
