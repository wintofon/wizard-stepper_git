<?php
declare(strict_types=1);
/**
 * File: views/steps/manual/step4.php
 * Paso 4 (Manual) – Selección de madera compatible
 * -------------------------------------------------
 * • Cabeceras seguras + anti-cache  (idénticas a step1 auto)
 * • Sesión segura  (cookie Secure, HttpOnly, SameSite=Strict)
 * • Rate-limit 10 POST / 5 min por IP  (copiado de step1 auto)
 * • CSRF-token
 * • Flujo: requiere wizard_progress ≥ 3  (ya se eligió estrategia+fresa)
 * • Carga sólo las maderas compatibles con la fresa seleccionada
 * • Valida material_id ∈ lista y que thickness sea numérico (cualquier valor ≠ ””)
 * • Guarda y avanza a step5.php
 */

//
// [A] Cabeceras de seguridad / anti-cache
//
header('Content-Type: text/html; charset=UTF-8');
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=()");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");

//
// [B] Debug opcional
//
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
require_once __DIR__.'/../../../includes/wizard_helpers.php';   // dbg(), etc.
if ($DEBUG && function_exists('dbg')) dbg('🔧 step4.php iniciado');

//
// [C] Sesión segura
//
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime'=>0,
        'path'    =>'/wizard-stepper_git/',
        'secure'  =>true,
        'httponly'=>true,
        'samesite'=>'Strict'
    ]);
    session_start();
    dbg('🔒 Sesión iniciada');
}

//
// [D] Flujo: debe venir del paso 3
//
if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state']!=='wizard') {
    header('Location:/wizard-stepper_git/index.php'); exit;
}
if ((int)($_SESSION['wizard_progress']??0) < 3) {
    header('Location:/wizard-stepper_git/views/steps/auto/step'.(int)$_SESSION['wizard_progress'].'.php'); exit;
}

//
// [E] Rate-limit 10 POST / 5 min  (igual a step1 auto)
//
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unk';
$_SESSION['rate_limit'] ??= [];
$_SESSION['rate_limit'][$ip] = array_filter(
    $_SESSION['rate_limit'][$ip] ?? [],
    fn(int $ts)=>($ts+300) > time()
);
if ($_SERVER['REQUEST_METHOD']==='POST' && count($_SESSION['rate_limit'][$ip])>=10) {
    http_response_code(429);
    exit('<h1 style="color:red;text-align:center;margin-top:2rem;">429 – Demasiados intentos.</h1>');
}

//
// [F] CSRF-token
//
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

//
// [G] Cargar dependencias y comprobar herramienta seleccionada
//
require_once __DIR__.'/../../../includes/db.php';
require_once __DIR__.'/../../../includes/debug.php';

if (empty($_SESSION['tool_id']) || empty($_SESSION['tool_table'])) {
    header('Location:/wizard-stepper_git/views/steps/auto/step2.php'); exit;
}
$toolId   = (int)$_SESSION['tool_id'];
$toolTbl  = preg_replace('/[^a-z0-9_]/i','',$_SESSION['tool_table']);

//
// [H] Maderas compatibles (materialcategories.name LIKE 'Madera%')
//    – idéntico a tu código original
//
$compat = 'toolsmaterial_'.str_replace('tools_','',$toolTbl);
$q = "
  SELECT m.material_id, m.name mat, c.category_id, c.name cat
    FROM {$compat} tm
    JOIN materials            m ON m.material_id = tm.material_id
    JOIN materialcategories   c ON c.category_id = m.category_id
   WHERE tm.tool_id = :tid AND c.name LIKE 'Madera%'
   ORDER BY c.name, m.name";
