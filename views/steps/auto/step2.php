<?php
/**
 * File: step2.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../src/Utils/Session.php';
/**
 * File: step2.php
 * ---------------------------------------------------------------
 * Paso 2 (Auto) – Selección del tipo de mecanizado y la estrategia
 * • Protegido contra CSRF y validación de flujo
 * • Chequea que wizard_progress>=1 (sino redirige a step1.php)
 * • Carga dinámicamente estrategias según tipo
 * • Guarda {machining_type_id, strategy_id} en sesión y avanza a step3.php
 * ---------------------------------------------------------------
 */

// -------------------------------------------
// [A] Cabeceras de seguridad y no‐caching
// -------------------------------------------
sendSecurityHeaders('text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");

// -------------------------------------------
// [B] Errores y Debug
// -------------------------------------------
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
require_once __DIR__ . '/../../../includes/wizard_helpers.php';
if ($DEBUG && function_exists('dbg')) {
    dbg('🔧 step2.php iniciado');
}

// -------------------------------------------
// [C] Inicio de sesión seguro
// -------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL . '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
    dbg('🔒 Sesión iniciada');
}

// -------------------------------------------
// [D] Validar flujo: se debe haber completado Paso 1
// -------------------------------------------
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 1) {
    dbg('❌ wizard_progress<1, redirigiendo a step1.php');
    header('Location: step1.php');
    exit;
}
$_SESSION['wizard_state'] = 'wizard'; // Asegurar estado

// -------------------------------------------
// [E] Generar CSRF‐token
// -------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// -------------------------------------------
// [F] Incluir DB y Debug
// -------------------------------------------
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/debug.php';

// -------------------------------------------
// [G] Cargar todos los tipos y estrategias
// -------------------------------------------
$sql = "
    SELECT s.strategy_id, s.name         AS strat_name,
           mt.machining_type_id, mt.name AS type_name
      FROM strategies s
      JOIN machining_types mt 
        ON s.machining_type_id = mt.machining_type_id
  ORDER BY mt.name, s.name
";
$stmt = $pdo->query($sql);
$strategies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar para la UI y validación “flat”
$types  = [];   // machining_type_id => type_name
$lists  = [];   // machining_type_id => [ ['id'=>..., 'name'=>...], ... ]
foreach ($strategies as $row) {
    $tid = (int)$row['machining_type_id'];
    $types[$tid] = $row['type_name'];
    $lists[$tid][] = [
        'id'   => (int)$row['strategy_id'],
        'name' => $row['strat_name'],
    ];
}
dbg('Types', $types);
dbg('Lists', $lists);

// -------------------------------------------
// [H] Procesar POST
// -------------------------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [H.1] Validar CSRF
    $postedToken = filter_input(INPUT_POST, 'csrf_token', FILTER_UNSAFE_RAW) ?? '';
    if (!hash_equals((string)$csrfToken, $postedToken)) {
        $errors[] = "Token de seguridad inválido. Recargá la página.";
        dbg('❌ CSRF inválido');
    }

    // [H.2] Validar “step=2”
    $postedStep = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    if ($postedStep !== 2) {
        $errors[] = "Paso inválido. Reiniciá el wizard.";
        dbg("❌ step inválido: " . var_export($postedStep, true));
    }

    // [H.3] Filtrar machining_type_id y strategy_id
    $typeRaw  = filter_input(INPUT_POST, 'machining_type_id', FILTER_VALIDATE_INT);
    $stratRaw = filter_input(INPUT_POST, 'strategy_id',       FILTER_VALIDATE_INT);

    if ($typeRaw === false || $typeRaw === null || !array_key_exists($typeRaw, $types)) {
        $errors[] = "Seleccioná un tipo de mecanizado válido.";
        dbg("❌ machining_type_id inválido: " . var_export($typeRaw, true));
    }
    if ($stratRaw === false || $stratRaw === null) {
        $errors[] = "Seleccioná una estrategia.";
        dbg("❌ strategy_id inválido: " . var_export($stratRaw, true));
    }

    // [H.4] Verificar que strategy_id esté dentro de $lists[$typeRaw]
    if (empty($errors)) {
        $foundStrat = false;
        foreach ($lists[$typeRaw] as $entry) {
            if ($entry['id'] === $stratRaw) {
                $foundStrat = true;
                break;
            }
        }
        if (!$foundStrat) {
            $errors[] = "La estrategia seleccionada no coincide con el tipo elegido.";
            dbg("❌ Estrategia y tipo no coinciden: type={$typeRaw}, strat={$stratRaw}");
        }
    }

    // [H.5] Si no hay errores, guardar en sesión y avanzar
    if (empty($errors)) {
        session_regenerate_id(true);
        $_SESSION['machining_type_id'] = $typeRaw;
        $_SESSION['strategy_id']       = $stratRaw;
        $_SESSION['wizard_progress']   = 2;
        dbg("✅ Paso 2 completado: type={$typeRaw}, strat={$stratRaw}");
        session_write_close();
        header('Location: step3.php');
        exit;
    }
}

