<?php
declare(strict_types=1);
/**
 * File: C:\xampp\htdocs\wizard-stepper_git\views\steps\manual\step4.php
 *
 * Paso 4 (Manual) – Selección de madera compatible
 * • Solo accesible si wizard_progress ≥ 3 (se completaron los pasos previos)
 * • Headers de seguridad + anti-caching
 * • Sesión segura (Secure, HttpOnly, SameSite=Strict)
 * • Rate-limit (10 POST / 5 min) – heredado opcional
 * • CSRF-token
 * • Carga solo las maderas compatibles con la fresa ya seleccionada
 * • Valida que material_id exista en esa lista y que thickness > 0
 * • Avanza a step5.php
 */

//────────────────────────────────────────────────────────────────────
// [A] Headers de seguridad / anti-caching
//────────────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=UTF-8');
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=()");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; "
     . "script-src 'self' 'unsafe-inline'; "            // ← inline JS presente
     . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");

//────────────────────────────────────────────────────────────────────
// [B] Errores y debug
//────────────────────────────────────────────────────────────────────
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
if (!function_exists('dbg')) {
    function dbg(string $msg, $data = null): void
    {
        global $DEBUG;
        if ($DEBUG) {
            error_log('[step4.php] ' . $msg . ' ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }
}
dbg('🔧 step4.php iniciado');

//────────────────────────────────────────────────────────────────────
// [C] Sesión segura
//────────────────────────────────────────────────────────────────────
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

//────────────────────────────────────────────────────────────────────
// [D] Flujo: asegurar paso anterior completado
//────────────────────────────────────────────────────────────────────
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 3) {
    dbg('❌ wizard_progress < 3 → redirigiendo a step1.php');
    header('Location: /wizard-stepper_git/views/steps/auto/step1.php');
    exit;
}

//────────────────────────────────────────────────────────────────────
// [E] Cargar dependencias
//────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/debug.php';

//────────────────────────────────────────────────────────────────────
// [F] CSRF-token
//────────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

//────────────────────────────────────────────────────────────────────
// [G] Verificar que exista herramienta seleccionada
//────────────────────────────────────────────────────────────────────
if (empty($_SESSION['tool_id']) || empty($_SESSION['tool_table'])) {
    dbg('❌ Sin tool_id / tool_table en sesión → step2');
    header('Location: /wizard-stepper_git/views/steps/auto/step2.php');
    exit;
}
$toolId    = (int)$_SESSION['tool_id'];
$toolTable = preg_replace('/[^a-z0-9_]/i', '', $_SESSION['tool_table']); // sanitizado

//────────────────────────────────────────────────────────────────────
// [H] Cargar lista de maderas compatibles con esta fresa
//      – materialcategories.name LIKE 'Madera%'
//────────────────────────────────────────────────────────────────────
$compatTbl = 'toolsmaterial_' . str_replace('tools_', '', $toolTable);

$sql = "
  SELECT  m.material_id,
          m.name        AS mat,
          c.category_id,
          c.name        AS cat
    FROM  {$compatTbl} tm
    JOIN  materials m          ON m.material_id = tm.material_id
    JOIN  materialcategories c ON c.category_id = m.category_id
   WHERE  tm.tool_id = :tid
     AND  c.name LIKE 'Madera%'
   ORDER BY c.name, m.name
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':tid' => $toolId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

dbg('Compat rows', $rows);

//────────────────────────────────────────────────────────────────────
// [I] Agrupar para UI
//────────────────────────────────────────────────────────────────────
$cats = [];    // cid → ['name'=>…, 'mats'=>[…]]
$flat = [];    // lista plana para validación POST
foreach ($rows as $r) {
    $cid = (int)$r['category_id'];
    if (!isset($cats[$cid])) {
        $cats[$cid] = ['name' => $r['cat'], 'mats' => []];
    }
    $cats[$cid]['mats'][] = [
        'id'   => (int)$r['material_id'],
        'name' => $r['mat']
    ];
    $flat[] = [
        'id'   => (int)$r['material_id'],
        'cid'  => $cid,
        'name' => $r['mat']
    ];
}
dbg('cats',  $cats);
dbg('flat',  $flat);

//────────────────────────────────────────────────────────────────────
// [J] Procesar POST
//────────────────────────────────────────────────────────────────────
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // J-1) CSRF
    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Token de seguridad inválido. Refrescá la página.';
    }

    // J-2) Paso correcto
    $step = filter_input(INPUT_POST, 'step', FILTER_VALIDATE_INT);
    if ($step !== 4) {
        $errors[] = 'Paso inválido. Reiniciá el asistente.';
    }

    // J-3) Inputs
    $matId = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);
    $thick = filter_input(INPUT_POST, 'thickness',   FILTER_VALIDATE_FLOAT);

    if ($matId === false || $matId === null || $matId <= 0) {
        $errors[] = 'Seleccioná una madera válida.';
    }
    if ($thick === false || $thick === null || $thick <= 0) {
        $errors[] = 'Ingresá un espesor válido (> 0).';
    }

    // J-4) Verificar que material_id esté en $flat
    if (empty($errors)) {
        $found = false;
        foreach ($flat as $f) {
            if ($f['id'] === $matId) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $errors[] = 'La madera seleccionada no es compatible con esta fresa.';
        }
    }

    // J-5) OK → guardar y avanzar
    if (empty($errors)) {
        $_SESSION['material_id']     = $matId;
        $_SESSION['thickness']       = $thick;
        $_SESSION['wizard_progress'] = 4;
        session_write_close();
        dbg("✅ Paso 4 completado: material={$matId}, thickness={$thick}");
        header('Location: /wizard-stepper_git/views/steps/auto/step5.php');
        exit;
    }
}

