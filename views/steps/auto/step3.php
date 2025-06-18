<?php
declare(strict_types=1);

/**
 * Paso 3 – Herramientas compatibles (Modo Auto)
 *
 * • Arranca sesión y valida estado/progreso.
 * • Si es POST, procesa la selección de fresa y redirige al Paso 4.
 * • Si es GET, imprime HTML + JavaScript para:
 *    – Hacer fetch AJAX a get_tools.php y cargar dinámicamente las tarjetas.
 *    – Filtrar por diámetro.
 *    – Manejar la selección y enviar POST de vuelta a este mismo archivo.
 * • Incluye puntos dbg() en cada paso para poder ver la sesión, datos recibidos,
 *   herramientas obtenidas, valores de filtro, etc.
 *
 * Requiere:
 *   /includes/debug.php        → define dbg(...)
 *   /includes/db.php           → expone $pdo = db();
 *   /src/Controller/AutoToolRecommenderController.php
 *   /ajax/get_tools.php        → devuelve JSON de fresas compatibles.
 */

use App\Controller\AutoToolRecommenderController;

////////////////////////////////////////////////////////////////////////////////
// 1) SESIÓN SEGURA + DEBUG
////////////////////////////////////////////////////////////////////////////////
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

// Carga opcional de debug.php
$DEBUG = isset($_GET['debug']) && ($_GET['debug'] === '1');
if ($DEBUG && is_readable(__DIR__ . '/../../../includes/debug.php')) {
    require_once __DIR__ . '/../../../includes/debug.php';
    dbg("🔧 [step3] Iniciando paso 3 (Modo Auto) con DEBUG=1");
} else {
    require_once __DIR__ . '/../../../includes/wizard_helpers.php';
    if (function_exists('dbg')) {
        dbg("🔧 [step3] Iniciando paso 3 (Modo Auto) sin DEBUG");
    }
}

////////////////////////////////////////////////////////////////////////////////
// 2) INCLUIR DB + CONTROLADOR
////////////////////////////////////////////////////////////////////////////////
require_once __DIR__ . '/../../../includes/db.php';          // Debe definir $pdo = db();
require_once __DIR__ . '/../../../src/Controller/AutoToolRecommenderController.php';

////////////////////////////////////////////////////////////////////////////////
// 3) VERIFICAR QUE SE COMPLETARON PASOS 1 y 2 (material + estrategia)
////////////////////////////////////////////////////////////////////////////////
try {
    AutoToolRecommenderController::checkStep();
} catch (\RuntimeException $e) {
    dbg("❌ [step3] checkStep lanzó excepción: " . $e->getMessage());
    // Si falla la precondición, forzamos redirección a inicio del wizard
    header('Location: /wizard-stepper_git/index.php');
    exit;
}

// 4) CONTROL DE PROGRESO (wizard_progress ≥ 2)
$currentProgress = (int)($_SESSION['wizard_progress'] ?? 0);
if ($currentProgress < 2) {
    dbg("⚠ [step3] Progreso insuficiente ({$currentProgress}) → redirigir a paso 2");
    header('Location: /wizard-stepper_git/public/load-step.php?step=2');
    exit;
}

////////////////////////////////////////////////////////////////////////////////
// 5) PROCESAR POST (Se pulsó “Seleccionar” en alguna tarjeta)
////////////////////////////////////////////////////////////////////////////////
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    dbg("► [step3][POST] Datos recibidos:", $_POST);

    $toolIdRaw  = filter_input(INPUT_POST, 'tool_id', FILTER_VALIDATE_INT);
    $toolTblRaw = filter_input(INPUT_POST, 'tool_table', FILTER_UNSAFE_RAW);
    $stepRaw    = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);

    // 5.1) Validar “step” = 3
    if ($stepRaw !== 3) {
        dbg("❌ [step3][POST] step inválido: {$stepRaw}");
        echo "<script>alert('Paso inválido. Reinicia el wizard.'); window.location='step3.php';</script>";
        exit;
    }

    // 5.2) Sanear y validar tool_table
    $toolTblClean = '';
    if (is_string($toolTblRaw)) {
        $toolTblClean = strtolower(preg_replace('/[^a-z0-9_]/i', '', $toolTblRaw));
    }
    $allowed = ['tools_sgs','tools_maykestag','tools_schneider','tools_generico'];

    if (!is_int($toolIdRaw) || $toolIdRaw <= 0) {
        dbg("❌ [step3][POST] tool_id inválido: " . var_export($toolIdRaw, true));
        echo "<script>alert('ID de herramienta inválido.'); window.location='step3.php';</script>";
        exit;
    }
    if (!in_array($toolTblClean, $allowed, true)) {
        dbg("❌ [step3][POST] tool_table inválido: {$toolTblRaw} → limpio: {$toolTblClean}");
        echo "<script>alert('Tabla de herramienta inválida.'); window.location='step3.php';</script>";
        exit;
    }

    // 5.3) Guardar en sesión y avanzar a paso 4
    $_SESSION['tool_id']         = $toolIdRaw;
    $_SESSION['tool_table']      = $toolTblClean;
    $_SESSION['wizard_progress'] = 3;
    session_regenerate_id(true);
    dbg("✅ [step3][POST] Herramienta guardada en sesión → tool_id={$toolIdRaw} tool_table={$toolTblClean}");
    header('Location: /wizard-stepper_git/public/load-step.php?step=4');
    exit;
}

