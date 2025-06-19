<?php
/**
 * File: views/steps/auto/step3.php
 * Explorador visual de herramientas – Paso 3 (modo auto)
 * Con blindaje de sesión, CSRF, validación y debug opcional
 */

declare(strict_types=1);
require_once __DIR__ . '/../../../src/Utils/Session.php';

// ──────────────── 1) Sesión y configuración segura ──────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

// ──────────────── Cabeceras de seguridad ────────────────────────────
sendSecurityHeaders('text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
$nonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-$nonce'; style-src  'self' 'unsafe-inline';");

// ──────────────── 2) Estado del wizard ──────────────────────────────
// Si aún no se inició el wizard, lo inicializamos en Paso 3
if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state'] !== 'wizard') {
    $_SESSION['wizard_state']    = 'wizard';
    $_SESSION['wizard_progress'] = 3;
}

// Si el usuario ya completó el Paso 3 (wizard_progress > 3),
// redirigimos directamente al paso que le corresponda
$currentProgress = (int)($_SESSION['wizard_progress'] ?? 3);
if ($currentProgress > 3) {
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
    dbg('POST recibido en Step 3', $_POST);

    // 6.1) Verificar CSRF
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, (string)$postedToken)) {
        $errors[] = 'Token de seguridad inválido. Recargá la página e intentá de nuevo.';
        dbg('Error CSRF: token no coincide');
    }

    // 6.2) Verificar “step”
    $postedStep = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    if ($postedStep !== 3) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
        dbg('Error Step: se esperaba step=3, llegó step=' . $postedStep);
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

        $_SESSION['wizard_progress'] = 3; // Marcamos que ya completó Paso 3
        dbg('Paso 3 validado con éxito. tool_id=' . $toolId . ' tool_table=' . $toolTable);

        header('Location: step4.php');
        exit;
    }
}

// ──────────────── 7) Preparar mensajes de error para mostrar en HTML ─
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <title>Paso 3 – Explorador de fresas (Auto)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 y Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

  <!-- Estilos propios -->
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/step1_manual.css">

  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/steps/auto/step3.css">
</head>
<body>
  <noscript>
    <div class="alert alert-danger m-3">
      ❌ Este asistente necesita <strong>JavaScript</strong>.
    </div>
  </noscript>

  <!-- ─────────────── Formulario Paso 3 ──────────────── -->
  <form id="step3AutoForm" method="post" action="" novalidate>
    <!-- Campos ocultos para CSRF y control de paso -->
    <input type="hidden" name="step"       value="3">
    <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
    <input type="hidden" name="tool_id"     id="tool_id" value="">
    <input type="hidden" name="tool_table"  id="tool_table" value="">

    <main class="container py-4" data-debug="step3">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="step-title"><i class="bi bi-box-seam"></i> Paso 3 – Explorador de fresas</h2>
          <p class="step-desc">Elegí la herramienta recomendada para tu material.</p>
        </div>
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

          <div class="list-scroll-container">
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
                </tbody>
              </table>
            </div>
            <div id="sentinel"></div>
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
  <script src="/wizard-stepper_git/assets/js/step3_auto_browser.js"
          onload="window._TOOL_BROWSER_LOADED=true"
          onerror="console.error('❌ step3_auto_browser.js no cargó');">
  </script>
  <script type="module" src="/wizard-stepper_git/assets/js/step3_lazy.js"></script>

  <script type="module" nonce="<?= $nonce ?>">
    import { initToolTable } from '/wizard-stepper_git/assets/js/step3_auto_hook.js';
    initToolTable();
  </script>
</body>
</html>
