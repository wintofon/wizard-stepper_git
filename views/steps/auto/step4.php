<?php
declare(strict_types=1);
/**
 * File: C:\xampp\htdocs\wizard-stepper_git\views\steps\auto\step4.php
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
        'path'     => '/wizard-stepper_git/',
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
    header('Location: /wizard-stepper_git/index.php');
    exit;
}
$currentProgress = (int)($_SESSION['wizard_progress'] ?? 0);
if ($currentProgress < 3) {
    dbg("❌ wizard_progress={$currentProgress} <3, redirigiendo a step3.php");
    header('Location: /wizard-stepper_git/views/steps/auto/step3.php');
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

// -------------------------------------------
// [H.1] Preparar URL de imagen
// -------------------------------------------
if ($tool && !empty($tool['image'])) {
    $tool['image_url'] = '/wizard-stepper_git/' . ltrim((string)$tool['image'], '/');
}

// -------------------------------------------
// [H.2] Cargar listado plano de herramientas
// -------------------------------------------
$tables = ['tools_sgs','tools_maykestag','tools_schneider','tools_generico'];
$toolsFlat = [];
foreach ($tables as $tbl) {
    $sql = "SELECT t.tool_id, t.tool_code, t.name, b.name AS brand
              FROM {$tbl} t
              JOIN series s ON t.series_id = s.id
              JOIN brands b ON s.brand_id  = b.id";
    try {
        $st = $pdo->query($sql);
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $toolsFlat[] = [
                'tool_id'   => (int)$row['tool_id'],
                'tool_code' => $row['tool_code'],
                'name'      => $row['name'],
                'brand'     => $row['brand'],
                'table'     => $tbl,
            ];
        }
    } catch (PDOException $e) {
        // Si falla alguna tabla, continuamos con las demás
        continue;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 4 – Elegí la herramienta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
    rel="stylesheet"
  >
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/steps/auto/step4.css">
</head>
<body>

<main class="container py-4">
  <div class="wizard-header">
    <i class="bi bi-tools"></i>
    <h2>Paso 4 – Elegí la herramienta</h2>
  </div>

  <form id="formTool" method="post" action="step5.php" novalidate>
    <input type="hidden" name="step" value="4">
    <input type="hidden" name="tool_id" id="tool_id" value="">
    <input type="hidden" name="tool_table" id="tool_table" value="">

    <div class="mb-3 position-relative">
      <label for="toolSearch" class="form-label">Buscar herramienta (2+ letras)</label>
      <input
        id="toolSearch"
        class="form-control"
        autocomplete="off"
        placeholder="Ej.: código o nombre"
      >
      <div id="toolNoMatchMsg">Herramienta no encontrada</div>
      <div id="toolDropdown" class="dropdown-search"></div>
    </div>

    <div id="next-button-container" class="text-end mt-4" style="display:none;">
      <button type="submit" id="btn-next" class="btn btn-primary btn-lg">
        Siguiente →
      </button>
    </div>
  </form>

  <pre id="debug" class="bg-dark text-info p-2 mt-4"></pre>

  <script>
  function normalizeText(str) {
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  }

  const toolsFlat = <?= json_encode($toolsFlat, JSON_UNESCAPED_UNICODE) ?>;

  const toolInp = document.getElementById('tool_id');
  const tblInp  = document.getElementById('tool_table');
  const search  = document.getElementById('toolSearch');
  const dropdown= document.getElementById('toolDropdown');
  const noMatch = document.getElementById('toolNoMatchMsg');
  const nextCont= document.getElementById('next-button-container');

  function validateNext() {
    nextCont.style.display = toolInp.value ? 'block' : 'none';
  }

  function noMatchMsg(state) {
    search.classList.toggle('is-invalid', state);
    noMatch.style.display = state ? 'block' : 'none';
  }

  function hideDropdown() {
    dropdown.style.display = 'none';
    dropdown.innerHTML = '';
  }

  function showDropdown(matches) {
    dropdown.innerHTML = '';
    matches.forEach(t => {
      const rawText = `${t.tool_code} - ${t.name}`;
      const termNorm = normalizeText(search.value.trim());
      const rawNorm = normalizeText(rawText);
      let highlighted = rawText;
      const idxNorm = rawNorm.indexOf(termNorm);
      if (idxNorm !== -1) {
        let idxOrigStart = 0;
        let acc = '';
        for (let i=0;i<rawText.length;i++) {
          acc += normalizeText(rawText[i]);
          if (acc.endsWith(termNorm)) {
            idxOrigStart = i + 1 - termNorm.length;
            break;
          }
        }
        const before = rawText.slice(0, idxOrigStart);
        const match  = rawText.slice(idxOrigStart, idxOrigStart + termNorm.length);
        const after  = rawText.slice(idxOrigStart + termNorm.length);
        highlighted = `${before}<span class="hl">${match}</span>${after}`;
      }
      const item = document.createElement('div');
      item.className = 'item';
      item.innerHTML = highlighted;
      item.onclick = () => {
        toolInp.value = t.tool_id;
        tblInp.value  = t.table;
        search.value  = rawText;
        hideDropdown();
        noMatchMsg(false);
        validateNext();
      };
      dropdown.appendChild(item);
    });
    dropdown.style.display = matches.length ? 'block' : 'none';
  }

  function attemptExactMatch() {
    const val = search.value.trim();
    if (val.length < 2) return;
    const norm = normalizeText(val);
    const exact = toolsFlat.find(t => normalizeText(`${t.tool_code} - ${t.name}`) === norm);
    if (!exact) return;
    toolInp.value = exact.tool_id;
    tblInp.value  = exact.table;
    search.value  = `${exact.tool_code} - ${exact.name}`;
    hideDropdown();
    noMatchMsg(false);
    validateNext();
  }

  search.addEventListener('input', e => {
    const val = e.target.value.trim();
    if (val.length < 2) {
      toolInp.value = '';
      tblInp.value  = '';
      noMatchMsg(false);
      hideDropdown();
      validateNext();
      return;
    }
    const term = normalizeText(val);
    const matches = toolsFlat.filter(t => normalizeText(`${t.tool_code} - ${t.name}`).includes(term));
    if (matches.length === 0) {
      toolInp.value = '';
      tblInp.value  = '';
      noMatchMsg(true);
      hideDropdown();
      validateNext();
      return;
    }
    noMatchMsg(false);
    showDropdown(matches);
  });

  search.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      attemptExactMatch();
    }
  });
  search.addEventListener('blur', () => { setTimeout(attemptExactMatch, 0); });

  </script>
</main>

</body>
</html>
