<?php
/**
 * File: views/steps/manual/step1.php
 * Explorador visual de herramientas de corte – Paso 1 (modo manual)
 * Con blindaje de sesión, CSRF, validación y debug opcional
 */

declare(strict_types=1);

// ──────────────── 1) Sesión y configuración segura ──────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

// ──────────────── 2) Estado del wizard ──────────────────────────────
// Si aún no se inició el wizard, lo inicializamos en Paso 1
if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state'] !== 'wizard') {
    $_SESSION['wizard_state']    = 'wizard';
    $_SESSION['wizard_progress'] = 1;
}

// Si el usuario ya completó el Paso 1 (wizard_progress > 1),
// redirigimos directamente al paso que le corresponda
$currentProgress = (int)($_SESSION['wizard_progress'] ?? 1);
if ($currentProgress > 1) {
    // Por ejemplo, si progress == 2, lo mandamos a step2.php
    header('Location: step' . $currentProgress . '.php');
    exit;
}

// ──────────────── 3) Debug opcional ─────────────────────────────────
$DEBUG = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($DEBUG && is_readable(__DIR__ . '/../../../includes/debug.php')) {
    require_once __DIR__ . '/../../../includes/debug.php';
    dbg('Sesión wizard cargada. Progreso actual: ' . $currentProgress);
} else {
    require_once __DIR__ . '/../../../includes/wizard_helpers.php';
}

// ──────────────── 4) Conexión a BD (solo si luego necesitas datos) ────
require_once __DIR__ . '/../../../includes/db.php';

// ──────────────── 5) Generar/verificar CSRF token ──────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ──────────────── 6) Procesar envío de formulario (POST) ────────────
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    dbg('POST recibido en Step 1', $_POST);

    // 6.1) Verificar CSRF
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, (string)$postedToken)) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
        dbg('Error CSRF: token no coincide');
    }

    // 6.2) Verificar “step”
    $postedStep = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    if ($postedStep !== 1) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
        dbg('Error Step: se esperaba step=1, llegó step=', $postedStep);
    }

    // 6.3) Validar tool_id
    $toolId = filter_input(INPUT_POST, 'tool_id', FILTER_VALIDATE_INT);
    if (!$toolId || $toolId < 1) {
        $errors[] = 'No se detectó una herramienta válida.';
        dbg('Error tool_id inválido:', $toolId);
    }

    // 6.4) Validar tool_table
    $toolTableRaw = $_POST['tool_table'] ?? '';
    // Permitimos solo letras, números y guiones bajos en el nombre de la tabla
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $toolTableRaw)) {
        $errors[] = 'Tabla de herramienta inválida.';
        dbg('Error tool_table inválido:', $toolTableRaw);
    } else {
        $toolTable = $toolTableRaw;
    }

    // 6.5) Si no hay errores, guardamos en sesión y avanzamos
    if (empty($errors)) {
        $_SESSION['tool_id']    = $toolId;
        $_SESSION['tool_table'] = $toolTable;

        // Guardar también la URL de la imagen seleccionada para usarla en el Paso 2
        require_once __DIR__ . '/../../../src/Utils/ToolService.php';
        $imgUrl = ToolService::getToolImageUrl($pdo, $toolTable, $toolId);
        $_SESSION['tool_image_url'] = $imgUrl;

        $_SESSION['wizard_progress'] = 2; // Marcamos que ya completó Paso 1
        dbg('Paso 1 validado con éxito. tool_id=', $toolId, 'tool_table=', $toolTable);

        header('Location: step2.php');
        exit;
    }
}

// ──────────────── 7) Preparar mensajes de error para mostrar en HTML ─
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 1 – Explorador de fresas (Manual)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 y Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

  <!-- Estilos propios -->
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/step1_manual.css">

  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/steps/manual/step1.css">
