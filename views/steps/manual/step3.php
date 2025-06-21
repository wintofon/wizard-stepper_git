<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/Utils/Session.php';
/**
 * File: step3.php
 * ------------------------------------------------------------------
 * Paso 3 (Auto) – Elegí el tipo de mecanizado y la estrategia
 * • Protegido contra CSRF, headers seguros y rate-limit básico  
 * • Requiere wizard_progress ≥ 2 (ya se eligió herramienta y estrategia)  
 * • Muestra solo las combinaciones válidas de la fresa seleccionada  
 * • Guarda {machining_type_id, strategy_id} y avanza a step4_select_material.php
 * ------------------------------------------------------------------
 */

/* ──────────────────────────────────────────────────────
 * [A]  Cabeceras de seguridad & anti-cache
 * ──────────────────────────────────────────────────── */
sendSecurityHeaders('text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");

/* ──────────────────────────────────────────────────────
 * [B]  Errores & debug
 * ──────────────────────────────────────────────────── */
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
    dbg('🔧 step3.php iniciado');
}

/* ──────────────────────────────────────────────────────
 * [C]  Sesión segura
 * ──────────────────────────────────────────────────── */
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

/* ──────────────────────────────────────────────────────
 * [D]  Flujo de wizard – debe haberse completado paso 2
 * ──────────────────────────────────────────────────── */
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 2) {
    dbg('❌ wizard_progress<2 – redirigiendo a step1.php');
    header('Location: step1.php');
    exit;
}
$_SESSION['wizard_state'] = 'wizard';

/* ──────────────────────────────────────────────────────
 * [E]  CSRF-token
 * ──────────────────────────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* ──────────────────────────────────────────────────────
 * [F]  Rate-limit 10 POST / 5 min por IP
 * ──────────────────────────────────────────────────── */
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unk';
$_SESSION['rate_limit'] ??= [];
$_SESSION['rate_limit'][$ip] = array_filter(
    $_SESSION['rate_limit'][$ip] ?? [],
    fn($ts) => $ts + 300 > time()
);
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    count($_SESSION['rate_limit'][$ip]) >= 10) {
    http_response_code(429);
    exit('<h1>Demasiados intentos. Probá más tarde.</h1>');
}

/* ──────────────────────────────────────────────────────
 * [G]  Conexión BD & helpers
 * ──────────────────────────────────────────────────── */
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/debug.php';

/* ──────────────────────────────────────────────────────
 * [H]  Validar que existe herramienta seleccionada
 * ──────────────────────────────────────────────────── */
if (empty($_SESSION['tool_id']) || empty($_SESSION['tool_table'])) {
    header('Location: step1.php'); /* flujo roto */
    exit;
}
$toolId    = (int)$_SESSION['tool_id'];
$toolTable = preg_replace('/[^a-z0-9_]/i', '', $_SESSION['tool_table']);

/* ──────────────────────────────────────────────────────
 * [I]  Cargar estrategias disponibles para esta fresa
 * ──────────────────────────────────────────────────── */
$q = "
  SELECT s.strategy_id,
         s.name,
         s.machining_type_id,
         mt.name AS type_name
    FROM toolstrategy ts
    JOIN strategies      s  ON s.strategy_id      = ts.strategy_id
    JOIN machining_types mt ON mt.machining_type_id = s.machining_type_id
   WHERE ts.tool_id    = :tid
     AND ts.tool_table = :tbl
   ORDER BY mt.name, s.name
";
$st = $pdo->prepare($q);
$st->execute([':tid' => $toolId, ':tbl' => $toolTable]);
$strats = $st->fetchAll(PDO::FETCH_ASSOC);

/* Agrupar */
$grouped = [];
foreach ($strats as $row) {
    $mt = (int)$row['machining_type_id'];
    $grouped[$mt]['name']           = $row['type_name'];
    $grouped[$mt]['estrategias'][]  = ['id' => (int)$row['strategy_id'],
                                       'name' => $row['name']];
}
dbg('Grouped', $grouped);

/* ──────────────────────────────────────────────────────
 * [J]  Procesar POST (guardar elección)
 * ──────────────────────────────────────────────────── */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* J-1  CSRF */
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido.';
    }

    /* J-2  Limitar */
    $_SESSION['rate_limit'][$ip][] = time();

    /* J-3  Sanitizar & validar */
    $mtId = filter_input(INPUT_POST, 'machining_type_id', FILTER_VALIDATE_INT);
    $stId = filter_input(INPUT_POST, 'strategy_id',       FILTER_VALIDATE_INT);
    if (!$mtId || !isset($grouped[$mtId])) {
        $errors[] = 'Tipo de mecanizado inválido.';
    }
    if (!$stId) {
        $errors[] = 'Estrategia inválida.';
    }
    if (!$errors) {
        $valid = false;
        foreach ($grouped[$mtId]['estrategias'] as $e) {
            if ($e['id'] === $stId) { $valid = true; break; }
        }
        if (!$valid) $errors[] = 'La estrategia no corresponde al tipo elegido.';
    }

    /* J-4  OK */
    if (!$errors) {
        $_SESSION['machining_type_id'] = $mtId;
        $_SESSION['strategy_id']       = $stId;
        $_SESSION['wizard_progress']   = 3;
        header('Location: step4_select_material.php');
        exit;
    }
}