// -------------------------------------------
// [I] Cargar valores previos (si vuelven atrás)
// -------------------------------------------
$prevType  = $_SESSION['machining_type_id'] ?? '';
$prevStrat = $_SESSION['strategy_id']       ?? '';
$hasPrev   = is_int($prevType) && array_key_exists((int)$prevType, $types)
           && is_int($prevStrat) && in_array((int)$prevStrat, array_column($lists[(int)$prevType] ?? [], 'id'), true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 2 – Mecanizado & Estrategia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 + hojas globales del wizard -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/objects/step-common.css">
  <link rel="stylesheet" href="assets/css/components/strategy.css">
</head>

<body>
<main class="container py-4">

  <!-- Título y descripción (misma línea visual que los demás pasos) -->
  <h2 class="step-title">
    <i data-feather="settings"></i> Mecanizado y estrategia
  </h2>
  <p class="step-desc">
    Elegí el tipo de mecanizado y la estrategia recomendada.
  </p>

  <!-- Errores de validación -->
  <?php if (!empty($errors)): ?>
    <div class="alert-custom">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e, ENT_QUOTES); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- FORMULARIO -->
  <form id="strategyForm" method="post" novalidate>

    <!-- ocultos -->
    <input type="hidden" name="step"       value="2">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
    <input type="hidden" name="machining_type_id" id="machining_type_id"
           value="<?= $hasPrev ? (int)$prevType  : ''; ?>">
    <input type="hidden" name="strategy_id"       id="strategy_id"
           value="<?= $hasPrev ? (int)$prevStrat : ''; ?>">

    <!-- 1 · Tipo de mecanizado -->
    <h5>Tipo de mecanizado</h5>
    <div id="machiningRow" class="d-flex flex-wrap mb-3">
      <?php foreach ($types as $tid => $tname): ?>
        <button type="button"
                class="btn btn-outline-primary btn-machining me-2 mb-2
                       <?= ($hasPrev && (int)$prevType === $tid) ? 'active' : ''; ?>"
                data-id="<?= $tid; ?>">
          <?= htmlspecialchars($tname, ENT_QUOTES); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- 2 · Estrategia -->
    <div id="strategyBox" class="mb-3"
         style="<?= $hasPrev ? 'display:block' : 'display:none'; ?>">
      <h5>Estrategia</h5>
      <div id="strategyButtons">
        <?php if ($hasPrev):
          foreach ($lists[(int)$prevType] as $s): ?>
            <button type="button"
                    class="btn btn-outline-secondary btn-strategy me-2 mb-2
                           <?= ((int)$prevStrat === $s['id']) ? 'active' : ''; ?>"
                    data-id="<?= $s['id']; ?>">
              <?= htmlspecialchars($s['name'], ENT_QUOTES); ?>
            </button>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- 3 · Botón Siguiente -->
    <div id="nextContainer" class="text-start mt-4"
         style="display:<?= $hasPrev ? 'block' : 'none'; ?>;">
      <button type="submit" class="btn btn-primary btn-lg">
        Siguiente <i data-feather="arrow-right" class="ms-1"></i>
      </button>
    </div>
  </form>
</main>

<!-- Scripts: Bootstrap bundle + Feather Icons -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons@4/dist/feather.min.js"></script>

<script>
/* PHP → JS */
const types  = <?= json_encode($types, JSON_UNESCAPED_UNICODE); ?>;
const lists  = <?= json_encode($lists, JSON_UNESCAPED_UNICODE); ?>;

/* DOM refs */
const machRow   = document.getElementById('machiningRow');
const stratBox  = document.getElementById('strategyBox');
const stratBtns = document.getElementById('strategyButtons');
const inType    = document.getElementById('machining_type_id');
const inStrat   = document.getElementById('strategy_id');
const nextBox   = document.getElementById('nextContainer');

/* Reset estrategias */
const resetStrategies = () => {
  stratBtns.innerHTML = '';
  stratBox.style.display = 'none';
  inStrat.value = '';
  nextBox.style.display = 'none';
};

/* Render estrategias según tipo */
const renderStrategies = tid => {
  (lists[tid] || []).forEach(s => {
    const b = document.createElement('button');
    b.type        = 'button';
    b.className   = 'btn btn-outline-secondary btn-strategy me-2 mb-2';
    b.dataset.id  = s.id;
    b.textContent = s.name;
    b.onclick = () => {
      stratBtns.querySelectorAll('.btn-strategy')
               .forEach(x => x.classList.remove('active'));
      b.classList.add('active');
      inStrat.value = s.id;
      nextBox.style.display = 'block';
    };
    stratBtns.appendChild(b);
  });
  stratBox.style.display = 'block';
};

/* Click en tipo de mecanizado */
machRow.querySelectorAll('.btn-machining').forEach(btn => {
  btn.addEventListener('click', () => {
    machRow.querySelectorAll('.btn-machining')
           .forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const tid = parseInt(btn.dataset.id, 10);
    inType.value = tid;
    resetStrategies();
    renderStrategies(tid);
  });
});

/* Mantener estado si venía de atrás */
if (inType.value)  stratBox.style.display = 'block';
if (inStrat.value) nextBox.style.display  = 'block';

/* Feather Icons */
feather.replace({ class: 'feather' });
</script>
</body>
</html>