//────────────────────────────────────────────────────────────────────
// [K] Valores previos (si vuelve “atrás”)
//────────────────────────────────────────────────────────────────────
$prevMat   = $_SESSION['material_id'] ?? '';
$prevThick = $_SESSION['thickness']   ?? '';
$hasPrevM  = is_int($prevMat) && $prevMat > 0;
$hasPrevT  = is_numeric($prevThick) && $prevThick > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 4 – Madera compatible</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap local o CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Estilos comunes + específicos -->
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/components/step-common.css">
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/components/material.css">
</head>
<body>

<main class="container py-4">
  <h2 class="mb-3">Paso 4 – Elegí la madera compatible</h2>

  <?php if (empty($rows)): ?>
    <div class="alert alert-warning">Esta fresa no tiene maderas compatibles registradas.</div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e, ENT_QUOTES) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form id="formWood" method="post" novalidate>
    <input type="hidden" name="step" value="4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
    <input type="hidden" name="material_id" id="material_id"
           value="<?= $hasPrevM ? htmlspecialchars((string)$prevMat, ENT_QUOTES) : '' ?>">

    <!-- 1) Buscador -->
    <div class="mb-3 position-relative">
      <label for="matSearch" class="form-label">Buscar madera (2+ letras)</label>
      <input id="matSearch" class="form-control" autocomplete="off"
             placeholder="Ej.: MDF…" <?= $rows ? '' : 'disabled' ?>>
      <div id="noMatchMsg">Sin coincidencias</div>
      <div id="searchDropdown" class="dropdown-search"></div>
    </div>

    <!-- 2) Categorías -->
    <h5>Categoría</h5>
    <div id="catRow" class="d-flex flex-wrap mb-3">
      <?php foreach ($cats as $cid => $c): ?>
        <button type="button"
                class="btn btn-outline-primary btn-cat"
                data-cid="<?= $cid ?>"
                <?= $rows ? '' : 'disabled' ?>>
          <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- 3) Materiales -->
    <div id="matBox" class="mb-3" style="display:none">
      <h5>Madera</h5>
      <div id="matCol"></div>
      <div id="emptyMsg" class="text-warning mt-2" style="display:none">No hay materiales aquí</div>
    </div>

    <!-- 4) Espesor -->
    <div id="thickGroup" class="mb-3" style="display:none">
      <label for="thick" class="form-label">Espesor (mm)</label>
      <input type="number" step="0.1" min="0.1" id="thick" name="thickness"
             class="form-control"
             <?= $hasPrevT ? 'value="' . htmlspecialchars((string)$prevThick, ENT_QUOTES) . '"' : '' ?>>
    </div>

    <!-- 5) Botón “Siguiente” -->
    <div id="next-button-container" class="text-end mt-4" style="display:none">
      <button type="submit" id="btn-next" class="btn btn-primary btn-lg w-100 w-md-auto">
        Siguiente →
      </button>
    </div>
  </form>

  <pre id="debug" class="bg-dark text-info p-2 mt-4 d-none d-md-block"></pre>
</main>

<!--──────────────────────────────────────────────────────────────────
     JS inline (requiere 'unsafe-inline' en CSP o mover a archivo externo)
──────────────────────────────────────────────────────────────────-->
<script>
/* Helpers ──────────────────────────────────────────────────────────*/
function normalizeText(s){return s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();}
function qs(sel,ctx=document){return ctx.querySelector(sel);}
function qsa(sel,ctx=document){return [...ctx.querySelectorAll(sel)];}