/* ──────────────────────────────────────────────────────
 * [K]  Salida HTML
 * ──────────────────────────────────────────────────── */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 3 – Tipo de mecanizado & estrategia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/main.css') ?>">
  <!-- Estilos compartidos -->
  <link rel="stylesheet" href="<?= asset('assets/css/step-common.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/strategy.css') ?>">
  <script>const BASE_URL = "<?= BASE_URL ?>"; window.BASE_URL = BASE_URL;</script>
</head>
<body>
<main class="container py-4">

  <h2 class="step-title"><i data-feather="settings"></i> Mecanizado y estrategia</h2>
  <p class="step-desc">Definí el tipo de mecanizado y la estrategia a usar.</p>

  <?php if ($errors): ?>
    <div class="alert-custom">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (empty($grouped)): ?>
    <div class="alert-custom">No hay estrategias disponibles para esta herramienta.</div>
  <?php else: ?>

  <form id="strategyForm" method="post" novalidate>
    <input type="hidden" name="step"           value="3">
    <input type="hidden" name="csrf_token"     value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
    <input type="hidden" name="machining_type_id" id="machining_type_id">
    <input type="hidden" name="strategy_id"       id="strategy_id">

    <!-- Tipo de mecanizado -->
    <h5>Tipo de mecanizado</h5>
    <div id="machiningRow" class="d-flex flex-wrap mb-3">
      <?php foreach ($grouped as $mid => $g): ?>
        <button type="button"
                class="btn btn-outline-primary btn-machining me-2 mb-2"
                data-id="<?= $mid ?>">
          <?= htmlspecialchars($g['name'], ENT_QUOTES) ?>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- Estrategias -->
    <div id="strategyBox" style="display:none">
      <h5>Estrategia</h5>
      <div id="strategyButtons"></div>
    </div>

    <!-- Siguiente -->
    <div id="nextContainer" class="text-end mt-4" style="display:none">
      <button type="submit" class="btn btn-primary btn-lg">
        Siguiente <i data-feather="arrow-right" class="ms-1"></i>
      </button>
    </div>
  </form>
  <?php endif; ?>

  <pre id="debug" class="debug-box"></pre>
</main>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* PHP → JS */
const grouped = <?= json_encode($grouped, JSON_UNESCAPED_UNICODE) ?>;

const machRow  = document.getElementById('machiningRow');
const stratBox = document.getElementById('strategyBox');
const stratBtns= document.getElementById('strategyButtons');
const inputMt  = document.getElementById('machining_type_id');
const inputSt  = document.getElementById('strategy_id');
const nextBox  = document.getElementById('nextContainer');

/* Helpers debug */
window.dbg = (...m)=>{ console.log('[DBG]',...m);
  const d=document.getElementById('debug'); if(d) d.textContent+=m.join(' ')+'\n';};

/* 1) Click en tipo */
machRow.querySelectorAll('.btn-machining').forEach(b=>{
  b.addEventListener('click',()=>{
    machRow.querySelectorAll('.btn-machining').forEach(x=>x.classList.remove('active'));
    b.classList.add('active');
    const id=b.dataset.id; inputMt.value=id; inputSt.value='';
    nextBox.style.display='none';
    stratBtns.innerHTML='';
    (grouped[id]?.estrategias||[]).forEach(e=>{
      const sb=document.createElement('button');
      sb.type='button'; sb.className='btn btn-outline-secondary btn-strategy me-2 mb-2';
      sb.dataset.id=e.id; sb.textContent=e.name;
      sb.onclick=()=>{stratBtns.querySelectorAll('.btn-strategy').forEach(x=>x.classList.remove('active'));
                      sb.classList.add('active'); inputSt.value=e.id; nextBox.style.display='block';};
      stratBtns.appendChild(sb);
    });
    stratBox.style.display='block';
  });
});

/* 2) Submit simple -> val JS extra */
document.getElementById('strategyForm').addEventListener('submit',e=>{
  if(!inputMt.value||!inputSt.value){
    e.preventDefault(); alert('Elegí un tipo de mecanizado y una estrategia.');
  }
});
</script>
</body>
</html>

