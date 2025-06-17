<?php
declare(strict_types=1);
/**
 * File: C:\xampp\htdocs\wizard-stepper_git\views\steps\auto\step1.php
 *
 * Paso 1 (Auto) – Selección de material y espesor
 * • Rate-limiting (10 POST / 5 minutos)
 * • Cabeceras de seguridad (HSTS, CSP, X-Content-Type-Options, etc.)
 * • Sesión segura (Secure, HttpOnly, SameSite=Strict)
 * • CSRF-token
 * • Validación de material_id y thickness
 * • Control de flujo: wizard_state y wizard_progress
 * • Avanza a step2.php
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
    function dbg(string $msg, $data = null): void {
        global $DEBUG;
        if ($DEBUG) {
            error_log("[step1.php] " . $msg . ' ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }
}
dbg('🔧 step1.php iniciado');

// -------------------------------------------
// [C] Inicio de sesión seguro
// -------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/wizard-stepper_git/',    // Fuerza la ruta base
        'domain'   => '',                    // Ajusta si usas dominio
        'secure'   => true,                  // Solo HTTPS
        'httponly' => true,                  // Inaccesible a JavaScript
        'samesite' => 'Strict'               // No compartido en cross-site
    ]);
    session_start();
    dbg('🔒 Sesión iniciada de forma segura');
}

// -------------------------------------------
// [D] Control de flujo: wizard_state = 'wizard'
// -------------------------------------------
// Si no venimos del index.php que fijó wizard_state='wizard', volvemos a index.php.
if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state'] !== 'wizard') {
    dbg('❌ wizard_state no válido → redirigiendo a /wizard-stepper_git/index.php');
    header('Location: /wizard-stepper_git/index.php');
    exit;
}

// -------------------------------------------
// [E] Rate-limiting básico por IP (10 POST en 5 min)
// -------------------------------------------
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!isset($_SESSION['rate_limit'])) {
    $_SESSION['rate_limit'] = [];
}
// Limpiar timestamps vencidos (>300 seg)
foreach ($_SESSION['rate_limit'] as $ip => $times) {
    $_SESSION['rate_limit'][$ip] = array_filter(
        $times,
        fn(int $ts) => ($ts + 300) > time()
    );
}
if (!isset($_SESSION['rate_limit'][$clientIp])) {
    $_SESSION['rate_limit'][$clientIp] = [];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && count($_SESSION['rate_limit'][$clientIp]) >= 10) {
    http_response_code(429);
    echo "<!DOCTYPE html>
<html lang=\"es\"><head><meta charset=\"UTF-8\"><title>429 Too Many Requests</title></head>
<body style=\"background:#000;color:#f00;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;\">
  <h1>429 – Demasiados intentos. Esperá unos minutos.</h1>
</body></html>";
    exit;
}

// -------------------------------------------
// [F] Generar/recuperar CSRF-token
// -------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// -------------------------------------------
// [G] Procesar POST
// -------------------------------------------
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [G.1] Validar CSRF
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals((string)$_SESSION['csrf_token'], $posted)) {
        $err = 'Token de seguridad inválido.';
        dbg('❌ Error CSRF');
    }

    // [G.2] Validar material_id y thickness
    $mat = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);
    $thk = filter_input(INPUT_POST, 'thickness',   FILTER_VALIDATE_FLOAT);

    if (!$err && ($mat === false || $mat === null || $mat < 1)) {
        $err = 'Material no válido.';
        dbg('❌ material_id inválido: ' . var_export($mat, true));
    }
    if (!$err && ($thk === false || $thk === null || $thk <= 0)) {
        $err = 'Espesor no válido.';
        dbg('❌ thickness inválido: ' . var_export($thk, true));
    }

    if (!$err) {
        // Registrar timestamp de rate-limit
        $_SESSION['rate_limit'][$clientIp][] = time();

        // Avanzar paso
        session_regenerate_id(true);
        $_SESSION['material_id']     = $mat;
        $_SESSION['thickness']       = (float)$thk;
        $_SESSION['wizard_progress'] = 1;  // Marcamos Paso 1 completado
        dbg("✅ Paso 1 completado: material={$mat}, thickness={$thk}");
        session_write_close();

        // Redirigir a Paso 2 (ruta absoluta dentro de /wizard-stepper_git/)
        header('Location: /wizard-stepper_git/views/steps/auto/step2.php');
        exit;
    }
}

// -------------------------------------------
// [H] Conexión a BD y carga de materiales
// -------------------------------------------
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/debug.php';

// Cargar categorías y materiales
$cats = $pdo->query(
    "SELECT category_id, name, parent_id
       FROM materialcategories
   ORDER BY parent_id, name"
)->fetchAll(PDO::FETCH_ASSOC);

$mats = $pdo->query(
    "SELECT material_id, name, category_id
       FROM materials
   ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

// Agrupar para la UI
$parents  = []; // id → name
$children = []; // parent_id → [ {id, name, cid}, … ]
foreach ($cats as $c) {
    if ($c['parent_id'] === null) {
        $parents[$c['category_id']] = $c['name'];
    }
}
foreach ($mats as $m) {
    $cid = $m['category_id'];
    $keys = array_column($cats, 'category_id');
    $idx  = array_search($cid, $keys, true);
    $pid  = ($idx !== false && $cats[$idx]['parent_id'] !== null)
             ? $cats[$idx]['parent_id']
             : $cid;
    $children[$pid][] = [
        'id'   => $m['material_id'],
        'cid'  => $cid,
        'name' => $m['name']
    ];
}
dbg('parents',  $parents);
dbg('children', $children);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 1 – Material</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <style>
    body {
      background-color: #0d1117;
      color: #e0e0e0;
      font-family: 'Segoe UI', Roboto, sans-serif;
    }
    .btn-cat {
      margin: .35rem .45rem;
      white-space: nowrap;
    }
    .btn-cat.active {
      background: #0d6efd !important;
      color: #fff   !important;
    }
    .btn-mat {
      margin: .25rem 0;
      width: 100%;
    }
    .btn-mat.active {
      background: #198754 !important;
      color: #fff       !important;
    }
    /* Dropdown búsqueda */
    .dropdown-search {
      position: absolute;
      width: 100%;
      max-height: 200px;
      overflow-y: auto;
      background: #000;
      border: 1px solid #444;
      z-index: 1000;
      display: none;
    }
    .dropdown-search .item {
      padding: .5rem .75rem;
      color: #f1f1f1;
      cursor: pointer;
    }
    .dropdown-search .item:hover {
      background: #333;
    }
    .dropdown-search .hl {
      background: #ffd54f;
      color: #000;
    }
    #noMatchMsg {
      color: #dc3545;
      font-size: .875rem;
      display: none;
      margin-top: .25rem;
    }
  </style>