$stmt = $pdo->prepare($q);
$stmt->execute([':tid'=>$toolId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
dbg('Compat rows', $rows);

//
// [I] Agrupar para UI
//
$cats=[]; $flat=[];
foreach ($rows as $r){
    $cid=(int)$r['category_id'];
    $cats[$cid]['name']=$r['cat'];
    $cats[$cid]['mats'][]=['id'=>(int)$r['material_id'],'name'=>$r['mat']];
    $flat[]=['id'=>(int)$r['material_id'],'cid'=>$cid,'name'=>$r['mat']];
}

//
// [J] Procesar POST
//
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($csrf,$_POST['csrf_token']??''))        $errors[]='Token inválido.';
    if ((int)($_POST['step']??0)!==4)                        $errors[]='Paso inválido.';
    $mat = filter_input(INPUT_POST,'material_id',FILTER_VALIDATE_INT);
    $thk = filter_input(INPUT_POST,'thickness'  ,FILTER_VALIDATE_FLOAT);
    if(!$mat || $mat<=0)                                     $errors[]='Seleccioná una madera válida.';
    if($thk===false || $thk===null)                          $errors[]='Ingresá un espesor.';
    if(!$errors && !in_array($mat,array_column($flat,'id'),true))
        $errors[]='La madera seleccionada no es compatible con esta fresa.';

    if(!$errors){
        $_SESSION['material_id']=$mat;
        $_SESSION['thickness']=$thk;
        $_SESSION['wizard_progress']=4;
        $_SESSION['rate_limit'][$ip][] = time();
        header('Location:/wizard-stepper_git/views/steps/manual/step5.php'); exit;
    }
}

//
// [K] Previos
//
$prevMat = $_SESSION['material_id']??'';
$prevThk = $_SESSION['thickness']??'';
$hasPrev = $prevMat!=='' && $prevThk!=='';
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8">
<title>Paso 4 – Madera compatible</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/step-common.css">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/material.css">
</head><body>
<main class="container py-4">
<h2 class="mb-3">Paso 4 – Elegí la madera compatible</h2>

<?php if(!$rows):?>
  <div class="alert alert-warning">Esta fresa no tiene maderas compatibles registradas.</div>
<?php endif;?>

<?php if($errors):?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo'<li>'.htmlspecialchars($e).'</li>';?></ul></div>
<?php endif;?>

<form id="formWood" method="post" novalidate>
  <input type="hidden" name="step" value="4">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
  <input type="hidden" name="material_id" id="material_id" value="<?=$prevMat?>">

  <!-- Buscador -->
  <div class="mb-3 position-relative">
    <label for="matSearch" class="form-label">Buscar madera (2+ letras)</label>
    <input id="matSearch" class="form-control" autocomplete="off" placeholder="Ej.: MDF…" <?=$rows?'':'disabled'?>>
    <div id="noMatchMsg">Sin coincidencias</div>
    <div id="searchDropdown" class="dropdown-search"></div>
  </div>

  <!-- Categorías -->
  <h5>Categoría</h5>
  <div id="catRow" class="d-flex flex-wrap mb-3">
    <?php foreach($cats as $cid=>$c):?>
      <button type="button" class="btn btn-outline-primary btn-cat me-2 mb-2"
              data-cid="<?=$cid?>" <?=$rows?'':'disabled'?>>
        <?=htmlspecialchars($c['name'])?>
      </button>
    <?php endforeach;?>
  </div>

  <!-- Materiales -->
  <div id="matBox" class="mb-3" style="display:none">
    <h5>Madera</h5><div id="matCol"></div>
    <div id="emptyMsg" class="text-warning mt-2" style="display:none">No hay materiales aquí</div>
  </div>

  <!-- Espesor -->
  <div id="thickGroup" class="mb-3" style="<?=$hasPrev?'':'display:none'?>">
    <label for="thick" class="form-label">Espesor (mm)</label>
    <input type="number" step="0.1" id="thick" name="thickness"
           class="form-control" placeholder="Ingresá el espesor (mm)"
           value="<?=$hasPrev?htmlspecialchars((string)$prevThk):''?>">
    <div class="invalid-feedback">Ingresá un espesor.</div>
  </div>

  <!-- Botón -->
  <div id="nextBox" class="text-end mt-4" style="<?=$hasPrev?'block':'none'?>">
    <button class="btn btn-primary btn-lg">Siguiente →</button>
  </div>
</form>
</main>