</head>
<body>
  <noscript>
    <div class="alert alert-danger m-3">
      ❌ Este asistente necesita <strong>JavaScript</strong>.
    </div>
  </noscript>

  <!-- ─────────────── Formulario Paso 1 ──────────────── -->
  <form id="step1ManualForm" method="post" action="" novalidate>
    <!-- Campos ocultos para CSRF y control de paso -->
    <input type="hidden" name="step"       value="1">
    <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
    <input type="hidden" name="tool_id"     id="tool_id" value="">
    <input type="hidden" name="tool_table"  id="tool_table" value="">

    <main class="container py-4" data-debug="step1">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-white m-0">
          <i class="bi bi-box-seam"></i> Explorador de fresas
        </h2>
        <img src="/wizard-stepper_git/assets/img/logo_nexgen.png"
             height="46"
             alt="logo"
             onerror="this.remove()">
      </div>

      <div class="row">
        <!-- Filtros -->
        <aside class="col-md-3 sidebar">
          <div class="card h-100 shadow-sm">
            <div class="card-header bg-primary text-white py-2">
              <i class="bi bi-funnel"></i> Filtros
            </div>
            <div id="facetBox"></div>
            <div id="brandWarning" class="alert alert-warning m-0 py-1" hidden>
              ⚠ Elegí al menos una marca
            </div>
          </div>
        </aside>

        <!-- Tabla -->
        <main class="col-md-9">
          <div class="input-group mb-2">
            <span class="input-group-text">
              <i class="bi bi-search"></i>
            </span>
            <input id="qBox"
                   class="form-control"
                   placeholder="Buscar…">
          </div>

          <div class="table-responsive">
            <table id="toolTbl"
                   class="table table-dark table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Sel.</th>
                  <th data-col="brand">Marca</th>
                  <th data-col="series_code">Serie</th>
                  <th data-col="img">Img</th>
                  <th data-col="tool_code">Código</th>
                  <th data-col="name">Nombre</th>
                  <th data-col="diameter_mm">Ø</th>
                  <th data-col="flute_count">Filos</th>
                  <th data-col="tool_type">Tipo</th>
                </tr>
              </thead>
              <tbody>
                <!-- El JS externo se encargará de poblar las filas -->
              </tbody>
            </table>
          </div>
        </main>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="mt-3">
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

    </main>
  </form>

  <!-- Caja opcional de debugging -->
  <pre id="debug" class="debug-box"></pre>

  <!-- ─────────────── Scripts ──────────────────────────────────────── -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Script principal del paso (se encarga de rellenar la tabla y habilitar radios) -->
  <script src="/wizard-stepper_git/assets/js/step1_manual_browser.js"
          onload="window._TOOL_BROWSER_LOADED=true"
          onerror="console.error('❌ step1_manual_browser.js no cargó');">
  </script>

  <!-- Alerta si no cargó el JS externo -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      setTimeout(() => {
        if (!window._TOOL_BROWSER_LOADED) {
          const msg = '❌ Falló la carga de step1_manual_browser.js';
          console.error(msg);
          document.getElementById('step1ManualForm')
                  .insertAdjacentHTML(
                    'afterbegin',
                    '<div class="alert alert-danger m-2">'+ msg +'</div>'
                  );
        }
      }, 1000);
    });
  </script>

  <!-- hook inline (no tocar tu JS externo) -->
  <script>
    /* helper global: imprime en consola + #debug */
    window.dbg = (...m) => {
      console.log('[DBG]', ...m);
      const box = document.getElementById('debug');
      if (box) box.textContent += m.join(' ') + '\n';
    };

    (() => {
      dbg('hook inline activo');
      const tbl = document.getElementById('toolTbl');
      if (!tbl) {
        dbg('tabla no encontrada');
        return;
      }

      tbl.addEventListener('click', e => {
        const btn = e.target.closest('.select-btn');
        if (!btn) return;

        // Capturamos dataset de la fila seleccionada
        document.getElementById('tool_id').value    = btn.dataset.tool_id;
        document.getElementById('tool_table').value = btn.dataset.tbl;

        // Enviamos el formulario automáticamente luego de la selección
        document.getElementById('step1ManualForm').requestSubmit();

        dbg('► herramienta seleccionada:', btn.dataset.tbl, btn.dataset.tool_id);
      });
    })();
  </script>
</body>
</html>
