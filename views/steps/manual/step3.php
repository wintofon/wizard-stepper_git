<?php
/**
 * Paso 3 (Auto) – Elegí el tipo de mecanizado y la estrategia
 *
 * • Acepta POST (desde el Paso 2) para guardar “machining_type_id” y “strategy_id”.
 * • Incluye token CSRF, rate‐limit, headers de seguridad y debug opcional.
 * • Verifica sesión, herramienta seleccionada en Step 1/2 y actualiza wizard_progress.
 * • Consulta las estrategias disponibles para la herramienta y las agrupa por tipo.
 */

declare(strict_types=1);

// ────────────────────────────────────────────────────────────────────────────
// 1) Iniciar sesión con parámetros seguros y CSRF‐token
// ────────────────────────────────────────────────────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}
// Inicializar wizard_state/progress si no existen
if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state'] !== 'wizard') {
    $_SESSION['wizard_state']    = 'wizard';
    $_SESSION['wizard_progress'] = 1;
}
// Sólo permitimos acceder a Step 3 si progress ≥ 2 (Step 1 y Step 2 completados)
$currentProgress = (int)($_SESSION['wizard_progress'] ?? 1);
if ($currentProgress < 2) {
    header('Location: step1.php');
    exit;
}
// Generar CSRF token si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ────────────────────────────────────────────────────────────────────────────
// 2) Rate‐limit básico por IP (máx. 10 POST en 5 minutos)
// ────────────────────────────────────────────────────────────────────────────
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!isset($_SESSION['rate_limit'])) {
    $_SESSION['rate_limit'] = [];
}
// Limpiar timestamps mayores a 5 minutos
foreach ($_SESSION['rate_limit'] as $ip => $timestamps) {
    $_SESSION['rate_limit'][$ip] = array_filter(
        $timestamps,
        fn($ts) => ($ts + 300) > time()
    );
}
if (!isset($_SESSION['rate_limit'][$clientIp])) {
    $_SESSION['rate_limit'][$clientIp] = [];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && count($_SESSION['rate_limit'][$clientIp]) >= 10) {
    http_response_code(429);
    echo "<h1>Demasiados intentos. Esperá unos minutos antes de reintentar.</h1>";
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// 3) Headers de seguridad
// ────────────────────────────────────────────────────────────────────────────
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("X-Content-Type-Options: nosniff");

// ────────────────────────────────────────────────────────────────────────────
// 4) Conexión a BD y debug opcional
// ────────────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../../includes/db.php';
$DEBUG = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($DEBUG && is_readable(__DIR__ . '/../../../includes/debug.php')) {
    require_once __DIR__ . '/../../../includes/debug.php';
    dbg('Step 3 iniciado. Progreso actual: ' . $currentProgress);
} else {
    if (!function_exists('dbg')) {
        function dbg() { /* stub */ }
    }
}

// ────────────────────────────────────────────────────────────────────────────
// 5) Validar que la herramienta ya esté en sesión (Step 1/2)
// ────────────────────────────────────────────────────────────────────────────
if (empty($_SESSION['tool_id']) || empty($_SESSION['tool_table'])) {
    // Si no viene tool_id/tool_table en sesión, lo mandamos al Paso 1
    header('Location: step1.php');
    exit;
}
$toolId       = (int)$_SESSION['tool_id'];
$toolTableRaw = (string)$_SESSION['tool_table'];

// ────────────────────────────────────────────────────────────────────────────
// 6) Tablas válidas y helper para sanitizar $toolTable
// ────────────────────────────────────────────────────────────────────────────
$brandTables = [
    'tools_sgs',
    'tools_maykestag',
    'tools_schneider',
    'tools_generico',
];
// Sólo permitimos nombres de tablas exactos en $brandTables
function tblClean(string $raw, array $allowed): ?string {
    $clean = preg_replace('/[^a-z0-9_]/i', '', $raw);
    return in_array($clean, $allowed, true) ? $clean : null;
}
$toolTable = tblClean($toolTableRaw, $brandTables);
if (!$toolTable) {
    // Sesión corrupta: tabla inválida
    unset($_SESSION['tool_id'], $_SESSION['tool_table']);
    header('Location: step1.php');
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// 7) Procesar POST para guardar “machining_type_id” y “strategy_id”
// ────────────────────────────────────────────────────────────────────────────
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['machining_type_id'], $_POST['strategy_id'], $_POST['csrf_token'])) {

    dbg('POST recibido en Step 3', $_POST);

    // 7.1) Validar token CSRF
    if (!hash_equals($csrfToken, (string)$_POST['csrf_token'])) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
        dbg('Error CSRF en POST Step 3');
    }

    // 7.2) Rate‐limit: registrar timestamp
    if (!$errors) {
        $_SESSION['rate_limit'][$clientIp][] = time();
    }

    // 7.3) Sanitizar y validar IDs
    if (!$errors) {
        $mtIdRaw    = filter_var($_POST['machining_type_id'], FILTER_VALIDATE_INT);
        $stratIdRaw = filter_var($_POST['strategy_id'], FILTER_VALIDATE_INT);

        if (!$mtIdRaw || $mtIdRaw < 1) {
            $errors[] = 'Tipo de mecanizado inválido.';
            dbg('machining_type_id inválido:', $_POST['machining_type_id']);
        }
        if (!$stratIdRaw || $stratIdRaw < 1) {
            $errors[] = 'Estrategia inválida.';
            dbg('strategy_id inválido:', $_POST['strategy_id']);
        }
    }

    // 7.4) Verificar que la combinación herramienta‐estrategia‐tipo exista en BD
    if (!$errors) {
        $checkSql = "
          SELECT COUNT(*) AS cnt
            FROM toolstrategy ts
            JOIN strategies s ON ts.strategy_id = s.strategy_id
           WHERE ts.tool_id          = :tool_id
             AND ts.tool_table       = :tool_table
             AND ts.strategy_id      = :strategy_id
             AND s.machining_type_id = :mt_id
        ";
        $stCheck = $pdo->prepare($checkSql);
        $stCheck->execute([
            ':tool_id'     => $toolId,
            ':tool_table'  => $toolTable,
            ':strategy_id' => $stratIdRaw,
            ':mt_id'       => $mtIdRaw,
        ]);
        $row = $stCheck->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['cnt'] === 0) {
            $errors[] = 'La estrategia seleccionada no está disponible para esta herramienta.';
            dbg('Combinación inválida en BD:', $toolId, $toolTable, $stratIdRaw, $mtIdRaw);
        }
    }

    // 7.5) Si no hay errores, guardamos en sesión y avanzamos
    if (empty($errors)) {
        $_SESSION['machining_type_id'] = $mtIdRaw;
        $_SESSION['strategy_id']       = $stratIdRaw;
        $_SESSION['wizard_progress']   = 3; // ya completó Step 3
        dbg('Step 3 validado. machining_type_id=', $mtIdRaw, 'strategy_id=', $stratIdRaw);

        header('Location: step4_select_material.php');
        exit;
    }
}

