<?php
/**
 * Paso 4 (Auto) – Selección de madera compatible
 *  • Solo accesible si wizard_progress ≥ 3 (es decir, se completaron los pasos previos).
 *  • Incluye comprobación CSRF en el POST.
 *  • Valida que material_id provenga efectivamente de la lista de materiales compatibles.
 *  • Valida espesor como número > 0.
 */

declare(strict_types=1);

// 1) Sesión y flujo
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

// Si no completó el paso 3, lo mandamos al paso 1
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 3) {
    header('Location: step1.php');
    exit;
}

// 2) Conexión a BD y debug
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/debug.php';

// 3) Generar/verificar CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// 4) Comprobamos que haya herramienta en sesión
if (empty($_SESSION['tool_id']) || empty($_SESSION['tool_table'])) {
    header('Location: step2.php');
    exit;
}
$toolId    = (int)$_SESSION['tool_id'];
$toolTable = preg_replace('/[^a-z0-9_]/i', '', $_SESSION['tool_table']);

// 5) Cargar lista de maderas compatibles
$compatTbl = 'toolsmaterial_' . str_replace('tools_', '', $toolTable);
$sql = "
    SELECT m.material_id, m.name AS mat, c.category_id, c.name AS cat
      FROM {$compatTbl} tm
      JOIN materials m          ON m.material_id = tm.material_id
      JOIN materialcategories c ON c.category_id = m.category_id
     WHERE tm.tool_id = :tid
       AND c.name LIKE 'Madera%'
     ORDER BY c.name, m.name
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':tid' => $toolId]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si no hay datos, mostramos alerta y dejamos avanzar igual (fall-back)
if (!$data) {
    $data = [];
}

// 6) Agrupar para la UI y construir array “flat” para validación en POST
$cats = [];
$flat = []; // [{ id, cid, name }]
foreach ($data as $r) {
    $cid = (int)$r['category_id'];
    if (!isset($cats[$cid])) {
        $cats[$cid] = [
            'name' => $r['cat'],
            'mats' => []
        ];
    }
    $cats[$cid]['mats'][] = [
        'id'   => (int)$r['material_id'],
        'name' => $r['mat'],
    ];
    $flat[] = [
        'id'   => (int)$r['material_id'],
        'cid'  => $cid,
        'name' => $r['mat'],
    ];
}

// 7) Procesar POST (validación CSRF + campos)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 7.1) CSRF
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, (string)$postedToken)) {
        $errors[] = "Token de seguridad inválido. Recargá la página e intentá de nuevo.";
    }

    // 7.2) Verificar “step”
    $postedStep = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    if ($postedStep !== 4) {
        $errors[] = "Paso inválido. Reiniciá el wizard.";
    }

    // 7.3) Validar material_id y espesor
    $matIdRaw = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);
    $thickRaw = filter_input(INPUT_POST, 'thickness', FILTER_VALIDATE_FLOAT);
    if ($matIdRaw === false || $matIdRaw === null || $matIdRaw <= 0) {
        $errors[] = "Seleccioná una madera válida.";
    }
    if ($thickRaw === false || $thickRaw === null || $thickRaw <= 0) {
        $errors[] = "Ingresá un espesor válido (> 0).";
    }

    // 7.4) Verificar que material_id exista en la lista “flat” (previene manipulación)
    if (empty($errors) && $data) {
        $found = false;
        foreach ($flat as $entry) {
            if ($entry['id'] === $matIdRaw) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $errors[] = "La madera seleccionada no es compatible.";
        }
    }

    // 7.5) Si no hay errores, guardamos en sesión y avanzamos al paso 5
    if (empty($errors)) {
        $_SESSION['material_id']     = $matIdRaw;
        $_SESSION['thickness']       = $thickRaw;
        $_SESSION['wizard_progress'] = 4;
        // IMPORTANTE: cerramos la sesión para asegurar escritura
        session_write_close();
        header('Location: step5.php');
        exit;
    }
}