/* Datos PHP → JS */
const cats   = <?= json_encode($cats, JSON_UNESCAPED_UNICODE) ?>;
const flat   = <?= json_encode($flat, JSON_UNESCAPED_UNICODE) ?>;

/* Refs DOM */
const matInp   = qs('#material_id');
const thickIn  = qs('#thick');
const nextBox  = qs('#next-button-container');
const search   = qs('#matSearch');
const noMatch  = qs('#noMatchMsg');
const ddwn     = qs('#searchDropdown');
const matBox   = qs('#matBox');
const matCol   = qs('#matCol');
const emptyMsg = qs('#emptyMsg');
const debugBox = qs('#debug');

/* Mapa id → cid */
const mat2cid = {}; Object.entries(cats).forEach(([cid,o])=>o.mats.forEach(m=>mat2cid[m.id]=cid));

/* Estado inicial */
validate();

/*──────────────────────────────────────────────────────────────────*/
function validate(){
  const ok = matInp.value && parseFloat(thickIn?.value||0)>0;
  nextBox.style.display = ok? 'block':'none';
}
function hideDD(){ddwn.style.display='none'; ddwn.innerHTML='';}
function noMatchMsg(state){search.classList.toggle('is-invalid',state); noMatch.style.display=state?'block':'none';}
function resetMat(){
  matCol.innerHTML=''; matBox.style.display='none';
  matInp.value=''; thickIn.value=''; qs('#thickGroup').style.display='none';
  nextBox.style.display='none'; noMatchMsg(false); emptyMsg.style.display='none'; hideDD();
}

/* Categorías ─ click */
qsa('.btn-cat').forEach(b=>{
  b.onclick=()=>{
    qsa('.btn-cat').forEach(x=>x.classList.remove('active')); b.classList.add('active');
    const cid=+b.dataset.cid; resetMat();
    const list=cats[cid]?.mats||[];
    list.forEach(m=>{
      const btn=document.createElement('button');
      btn.type='button'; btn.className='btn btn-outline-secondary btn-mat';
      btn.textContent=m.name; btn.dataset.mid=m.id;
      btn.onclick=()=>{
        qsa('.btn-mat').forEach(x=>x.classList.remove('active')); btn.classList.add('active');
        matInp.value=m.id; search.value=m.name; noMatchMsg(false);
        qs('#thickGroup').style.display='block'; validate(); hideDD();
      };
      matCol.appendChild(btn);
    });
    emptyMsg.style.display=list.length?'none':'block';
    matBox.style.display='block';
  };
});

/* Buscador */
search.addEventListener('input',e=>{
  const val=e.target.value.trim(); if(val.length<2){noMatchMsg(false);hideDD();return;}
  const term=normalizeText(val);
  const matches=flat.filter(m=>normalizeText(m.name).includes(term));
  if(!matches.length){resetMat();noMatchMsg(true);return;}
  noMatchMsg(false); ddwn.innerHTML='';
  matches.forEach(m=>{
    const div=document.createElement('div'); div.className='item'; div.dataset.mid=m.id;
    const raw=m.name, idx=normalizeText(raw).indexOf(term);
    div.innerHTML=idx==-1?raw:raw.slice(0,idx)+'<span class="hl">'+raw.slice(idx,idx+term.length)+'</span>'+raw.slice(idx+term.length);
    div.onclick=()=>{qs(`.btn-cat[data-cid="${mat2cid[m.id]}"]`)?.click();
                     setTimeout(()=>qs(`.btn-mat[data-mid="${m.id}"]`)?.click(),0); hideDD();};
    ddwn.appendChild(div);
  });
  ddwn.style.display='block';
});
search.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();attemptExact();}});
search.addEventListener('blur',()=>setTimeout(attemptExact,0));
function attemptExact(){
  const val=search.value.trim(); if(val.length<2)return;
  const ex=flat.find(m=>normalizeText(m.name)===normalizeText(val)); if(!ex)return;
  qs(`.btn-cat[data-cid="${mat2cid[ex.id]}"]`)?.click();
  setTimeout(()=>qs(`.btn-mat[data-mid="${ex.id}"]`)?.click(),0); hideDD();
}

/* Espesor */
thickIn?.addEventListener('input',validate);

/* Validación submit */
qs('#formWood').addEventListener('submit',e=>{
  if(!matInp.value||parseFloat(thickIn.value)<=0){
    e.preventDefault(); alert('Elegí madera y espesor válido.'); }
});

/* Debug opcional */
if(debugBox){debugBox.textContent=Object.values(cats).map(c=>`${c.name}: ${c.mats.length}`).join('\n');}
</script>
</body>
</html>