////////////////////////////////////////////////////////////////////////////////
// 6) GET (sin POST): obtenemos datos de sesión para pasarlos a JS
////////////////////////////////////////////////////////////////////////////////
try {
    $data = AutoToolRecommenderController::getSessionData();
    dbg("ℹ [step3][GET] data obtenida de sesión:", $data);
} catch (\RuntimeException $e) {
    dbg("❌ [step3][GET] getSessionData lanzó excepción: " . $e->getMessage());
    // Si por algún motivo falta material/estrategia, redirigimos a paso 2
    header('Location: /wizard-stepper_git/public/load-step.php?step=2');
    exit;
}

////////////////////////////////////////////////////////////////////////////////
// 7) IMPRIMIR HTML + JavaScript (AJAX) para cargar y filtrar dinámicamente.
////////////////////////////////////////////////////////////////////////////////
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 3 – Herramientas compatibles (Auto)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/steps/auto/step3.css">
</head>
<body>
  <main class="container py-4">

  <h2>Paso 3 – Herramientas compatibles (Modo Auto)</h2>

  <!-- 7.1) FILTRO POR DIÁMETRO -->
  <div class="mb-3">
    <label for="diaFilter" class="form-label">Filtrar por diámetro</label>
    <select id="diaFilter" class="form-select">
      <option value="">— Todos —</option>
      <!-- Las opciones se inyectarán vía JS -->
    </select>
  </div>

  <!-- 7.2) Contenedor donde se agregarán las tarjetas -->
  <div id="toolContainer">
    <!-- ↓ Aquí se pintarán dinámicamente las “fresa-card” por JS ↓ -->
  </div>

  <!-- 7.3) Formulario oculto que se usará al pulsar “Seleccionar” -->
  <form id="selectForm" method="post" action="" style="display:none;">
    <input type="hidden" name="step" value="3">
    <input type="hidden" id="tool_id"    name="tool_id"    value="">
    <input type="hidden" id="tool_table" name="tool_table" value="">
  </form>

  <!-- Botón "Siguiente" removido -->

  <!-- 7.4) Consola interna de debugging -->
  <pre id="debug" class="bg-dark text-info p-2 mt-4"></pre>

  <!-- Bootstrap JS (para estilos, no es estrictamente necesario) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- 7.5) Script inline con toda la lógica AJAX + render + filtrado -->
  <script>
  (() => {
    // Helper de debug: imprime en consola y en <pre id="debug">
    window.dbg = (...msgs) => {
      console.log('[STEP-3]', ...msgs);
      const box = document.getElementById('debug');
      if (box) box.textContent += msgs.join(' ') + '\n';
    };

    dbg('ℹ [step3.js] Iniciando lógica de Paso 3 (Auto)');

    // Extraer datos de sesión PHP para pasarlos a JS:
    const materialId = <?= json_encode($data['material_id'], JSON_THROW_ON_ERROR) ?>;
    const strategyId = <?= json_encode($data['strategy_id'], JSON_THROW_ON_ERROR) ?>;
    const thickness  = <?= json_encode($data['thickness'], JSON_THROW_ON_ERROR) ?>;

    dbg('ℹ [step3.js] materialId=', materialId, 'strategyId=', strategyId, 'thickness=', thickness);

    const diaFilter    = document.getElementById('diaFilter');
    const container    = document.getElementById('toolContainer');
    const selectForm   = document.getElementById('selectForm');
    const inputToolId  = document.getElementById('tool_id');
    const inputToolTbl = document.getElementById('tool_table');

    let allTools = [];     // Aquí se guardará el array de fresas recibido
    let diameters = [];    // Diámetros únicos (array de strings con 3 decimales)

    /**
     * 1) Hacer fetch AJAX a get_tools.php para obtener JSON de fresas.
     */
    async function fetchTools() {
      try {
        const url = `/wizard-stepper_git/ajax/get_tools.php?material_id=${encodeURIComponent(materialId)}&strategy_id=${encodeURIComponent(strategyId)}`;
        dbg('⬇ [step3.js] Fetch →', url);
        const resp = await fetch(url, { cache: 'no-store' });
        if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}`);
        }
        const data = await resp.json();
        if (!Array.isArray(data)) {
          throw new Error('Respuesta no es un array JSON');
        }
        dbg('ℹ [step3.js] Respuesta recibida:', data);
        allTools = data;
        renderTools();
      } catch (err) {
        dbg('❌ [step3.js] Error en fetchTools →', err);
        container.innerHTML = `<div class="alert alert-danger">Error al cargar herramientas: ${err.message}</div>`;
      }
    }

    /**
     * 2) Extrae diámetros únicos de allTools y los ordena.
     */
    function extractDiameters() {
      const set = new Set();
      allTools.forEach(t => {
        const d = parseFloat(t.diameter_mm);
        if (!isNaN(d)) {
          // Aseguramos 3 decimales consistentes
          set.add(d.toFixed(3));
        }
      });
      diameters = Array.from(set).sort((a, b) => parseFloat(a) - parseFloat(b));
      dbg('ℹ [step3.js] Diámetros únicos extraídos →', diameters);
    }

    /**
     * 3) Rellena el <select id="diaFilter"> con las opciones de diámetro.
     */
    function fillDiameterOptions() {
      // Dejamos la opción “— Todos —” con valor ""
      diameters.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d;
        opt.textContent = `${d} mm`;
        diaFilter.appendChild(opt);
      });
      dbg('ℹ [step3.js] Opciones de diámetro añadidas al select.');
    }

    /**
     * 4) Renderiza todas las tarjetas de fresas dentro de #toolContainer.
     */
    function renderTools() {
      container.innerHTML = ''; // Limpiamos contenedor

      if (allTools.length === 0) {
        container.innerHTML = `<div class="alert alert-warning">No se encontraron herramientas compatibles.</div>`;
        return;
      }

      extractDiameters();
      fillDiameterOptions();

      allTools.forEach(tool => {
        // Normalizamos diámetro a string de 3 decimales
        const diaNorm = parseFloat(tool.diameter_mm).toFixed(3);

        // Construimos la tarjeta
        const card = document.createElement('div');
        card.className = 'fresa-card row align-items-center tool-card';
        card.setAttribute('data-dia', diaNorm);

        // Celdas internas (imagen / detalles / botón)
        const imgCol = document.createElement('div');
        imgCol.className = 'col-md-2 mb-2 mb-md-0';
        const baseUrl = '/wizard-stepper_git/';
        const img = document.createElement('img');
        img.className = 'img-fluid tool-thumb';
        if (tool.image) {
          const clean = String(tool.image).replace(/^\/+/, '');
          img.src = baseUrl + clean;
        } else {
          img.src = baseUrl + 'assets/img/logos/logo_stepper.png';
        }
        img.alt = 'Imagen de la fresa';
        img.onerror = () => { img.src = baseUrl + 'assets/img/logos/logo_stepper.png'; };
        imgCol.appendChild(img);
        card.appendChild(imgCol);

        const infoCol = document.createElement('div');
        infoCol.className = 'col-md-7';
        infoCol.innerHTML = `
          <strong>${tool.brand}</strong><br>
          ${tool.name} —
          Serie ${tool.serie} —
          Código ${tool.tool_code}<br>
          <small>
            Ø${tool.diameter_mm} mm ·
            Mango ${tool.shank_diameter_mm} mm ·
            L. útil ${tool.cut_length_mm} mm ·
            Z = ${tool.flute_count || '-'}
          </small><br>
          <span class="estrella">${'★'.repeat(parseInt(tool.rating, 10))}</span>
        `;
        if (thickness > parseFloat(tool.cut_length_mm)) {
          const warn = document.createElement('div');
          warn.className = 'warning mt-1';
          warn.innerHTML = `⚠ El espesor (${thickness} mm) supera el largo útil (${tool.cut_length_mm} mm)`;
          infoCol.appendChild(warn);
        }
        card.appendChild(infoCol);

        const btnCol = document.createElement('div');
        btnCol.className = 'col-md-3 text-md-end mt-2 mt-md-0';
        const selectBtn = document.createElement('button');
        selectBtn.type = 'button';
        selectBtn.className = 'btn btn-select';
        selectBtn.textContent = 'Seleccionar';
        // Pasamos datos tool_id y source_table para el POST
        selectBtn.dataset.tool_id   = tool.tool_id;
        selectBtn.dataset.tool_tbl  = tool.source_table;
        selectBtn.dataset.dia       = diaNorm;

        btnCol.appendChild(selectBtn);
        card.appendChild(btnCol);

        container.appendChild(card);
      });

      attachCardListeners();
      dbg('ℹ [step3.js] Se han generado ' + allTools.length + ' tarjetas.');
    }

    /**
     * 5) Agrega listener a cada botón “Seleccionar” para enviar el formulario.
     */
    function attachCardListeners() {
      document.querySelectorAll('.btn-select').forEach(btn => {
        btn.addEventListener('click', () => {
          const id  = btn.dataset.tool_id;
          const tbl = btn.dataset.tool_tbl;
          dbg('► [step3.js] Seleccionada herramienta → table=', tbl, 'tool_id=', id);
          inputToolId.value  = id;
          inputToolTbl.value = tbl;
          selectForm.requestSubmit();
        });
      });
    }

    /**
     * 6) Filtrar tarjetas por diámetro
     */
    diaFilter.addEventListener('change', () => {
      const sel = diaFilter.value;
      dbg('ℹ [step3.js] filtro de diámetro seleccionado →', sel);
      document.querySelectorAll('.tool-card').forEach(card => {
        const diaCard = card.dataset.dia;
        if (sel === '' || diaCard === sel) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });

    // Al final, disparar la carga inicial
    fetchTools();
  })();
  </script>
  </main>
</body>
</html>