</head>
<body class="container py-4">

  <h2 class="mb-3">Paso 1 – Elegí el material y el espesor</h2>

  <?php if (!empty($err)): ?>
    <div class="alert alert-danger">
      <?= htmlspecialchars($err, ENT_QUOTES) ?>
    </div>
  <?php endif; ?>

  <form id="formMat" method="post" novalidate>
    <!-- always-send “step” por consistencia interna -->
    <input type="hidden" name="step" value="1">
    <!-- CSRF token -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
    <!-- material_id se rellena desde JS -->
    <input type="hidden" name="material_id" id="material_id" value="">

    <!-- 1) Buscador -->
    <div class="mb-3 position-relative">
      <label for="matSearch" class="form-label">Buscar material (2+ letras)</label>
      <input
        id="matSearch"
        class="form-control"
        autocomplete="off"
        placeholder="Ej.: MDF, Aluminio…"
      >
      <div id="noMatchMsg">Material no encontrado</div>
      <div id="searchDropdown" class="dropdown-search"></div>
    </div>

    <!-- 2) Categorías -->
    <h5>Categoría</h5>
    <div id="catRow" class="d-flex flex-wrap mb-3">
      <?php foreach ($parents as $pid => $pname): ?>
        <?php if (!empty($children[$pid])): ?>
          <button
            type="button"
            class="btn btn-outline-primary btn-cat"
            data-pid="<?= $pid ?>"
          ><?= htmlspecialchars($pname, ENT_QUOTES) ?></button>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- 3) Materiales (se genera al hacer clic en Categoría) -->
    <div id="matBox" class="mb-3" style="display:none">
      <h5>Material</h5>
      <div id="matCol"></div>
    </div>

    <!-- 4) Espesor (se muestra tras elegir Material) -->
    <div id="thickGroup" class="mb-3" style="display:none">
      <label for="thick" class="form-label">Espesor (mm)</label>
      <input
        type="number"
        id="thick"
        name="thickness"
        class="form-control"
        step="0.1"
        min="0.1"
        required
      >
    </div>

    <!-- 5) Botón “Siguiente” -->
    <div class="text-end mt-4">
      <button type="submit"
              class="btn btn-primary btn-lg float-end mt-4 btn-next-step"
              id="nextBtn"
              disabled>
        Siguiente →
      </button>
    </div>
  </form>

  <pre id="debug" class="bg-dark text-info p-2 mt-4"></pre>

  <script>
  //────────────────────────────────────────────────────────────────────
  // normalizeText: Quita tildes, pasa a minúsculas
  function normalizeText(str) {
    return str.normalize('NFD')
              .replace(/[\u0300-\u036f]/g, '')
              .toLowerCase();
  }

  //────────────────────────────────────────────────────────────────────
  // Pasar datos PHP → JS
  const parents  = <?= json_encode($parents,  JSON_UNESCAPED_UNICODE) ?>;
  const children = <?= json_encode($children, JSON_UNESCAPED_UNICODE) ?>;
  const matsFlat = <?= json_encode($mats,     JSON_UNESCAPED_UNICODE) ?>;

  //────────────────────────────────────────────────────────────────────
  // Referencias DOM
  const matBox   = document.getElementById('matBox');
  const matCol   = document.getElementById('matCol');
  const matInp   = document.getElementById('material_id');
  const thick    = document.getElementById('thick');
  const thickGrp = document.getElementById('thickGroup');
  const nextBtn  = document.getElementById('nextBtn');
  const search   = document.getElementById('matSearch');
  const noMatch  = document.getElementById('noMatchMsg');
  const dropdown = document.getElementById('searchDropdown');

  //────────────────────────────────────────────────────────────────────
  // Mapa material_id → parent_id
  const matToPid = {};
  Object.entries(children).forEach(([pid, mats]) => {
    mats.forEach(m => {
      matToPid[m.id] = pid;
    });
  });

  //────────────────────────────────────────────────────────────────────
  // Funciones auxiliares
  function resetMat() {
    matCol.innerHTML = '';
    matBox.style.display = 'none';
    matInp.value = '';
    thickGrp.style.display = 'none';
    thick.value = '';
    nextBtn.disabled = true;
    search.classList.remove('is-invalid');
    noMatch.style.display = 'none';
  }

  function validate() {
    if (matInp.value && parseFloat(thick.value) > 0) {
      nextBtn.disabled = false;
    } else {
      nextBtn.disabled = true;
    }
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
    matches.forEach(m => {
      const rawText   = m.name;
      const termNorm  = normalizeText(search.value.trim());
      const rawNorm   = normalizeText(rawText);
      let highlighted = rawText;
      const idxNorm   = rawNorm.indexOf(termNorm);
      if (idxNorm !== -1) {
        let idxOrigStart = 0;
        let accumulator = '';
        for (let i = 0; i < rawText.length; i++) {
          accumulator += normalizeText(rawText[i]);
          if (accumulator.endsWith(termNorm)) {
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
      item.dataset.mid = m.material_id;
      item.onclick = () => {
        const pid = matToPid[m.material_id];
        const catBtn = document.querySelector(`.btn-cat[data-pid='${pid}']`);
        if (catBtn) catBtn.click();
        setTimeout(() => {
          const matBtn = document.querySelector(`.btn-mat[data-mid='${m.material_id}']`);
          if (matBtn) matBtn.click();
        }, 0);
        hideDropdown();
      };
      dropdown.appendChild(item);
    });
    dropdown.style.display = matches.length ? 'block' : 'none';
  }

  function attemptExactMatch() {
    const val = search.value.trim();
    if (val.length < 2) return;
    const exact = matsFlat.find(m => normalizeText(m.name) === normalizeText(val));
    if (!exact) return;
    const pid = matToPid[exact.material_id];
    const catBtn = document.querySelector(`.btn-cat[data-pid='${pid}']`);
    if (catBtn) catBtn.click();
    setTimeout(() => {
      const matBtn = document.querySelector(`.btn-mat[data-mid='${exact.material_id}']`);
      if (matBtn) matBtn.click();
    }, 0);
    hideDropdown();
  }

  //────────────────────────────────────────────────────────────────────
  // Clic en botón “Categoría”
  document.querySelectorAll('.btn-cat').forEach(btn => {
    btn.onclick = () => {
      document.querySelectorAll('.btn-cat').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const pid = btn.dataset.pid;
      resetMat();

      (children[pid] || []).forEach(m => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-outline-secondary btn-mat';
        b.textContent = m.name;
        b.dataset.mid = m.id;
        b.onclick = () => {
          document.querySelectorAll('.btn-mat').forEach(x => x.classList.remove('active'));
          b.classList.add('active');
          matInp.value = m.id;
          search.value = m.name;
          noMatchMsg(false);
          thickGrp.style.display = 'block';
          validate();
          hideDropdown();
        };
        matCol.appendChild(b);
      });

      matBox.style.display = 'block';
      hideDropdown();
    };
  });

  //────────────────────────────────────────────────────────────────────
  // Campo búsqueda “input”
  search.addEventListener('input', e => {
    const val = e.target.value.trim();
    if (val.length < 2) {
      noMatchMsg(false);
      hideDropdown();
      return;
    }
    const normTerm = normalizeText(val);
    const matches  = matsFlat.filter(m => normalizeText(m.name).includes(normTerm));
    if (matches.length === 0) {
      resetMat();
      noMatchMsg(true);
      hideDropdown();
      return;
    }
    noMatchMsg(false);
    showDropdown(matches);
  });

  //────────────────────────────────────────────────────────────────────
  // “Enter” o “blur” en búsqueda (coincidencia exacta)
  search.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      attemptExactMatch();
    }
  });
  search.addEventListener('blur', () => {
    setTimeout(attemptExactMatch, 0);
  });

  //────────────────────────────────────────────────────────────────────
  // “input” en espesor
  thick.addEventListener('input', validate);

  //────────────────────────────────────────────────────────────────────
  // Validación final on-submit
  document.getElementById('formMat').addEventListener('submit', e => {
    if (!matInp.value || !(parseFloat(thick.value) > 0)) {
      e.preventDefault();
      alert('Debés elegir un material válido y un espesor mayor a 0 antes de continuar.');
    }
  });
  </script>
</body>
</html>