<!-- ─────────── JS (copiado del anterior) ─────────── -->
<script>
const norm=s=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
const cats=<?=json_encode($cats,JSON_UNESCAPED_UNICODE)?>;
const flat=<?=json_encode($flat,JSON_UNESCAPED_UNICODE)?>;
const mat2cid={};Object.entries(cats).forEach(([cid,o])=>o.mats.forEach(m=>mat2cid[m.id]=cid));

const matInp=document.getElementById('material_id');
const thick=document.getElementById('thick');
const thickGrp=document.getElementById('thickGroup');
const nextBox=document.getElementById('nextBox');
const search=document.getElementById('matSearch');
const noMatch=document.getElementById('noMatchMsg');
const ddwn=document.getElementById('searchDropdown');
const matBox=document.getElementById('matBox');
const matCol=document.getElementById('matCol');
const emptyMsg=document.getElementById('emptyMsg');

function validate(){
  const filled=thick.value.trim()!=='';                        // sólo que no esté vacío
  thick.classList.toggle('is-invalid',!filled);
  thick.classList.toggle('is-valid',filled);
  nextBox.style.display = filled && matInp.value ? 'block':'none';
}
function showNoMatch(state){
  if(thickGrp.style.display==='none') return;
  search.classList.toggle('is-invalid',state); noMatch.style.display=state?'block':'none';
}
function hideDD(){ddwn.style.display='none'; ddwn.innerHTML='';}
function resetMat(){
  matCol.innerHTML=''; matBox.style.display='none';
  matInp.value=''; thick.value=''; thickGrp.style.display='none';
  nextBox.style.display='none'; showNoMatch(false); emptyMsg.style.display='none'; hideDD();
}

document.querySelectorAll('.btn-cat').forEach(btn=>{
  btn.onclick=()=>{
    document.querySelectorAll('.btn-cat').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const cid=btn.dataset.cid; resetMat();
    (cats[cid]?.mats||[]).forEach(m=>{
      const b=document.createElement('button');
      b.type='button'; b.className='btn btn-outline-secondary btn-mat me-2 mb-2';
      b.textContent=m.name; b.dataset.mid=m.id;
      b.onclick=()=>{
        document.querySelectorAll('.btn-mat').forEach(x=>x.classList.remove('active'));
        b.classList.add('active');
        matInp.value=m.id; search.value=m.name;
        thickGrp.style.display='block'; showNoMatch(false); validate(); hideDD();
      };
      matCol.appendChild(b);
    });
    emptyMsg.style.display=(cats[cid]?.mats||[]).length?'none':'block';
    matBox.style.display='block';
  };
});

search.addEventListener('input',e=>{
  const v=e.target.value.trim();
  if(v.length<2){showNoMatch(false);hideDD();return;}
  const t=norm(v);
  const matches=flat.filter(m=>norm(m.name).includes(t));
  if(!matches.length){resetMat();showNoMatch(true);return;}
  showNoMatch(false); ddwn.innerHTML=''; ddwn.style.display='block';
  matches.forEach(m=>{
    const div=document.createElement('div'); div.className='item';
    const raw=m.name,idx=norm(raw).indexOf(t);
    div.innerHTML=idx===-1?raw:raw.slice(0,idx)+'<span class="hl">'+raw.slice(idx,idx+t.length)+'</span>'+raw.slice(idx+t.length);
    div.onclick=()=>{
      document.querySelector(`.btn-cat[data-cid='${mat2cid[m.id]}']`)?.click();
      setTimeout(()=>document.querySelector(`.btn-mat[data-mid='${m.id}']`)?.click(),0);
      hideDD();
    };
    ddwn.appendChild(div);
  });
});
search.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();hideDD();}});
search.addEventListener('blur',()=>setTimeout(hideDD,80));

thick?.addEventListener('input',validate);

document.getElementById('formWood').addEventListener('submit',e=>{
  if(!(matInp.value && thick.value.trim()!==''))
    {e.preventDefault(); alert('Seleccioná madera y completá el espesor.');}
});

window.addEventListener('pageshow',()=>{
  if(matInp.value && thick.value.trim()!==''){
    thickGrp.style.display='block'; validate();
  }
});

/* init */
validate();
</script>
</body></html>