// 8) Valores previos (para precargar si vino del “volver atrás”)
$prevMat      = $_SESSION['material_id'] ?? '';
$prevThick    = $_SESSION['thickness']    ?? '';
$hasPrevMat   = is_int($prevMat) && $prevMat > 0;
$hasPrevThick = is_numeric($prevThick) && $prevThick > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 4 – Selección de madera</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/step-common.css">
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/steps/manual/step4.css">
</head>
<body>

  <main class="container py-4">
    <h2>Paso 4 – Elegí la madera compatible</h2>

    <!-- Si no se encontró ninguna madera compatible, mostrar alerta -->
    <?php if (empty($data)): ?>
      <div class="alert-warning">
        No se encontraron maderas compatibles para esta fresa.
      </div>
    <?php endif; ?>

    <!-- Mostrar errores si existen -->
    <?php if (!empty($errors)): ?>
      <div class="alert alert-custom">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="" id="formWood" novalidate>
      <!-- Campo oculto “step” y CSRF -->
      <input type="hidden" name="step" value="4">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
      <input type="hidden" name="material_id" id="material_id" value="<?= $hasPrevMat ? htmlspecialchars((string)$prevMat, ENT_QUOTES) : '' ?>">

      <!-- 1) Buscador / Autocompletado -->
      <div class="mb-3 position-relative">
        <label for="matSearch" class="form-label">Buscar (2+ letras)</label>
        <input
          id="matSearch"
          class="form-control"
          autocomplete="off"
          placeholder="Ej.: MDF, Blanda…"
          <?= empty($data) ? 'disabled' : '' ?>
        >
        <div id="noMatchMsg">Material no encontrado</div>
        <div id="searchDropdown" class="dropdown-search"></div>
      </div>

      <!-- 2) Categorías de madera -->
      <h5>Categoría</h5>
      <div id="catRow" class="d-flex flex-wrap mb-3">
        <?php foreach ($cats as $cid => $c): ?>
          <button type="button"
                  class="btn btn-outline-primary btn-cat"
                  data-cid="<?= $cid ?>"
                  <?= empty($data) ? 'disabled' : '' ?>>
            <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- 3) Botones de materiales (aparecen al elegir categoría) -->
      <div id="matBox" style="display:none" class="mb-3">
        <h5>Madera</h5>
        <div id="matCol"></div>
        <div id="emptyMsg" class="text-warning mt-2" style="display:none">No se encontraron materiales compatibles</div>
      </div>

      <!-- 4) Espesor (mm) -->
      <div id="thickGroup" class="mb-3" style="display:none">
        <label for="thick" class="form-label">Espesor (mm)</label>
        <input
          type="number"
          step="0.1"
          min="0.1"
          id="thick"
          name="thickness"
          class="form-control"
          required
          <?= $hasPrevThick ? "value=\"" . htmlspecialchars((string)$prevThick, ENT_QUOTES) . "\"" : "" ?>
        >
      </div>

      <!-- 5) Botón “Siguiente” unificado -->
      <div id="next-button-container" class="text-end mt-4" style="display: none;">
        <button type="submit" id="btn-next" class="btn btn-primary btn-lg w-100 w-md-auto">
          Siguiente →
        </button>
      </div>
    </form>
  </main>

  <!-- Caja opcional de debugging -->
  <pre id="debug" class="debug-box"></pre>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
  /*────────────────────────────────────────────────────────────────────
    normalizeText(str):
      – Quita tildes, convierte a minúsculas.
      – Permite búsquedas insensibles a tildes y mayúsculas.
  ────────────────────────────────────────────────────────────────────*/
  function normalizeText(str) {
    return str.normalize('NFD')
              .replace(/[\u0300-\u036f]/g, '')
              .toLowerCase();
  }

  /*────────────────────────────────────────────────────────────────────
    Pasar datos PHP → JS
  ────────────────────────────────────────────────────────────────────*/
  const cats    = <?= json_encode($cats, JSON_UNESCAPED_UNICODE) ?>;
  const flat    = <?= json_encode($flat, JSON_UNESCAPED_UNICODE) ?>;
  const matCol  = document.getElementById('matCol');
  const matBox  = document.getElementById('matBox');
  const emptyMsg = document.getElementById('emptyMsg');
  const thickIn = document.getElementById('thick');
  const nextContainer = document.getElementById('next-button-container');
  const nextBtn = document.getElementById('btn-next');
  const search  = document.getElementById('matSearch');
  const noMatch = document.getElementById('noMatchMsg');
  const dropdown = document.getElementById('searchDropdown');
  const debugBox = document.getElementById('debug');

  /*────────────────────────────────────────────────────────────────────
    Mapa material_id → parent_id (para búsquedas rápidas)
  ────────────────────────────────────────────────────────────────────*/
  const matToPid = {};
  Object.entries(cats).forEach(([pid, info]) => {
    info.mats.forEach(m => {
      matToPid[m.id] = pid;
    });
  });
  if (debugBox) {
    debugBox.textContent = Object.entries(cats)
      .map(([id, c]) => `${c.name}: ${c.mats.length}`)
      .join('\n');
  }

  /*────────────────────────────────────────────────────────────────────
    Functions auxiliares
  ────────────────────────────────────────────────────────────────────*/
  function resetMat() {
    matCol.innerHTML = '';
    matBox.style.display = 'none';
    matInp.value = '';
    thickIn.value = '';
    thickIn.parentNode.style.display = 'none';
    nextContainer.style.display = 'none';
    search.classList.remove('is-invalid');
    noMatch.style.display = 'none';
    emptyMsg.style.display = 'none';
    hideDropdown();
  }

  function validateNext() {
    const matOk   = matInp.value !== '';
    const thickOk = parseFloat(thickIn.value) > 0;
    nextContainer.style.display = (matOk && thickOk) ? 'block' : 'none';
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
      // Resaltar coincidencia parcial
      const rawText   = m.name;
      const termNorm  = normalizeText(search.value.trim());
      const rawNorm   = normalizeText(rawText);
      let highlighted = rawText;
      const idxNorm = rawNorm.indexOf(termNorm);
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
      // Crear <div class="item">
      const item = document.createElement('div');
      item.className = 'item';
      item.innerHTML = highlighted;
      item.dataset.mid = m.id;
      item.onclick = () => {
        const pid = matToPid[m.id];
        const catBtn = document.querySelector(`.btn-cat[data-cid='${pid}']`);
        if (catBtn) catBtn.click();
        setTimeout(() => {
          const matBtn = document.querySelector(`.btn-mat[data-mid='${m.id}']`);
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
    const exact = flat.find(x => normalizeText(x.name) === normalizeText(val));
    if (!exact) return;
    const pid = matToPid[exact.id];
    const catBtn = document.querySelector(`.btn-cat[data-cid='${pid}']`);
    if (catBtn) catBtn.click();
    setTimeout(() => {
      const matBtn = document.querySelector(`.btn-mat[data-mid='${exact.id}']`);
      if (matBtn) matBtn.click();
    }, 0);
    hideDropdown();
  }

  // 1) Referencia al campo oculto material_id
  const matInp = document.getElementById('material_id');
  validateNext();

  /*────────────────────────────────────────────────────────────────────
    2) Al hacer clic en cada botón de categoría:
  ────────────────────────────────────────────────────────────────────*/
  document.querySelectorAll('.btn-cat').forEach(btn => {
    btn.onclick = () => {
      document.querySelectorAll('.btn-cat').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const cid = parseInt(btn.dataset.cid, 10);
      console.log('→ Clic en categoría', cid);
      resetMat();

      const list = (cats[cid] && cats[cid].mats) ? cats[cid].mats : [];
      console.log('→ Materiales:', list);
      list.forEach(m => {
        const b = document.createElement('button');
        b.type      = 'button';
        b.className = 'btn btn-outline-secondary btn-mat';
        b.textContent = m.name;
        b.dataset.mid = m.id;
        b.onclick = () => {
          document.querySelectorAll('.btn-mat').forEach(x => x.classList.remove('active'));
          b.classList.add('active');
          matInp.value = m.id;
          search.value = m.name;
          noMatchMsg(false);
          thickIn.parentNode.style.display = 'block';
          validateNext();
          hideDropdown();
        };
        matCol.appendChild(b);
      });

      emptyMsg.style.display = list.length ? 'none' : 'block';
      matBox.style.display = 'block';
      hideDropdown();
    };
  });

  /*────────────────────────────────────────────────────────────────────
    3) “input” en el campo de búsqueda
  ────────────────────────────────────────────────────────────────────*/
  search.addEventListener('input', e => {
    const val = e.target.value.trim();
    if (val.length < 2) {
      noMatchMsg(false);
      hideDropdown();
      return;
    }
    const normTerm = normalizeText(val);
    const matches  = flat.filter(m => normalizeText(m.name).includes(normTerm));
    if (matches.length === 0) {
      resetMat();
      noMatchMsg(true);
      return;
    }
    noMatchMsg(false);
    showDropdown(matches);
  });

  /*────────────────────────────────────────────────────────────────────
    4) “Enter” o “blur” en el campo de búsqueda (coincidencia exacta)
  ────────────────────────────────────────────────────────────────────*/
  search.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      attemptExactMatch();
    }
  });
  search.addEventListener('blur', () => {
    setTimeout(attemptExactMatch, 0);
  });

  /*────────────────────────────────────────────────────────────────────
    5) “input” en el campo de espesor activa validateNext()
  ────────────────────────────────────────────────────────────────────*/
  thickIn.addEventListener('input', validateNext);

  /*────────────────────────────────────────────────────────────────────
    6) Validación final on-submit
  ────────────────────────────────────────────────────────────────────*/
  document.getElementById('formWood').addEventListener('submit', e => {
    if (!matInp.value || !(parseFloat(thickIn.value) > 0)) {
      e.preventDefault();
      alert('Debés elegir un material válido y un espesor mayor a 0 antes de continuar.');
    }
  });
  });
  </script>
</body>
</html>
