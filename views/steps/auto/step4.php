<?php
declare(strict_types=1);
/**
 * File: C:\xampp\htdocs\wizard-stepper\views\steps\auto\step4.php
 *
 * Paso 4 (Auto) – Confirmar herramienta seleccionada
 * • POST desde step3.php o GET con brand+code
 * • Validación de CSRF y flujo (wizard_progress ≥ 3)
 * • fetchTool() con WHERE seguro (por ID o por code)
 * • Guarda tool_id, tool_table en sesión y avanza a step5.php
 */

//
// [A] Cabeceras de seguridad / anti-caching
//
header('Content-Type: text/html; charset=UTF-8');
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=()");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");

//
// [B] Errores y Debug
//
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
if (!function_exists('dbg')) {
    function dbg(string $tag, $data = null): void {
        global $DEBUG;
        if ($DEBUG) {
            error_log("[step4.php] " . $tag . ' ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }
}
dbg('🔧 step4.php iniciado');

// -------------------------------------------
// [C] Inicio de sesión seguro
// -------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/wizard-stepper/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
    dbg('🔒 Sesión iniciada');
}

// -------------------------------------------
// [D] Validar flujo: wizard_progress ≥ 3
// -------------------------------------------
if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state'] !== 'wizard') {
    dbg('❌ wizard_state no válido, redirigiendo a index.php');
    header('Location: /wizard-stepper/index.php');
    exit;
}
$currentProgress = (int)($_SESSION['wizard_progress'] ?? 0);
if ($currentProgress < 3) {
    dbg("❌ wizard_progress={$currentProgress} <3, redirigiendo a step3.php");
    header('Location: /wizard-stepper/views/steps/auto/step3.php');
    exit;
}

// -------------------------------------------
// [E] Incluir dependencias
// -------------------------------------------
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/debug.php';

// -------------------------------------------
// [F] Funciones auxiliares
// -------------------------------------------

/** Sanitiza y valida nombre de tabla contra lista permitida */
function tblClean(string $raw): ?string {
    $clean = strtolower(preg_replace('/[^a-z0-9_]/i', '', $raw));
    return in_array($clean, ['tools_sgs','tools_maykestag','tools_schneider','tools_generico'], true)
        ? $clean
        : null;
}

/**
 * fetchTool(): Obtiene la fresa usando la tabla validada y la condición “id” o “code”
 *   – Si $by='id', hará WHERE t.tool_id = ?
 *   – Si $by='code', hará WHERE t.tool_code = ?
 */
function fetchTool(PDO $pdo, string $tbl, string $by, $val): ?array {
    if ($by === 'id') {
        $where = "t.tool_id = ?";
    } elseif ($by === 'code') {
        $where = "t.tool_code = ?";
    } else {
        return null;
    }
    $sql = "
      SELECT t.*, s.code AS serie, b.name AS brand
        FROM {$tbl} t
        JOIN series s  ON t.series_id = s.id
        JOIN brands b  ON s.brand_id  = b.id
       WHERE {$where}
    ";
    $st = $pdo->prepare($sql);
    $st->execute([ $val ]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

// -------------------------------------------
// [G] Lógica principal: detectar POST o GET
// -------------------------------------------
$error = null;
$tool  = null;

// [G.1] Si llega POST desde step3.php
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['tool_id'], $_POST['tool_table'], $_POST['step'])) {

    $stepRaw = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    if ($stepRaw !== 3) {
        $error = 'Paso inválido.';
        dbg("❌ step POST inválido: " . var_export($stepRaw, true));
    } else {
        // Validar CSRF
        $csrfPosted = $_SESSION['csrf_token'] ?? '';
        $posted     = $_POST['csrf_token']  ?? '';
        if (!hash_equals((string)$csrfPosted, $posted)) {
            $error = 'Token CSRF inválido.';
            dbg('❌ CSRF inválido en POST');
        } else {
            // Validar tool_id
            $toolId = filter_input(INPUT_POST, 'tool_id', FILTER_VALIDATE_INT);
            if ($toolId === false || $toolId === null) {
                $error = 'ID de herramienta inválido.';
                dbg('❌ tool_id inválido: ' . var_export($toolId, true));
            } else {
                // Validar tool_table
                $tblRaw = $_POST['tool_table'];
                $tbl    = tblClean((string)$tblRaw);
                if (!$tbl) {
                    $error = 'Tabla de herramientas inválida.';
                    dbg('❌ tool_table inválida: ' . var_export($tblRaw, true));
                } else {
                    $tool = fetchTool($pdo, $tbl, 'id', $toolId);
                    if (!$tool) {
                        $error = "No se encontró la herramienta #{$toolId}.";
                        dbg("❌ fetchTool no encontró tool_id={$toolId} en {$tbl}");
                    } else {
                        // Guardar en sesión y avanzar
                        session_regenerate_id(true);
                        $_SESSION['tool_id']         = $toolId;
                        $_SESSION['tool_table']      = $tbl;
                        $_SESSION['wizard_progress'] = 3;  // Marcamos Paso 3 completado
                        dbg("✅ Paso 4 POST completado: tool_id={$toolId}, table={$tbl}");
                    }
                }
            }
        }
    }
}

// [G.2] Si no llegó POST pero sí GET con brand+code
elseif ($_SERVER['REQUEST_METHOD'] === 'GET'
        && isset($_GET['brand'], $_GET['code'])) {

    $brandInput = strtoupper(trim($_GET['brand']));
    $code       = trim($_GET['code']);
    $map = [
        'SGS'       => 'tools_sgs',
        'MAYKESTAG' => 'tools_maykestag',
        'SCHNEIDER' => 'tools_schneider',
        'GENERICO'  => 'tools_generico',
    ];
    if (!array_key_exists($brandInput, $map)) {
        $error = 'Marca inválida.';
        dbg('❌ brand GET inválido: ' . var_export($brandInput, true));
    } else {
        $tbl = $map[$brandInput];
        $tool = fetchTool($pdo, $tbl, 'code', $code);
        if (!$tool) {
            $error = "No se encontró la fresa {$code}.";
            dbg("❌ fetchTool no encontró tool_code={$code} en {$tbl}");
        } else {
            session_regenerate_id(true);
            $_SESSION['tool_id']         = (int)$tool['tool_id'];
            $_SESSION['tool_table']      = $tbl;
            $_SESSION['wizard_progress'] = 3;  // Marcamos Paso 3 completado
            dbg("✅ Paso 4 GET completado: brand={$brandInput}, code={$code}");
        }
    }
}

// [G.3] Si ya hay datos de herramienta en sesión (“volver atrás”)
elseif (!empty($_SESSION['tool_id']) && !empty($_SESSION['tool_table'])) {
    $tblCleaned = tblClean((string)$_SESSION['tool_table']);
    if (!$tblCleaned) {
        $error = 'La tabla guardada en sesión no es válida.';
        unset($_SESSION['tool_id'], $_SESSION['tool_table'], $_SESSION['wizard_progress']);
        dbg("❌ tool_table en sesión no válida: " . var_export($_SESSION['tool_table'], true));
    } else {
        $tool = fetchTool($pdo, $tblCleaned, 'id', (int)$_SESSION['tool_id']);
        if (!$tool) {
            $error = 'La herramienta guardada ya no existe.';
            unset($_SESSION['tool_id'], $_SESSION['tool_table'], $_SESSION['wizard_progress']);
            dbg("❌ fetchTool no encontró tool_id=" . var_export($_SESSION['tool_id'], true));
        } else {
            dbg("✅ Cargando herramienta desde sesión: tool_id=" . $_SESSION['tool_id']);
        }
    }
}
// [G.4] Si no hay POST ni GET ni datos válidos en sesión
else {
    $error = 'Faltan parámetros para confirmar la herramienta.';
    dbg("❌ No llegó ni POST ni GET válido, ni hay tool en sesión");
}

dbg('RESULTADO paso 4', $error ?? ['tool_id' => $tool['tool_id'] ?? null]);

// -------------------------------------------
// [H] Ajuste length_total_mm
// -------------------------------------------
if (isset($tool['length_total_mm'])) {
    // ya existe
} elseif (isset($tool['full_length_mm'])) {
    $tool['length_total_mm'] = $tool['full_length_mm'];
} else {
    $tool['length_total_mm'] = 0;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 4 – Confirmar herramienta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <!-- Bootstrap Icons -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
    rel="stylesheet"
  >
  <style>
    body {
      background-color: #0d1117;
      color: #e0e0e0;
      font-family: 'Segoe UI', Roboto, sans-serif;
    }
    .wizard-body {
      max-width: 800px;
      margin: 2rem auto;
      background: #132330;
      padding: 2rem;
      border-radius: 0.75rem;
      box-shadow: 0 0 24px rgba(0,0,0,0.5);
      border: 1px solid #264b63;
    }
    .wizard-header {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
    }
    .wizard-header .bi-tools {
      font-size: 1.5rem;
      color: #4fc3f7;
    }
    .wizard-header h2 {
      color: #4fc3f7;
      margin: 0;
      font-size: 1.75rem;
    }
    .card {
      border: 1px solid #264b63;
    }
    .card-body {
      background: #1e293b;
      color: #e0e0e0;
      display: flex;
      gap: 1rem;
      align-items: flex-start;
    }
    .card img {
      max-height: 180px;
      object-fit: contain;
      border-radius: 0.25rem;
      background: #2c2f33;
    }
    .card-body h4 {
      margin-bottom: 0.5rem;
      color: #4fc3f7;
    }
    .card-body p, .card-body small {
      margin: 0.25rem 0;
    }
    .btn-back {
      background-color: transparent;
      border: 1px solid #4fc3f7;
      color: #4fc3f7;
      border-radius: 0.4rem;
      padding: 0.5rem 1rem;
      transition: background 0.3s, color 0.3s;
    }
    .btn-back:hover {
      background-color: #4fc3f7;
      color: #0d1117;
    }
    .btn-next {
      background-color: #4fc3f7;
      border: none;
      color: #0d1117;
      border-radius: 0.4rem;
      padding: 0.5rem 1rem;
      transition: background 0.3s;
    }
    .btn-next:hover {
      background-color: #0d6efd;
      color: #fff;
    }
    .alert-danger {
      background-color: #4c1d1d;
      color: #f8d7da;
      border-color: #f5c2c7;
      padding: 0.75rem 1rem;
      border-radius: 0.375rem;
      margin-bottom: 1rem;
    }
    #debug {
      background: #102735;
      color: #a7d3e9;
      font-family: monospace;
      font-size: 0.85rem;
      padding: 1rem;
      margin-top: 1.5rem;
      white-space: pre-wrap;
      height: 160px;
      overflow-y: auto;
      border-radius: 6px;
      border-top: 1px solid #2e5b78;
    }
  </style>
</head>
<body>

<main class="wizard-body">
  <div class="wizard-header">
    <i class="bi bi-tools"></i>
    <h2>Paso 4 – Confirmar herramienta</h2>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle"></i>
      <?= htmlspecialchars($error, ENT_QUOTES) ?>
    </div>
    <a href="step3.php" class="btn-back mt-3">
      <i class="bi bi-arrow-left-circle"></i> Volver a Paso 3
    </a>

  <?php else: ?>
    <div class="card mb-4">
      <div class="card-body">
        <?php if (!empty($tool['image_url'])): ?>
          <img
            src="/<?= htmlspecialchars($tool['image_url'], ENT_QUOTES) ?>"
            alt="Imagen de la herramienta"
            class="img-thumbnail"
            onerror="this.style.display='none'"
          >
        <?php endif; ?>
        <div>
          <h4 class="text-info">
            <?= htmlspecialchars($tool['tool_code'], ENT_QUOTES) ?> –
            <?= htmlspecialchars($tool['name'], ENT_QUOTES) ?>
          </h4>
          <p class="mb-1">
            <strong>Marca:</strong> <?= htmlspecialchars($tool['brand'], ENT_QUOTES) ?>
            &nbsp;|&nbsp;
            <strong>Serie:</strong> <?= htmlspecialchars($tool['serie'], ENT_QUOTES) ?>
          </p>
          <p class="mb-1">
            <strong>Ø:</strong> <?= htmlspecialchars((string)$tool['diameter_mm'], ENT_QUOTES) ?> mm
            &nbsp;|&nbsp;
            <strong>Filos:</strong> <?= htmlspecialchars((string)$tool['flute_count'], ENT_QUOTES) ?>
          </p>
          <p class="mb-1">
            <strong>Tipo:</strong> <?= htmlspecialchars($tool['tool_type'] ?? '-', ENT_QUOTES) ?>
          </p>
          <p class="mb-0">
            <strong>Long. corte:</strong> <?= htmlspecialchars((string)$tool['cut_length_mm'], ENT_QUOTES) ?> mm
            &nbsp;|&nbsp;
            <strong>Total:</strong> <?= htmlspecialchars((string)$tool['length_total_mm'], ENT_QUOTES) ?> mm
          </p>
        </div>
      </div>
    </div>

    <!-- Formulario para avanzar a Paso 5 -->
    <form action="step5.php" method="post" class="d-flex justify-content-between">
      <input type="hidden" name="step"       value="4">
      <input type="hidden" name="tool_id"    value="<?= htmlspecialchars((string)$tool['tool_id'], ENT_QUOTES) ?>">
      <input type="hidden" name="tool_table" value="<?= htmlspecialchars((string)$_SESSION['tool_table'], ENT_QUOTES) ?>">

      <a href="step3.php" class="btn-back">
        ← Volver a Paso 3
      </a>
      <button type="submit" class="btn-next">
        Siguiente → Paso 5
      </button>
    </form>
  <?php endif; ?>
</main>

<pre id="debug"></pre>
</body>
</html>
