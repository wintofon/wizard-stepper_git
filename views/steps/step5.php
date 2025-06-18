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
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/steps/step5.css">
</head>
<body>
  <main class="container py-4">
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

    <form method="post" action="" id="routerForm" class="needs-validation" novalidate>
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
              <div class="invalid-feedback">
                ⚠ Valor inválido.
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 3) Botón “Siguiente” -->
      <div id="next-button-container" class="text-end mt-4" style="display: <?= $hasPrev ? 'block' : 'none' ?>;">
        <button type="submit" id="btn-next" class="btn btn-primary btn-lg">
          Siguiente →
        </button>
      </div>
    </form>
  </div>

  <script>
  (function() {
    const radios    = document.querySelectorAll('.btn-check');
    const paramSection = document.getElementById('paramSection');
    const nextContainer = document.getElementById('next-button-container');
    const nextBtn   = document.getElementById('btn-next');
    const getById   = id => document.getElementById(id);
  const inputs    = {
      rpm_min:  getById('rpm_min'),
      rpm_max:  getById('rpm_max'),
      feed_max: getById('feed_max'),
      hp:       getById('hp')
    };

    function checkValidation() {
      let valid = true;
      const rpmMin  = parseFloat(inputs.rpm_min.value);
      const rpmMax  = parseFloat(inputs.rpm_max.value);
      const feedMax = parseFloat(inputs.feed_max.value);
      const hp      = parseFloat(inputs.hp.value);

      const setInvalid = (input, condition) => {
        if (condition) {
          input.classList.add('is-invalid');
          valid = false;
        } else {
          input.classList.remove('is-invalid');
        }
      };

      setInvalid(inputs.rpm_min, !(rpmMin > 0));
      setInvalid(inputs.rpm_max, !(rpmMax > 0));
      setInvalid(inputs.feed_max, !(feedMax > 0));
      setInvalid(inputs.hp, !(hp > 0));

      if (rpmMin > 0 && rpmMax > 0 && rpmMin >= rpmMax) {
        inputs.rpm_min.classList.add('is-invalid');
        inputs.rpm_max.classList.add('is-invalid');
        valid = false;
      }

      return valid;
    }

    Object.values(inputs).forEach(inp =>
      inp.addEventListener('input', checkValidation)
    );

    document.getElementById('routerForm').addEventListener('submit', e => {
      if (!checkValidation()) {
        e.preventDefault();
        e.stopPropagation();
      }
    });

    // Ocultar inputs hasta que se elija una transmisión
    function hideParams() {
      paramSection.style.display = 'none';
      nextContainer.style.display = 'none';
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
        nextContainer.style.display = 'block';
      });
    });

    // Si ya había una transmisión seleccionada, mostrar parámetros
    <?php if (! $hasPrev): ?>
      hideParams();
    <?php endif; ?>
  })();
  </script>
  </main>
</body>
</html>