// ────────────────────────────────────────────────────────────────────────────
// 8) Consultar estrategias disponibles para la herramienta actual
// ────────────────────────────────────────────────────────────────────────────
$query = "
  SELECT s.strategy_id,
         s.name,
         s.machining_type_id,
         mt.name AS type_name
    FROM toolstrategy ts
    JOIN strategies s ON ts.strategy_id = s.strategy_id
    JOIN machining_types mt ON s.machining_type_id = mt.machining_type_id
   WHERE ts.tool_id    = :tool_id
     AND ts.tool_table = :tool_table
   ORDER BY mt.name, s.name
";
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':tool_id'    => $toolId,
    ':tool_table' => $toolTable
]);
$strategies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por tipo de mecanizado
$grouped = [];
foreach ($strategies as $s) {
    $mtid = (int)$s['machining_type_id'];
    if (!isset($grouped[$mtid])) {
        $grouped[$mtid] = [
            'name'        => $s['type_name'],
            'estrategias' => []
        ];
    }
    $grouped[$mtid]['estrategias'][] = [
        'id'   => (int)$s['strategy_id'],
        'name' => $s['name']
    ];
}
dbg('Tipos de mecanizado disponibles:', $grouped);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paso 3 – Elegí tipo de mecanizado y estrategia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

  <!-- Estilos mínimos para botones de estrategia -->
  <style>
    .btn-strategy { margin: 5px; }
    .btn-strategy.active { background-color: #0d6efd; color: white; }
    .alert-custom {
      background-color: #4c1d1d;
      color: #f8d7da;
      border: 1px solid #f5c2c7;
      margin-bottom: 1rem;
      padding: 0.75rem 1rem;
    }
    .debug-box {
      background: #102735;
      color: #a7d3e9;
      font-family: monospace;
      font-size: 0.85rem;
      padding: 1rem;
      max-width: 1000px;
      margin: 2rem auto 0;
      white-space: pre-wrap;
      height: 160px;
      overflow-y: auto;
      border-top: 1px solid #2e5b78;
      border-radius: 6px;
    }
    body {
      background-color: #0d1117;
      color: #e0e0e0;
      font-family: 'Segoe UI', Roboto, sans-serif;
    }
    h2, h5 {
      color: #4fc3f7;
    }
  </style>
</head>
<body>
  <div class="container py-4">

  <h2 class="mb-4">Paso 3 – Elegí el tipo de mecanizado y la estrategia</h2>

  <!-- Si no hay estrategias, mostramos un cartel -->
  <?php if (empty($grouped)): ?>
    <div class="alert-custom">
      No se encontraron estrategias disponibles para esta herramienta.
    </div>
  <?php endif; ?>

  <form method="post" id="strategyForm" novalidate>
    <!-- Campos ocultos: step, CSRF, IDs seleccionados -->
    <input type="hidden" name="step" value="3">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
    <input type="hidden" name="machining_type_id" id="machining_type_id" value="">
    <input type="hidden" name="strategy_id" id="strategy_id" value="">

    <!-- 1) Selección de tipo de mecanizado -->
    <h5>Tipo de mecanizado</h5>
    <div id="machining-buttons" class="mb-4">
      <?php foreach ($grouped as $mtid => $g): ?>
        <button type="button"
                class="btn btn-outline-primary me-2 mb-2 btn-machining"
                data-id="<?= $mtid ?>">
          <?= htmlspecialchars($g['name'], ENT_QUOTES) ?>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- 2) Estrategias -->
    <div id="strategy-container" class="mb-4" style="display:none">
      <h5>Estrategia</h5>
      <div id="strategy-buttons"></div>
    </div>

    <!-- 3) Continuar -->
    <div id="next-button-container" class="text-end mt-4" style="display: none;">
      <button type="submit" id="btn-next" class="btn btn-primary btn-lg">
        Siguiente →
      </button>
    </div>
  </form>

  <!-- Caja de debug interno -->
  <pre id="debug" class="debug-box"></pre>

  <!-- Bootstrap Bundle JS (CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- JavaScript para manejo de botones y validaciones -->
  <script>
    // Convertir “$grouped” de PHP a objeto JS
    const grouped = <?= json_encode($grouped, JSON_UNESCAPED_UNICODE) ?>;
    const nextContainer = document.getElementById('next-button-container');
    const btnNext   = document.getElementById('btn-next');
    const inputType = document.getElementById('machining_type_id');
    const inputStrat= document.getElementById('strategy_id');

    const machiningBtns  = document.querySelectorAll('.btn-machining');
    const strategyBox    = document.getElementById('strategy-container');
    const strategyBtns   = document.getElementById('strategy-buttons');

    // Helper de debug (imprime en consola + <pre id="debug">)
    window.dbg = (...m) => {
      console.log('[DBG]', ...m);
      const box = document.getElementById('debug');
      if (box) box.textContent += m.join(' ') + '\n';
    };
    dbg('JS de Step 3 cargado. grouped=', grouped);

    // 1) Elegir tipo de mecanizado
    machiningBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        machiningBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const mtid = btn.dataset.id;
        inputType.value = mtid;

        // Mostrar/llenar estrategias correspondientes
        strategyBtns.innerHTML = '';
        const estrats = grouped[mtid]?.estrategias || [];
        estrats.forEach(e => {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'btn btn-outline-secondary btn-strategy';
          b.dataset.id = e.id;
          b.textContent = e.name;
          b.addEventListener('click', () => {
            document.querySelectorAll('.btn-strategy')
                    .forEach(bs => bs.classList.remove('active'));
            b.classList.add('active');
            inputStrat.value = e.id;
            nextContainer.style.display = 'block';
            dbg('Estrategia seleccionada:', e.id, e.name);
          });
          strategyBtns.appendChild(b);
        });
        strategyBox.style.display = 'block';
        nextContainer.style.display = 'none';
        dbg('Tipo de mecanizado seleccionado:', mtid, grouped[mtid].name);
      });
    });

    // 2) Validación extra antes de enviar el formulario
    const form = document.getElementById('strategyForm');
    form.addEventListener('submit', e => {
      const mtid = inputType.value.trim();
      const sid  = inputStrat.value.trim();
      const token = form.querySelector('input[name="csrf_token"]').value.trim();
      if (!mtid || !sid || !token) {
        e.preventDefault();
        alert('Debe seleccionar un tipo de mecanizado y una estrategia válidos.');
        dbg('Intento de submit inválido: mtid=', mtid, 'sid=', sid, 'token=', token);
      }
    });

    // 3) Evitar doble envío muy rápido
    form.addEventListener('submit', () => {
      nextContainer.style.display = 'none';
    });
  </script>
  </div>
</body>
</html>
