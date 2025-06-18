<?php
declare(strict_types=1);
/**
 * Paso 4 (Manual) – Selección de madera compatible
 *
 * ► Cambios puntuales solicitados
 *   1. El texto de error del espesor ahora dice
 *      “El espesor no puede ser 0”.
 *   2. El cartel desaparece en cuanto el valor es válido (> 0).
 *   3. Si el usuario vuelve al paso y ya tenía material+espesor,
 *      el bloque se muestra correctamente y el botón aparece
 *      sin necesidad de refrescar la página.
 *
 * El resto del código (seguridad, flujo, queries) permanece igual.
 */

/* ─────────────────────────────── A – Cabeceras ──────────────── */
header('Content-Type: text/html; charset=UTF-8');
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=()");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; "
     . "script-src 'self' 'unsafe-inline'; "
     . "style-src  'self' 'unsafe-inline' https://cdn.jsdelivr.net;");

/* ────────────────────────────── B – Debug opcional ─────────── */
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors','1'); }
else        { error_reporting(0);    ini_set('display_errors','0'); }
if (!function_exists('dbg')) {
  function dbg(string $m,$d=null){ global $DEBUG;
    if($DEBUG) error_log('[step4.php] '.$m.' '.json_encode($d,JSON_UNESCAPED_UNICODE));}
}

/* ────────────────────────────── C – Sesión segura ──────────── */
if (session_status()!==PHP_SESSION_ACTIVE){
  session_set_cookie_params([
    'lifetime'=>0,'path'=>'/wizard-stepper_git/','secure'=>true,
    'httponly'=>true,'samesite'=>'Strict'
  ]);
  session_start();
}

/* ────────────────────────────── D – Flujo válido ───────────── */
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress']<3){
  header('Location:/wizard-stepper_git/views/steps/auto/step1.php'); exit;
}

/* ────────────────────────────── E – DB y dependencias ──────── */
require_once __DIR__.'/../../../includes/db.php';
require_once __DIR__.'/../../../includes/debug.php';

/* ────────────────────────────── F – CSRF ───────────────────── */
if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];

/* ────────────────────────────── G – Tool previa ────────────── */
if(empty($_SESSION['tool_id'])||empty($_SESSION['tool_table'])){
  header('Location:/wizard-stepper_git/views/steps/auto/step2.php'); exit;
}
$toolId =(int)$_SESSION['tool_id'];
$toolTbl=preg_replace('/[^a-z0-9_]/i','',$_SESSION['tool_table']);

/* ────────────────────────────── H – Materiales compatibles ─── */
$compat='toolsmaterial_'.str_replace('tools_','',$toolTbl);
$stmt=$pdo->prepare("
  SELECT m.material_id,m.name mat,c.category_id,c.name cat
    FROM {$compat} tm
    JOIN materials m ON m.material_id=tm.material_id
    JOIN materialcategories c ON c.category_id=m.category_id
   WHERE tm.tool_id=:tid AND c.name LIKE 'Madera%'
   ORDER BY c.name,m.name
");
$stmt->execute([':tid'=>$toolId]);
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);

/* Agrupación para UI */
$cats=[];$flat=[];
foreach($rows as $r){
  $cid=(int)$r['category_id'];
  $cats[$cid]['name']=$r['cat'];
  $cats[$cid]['mats'][]=['id'=>(int)$r['material_id'],'name'=>$r['mat']];
  $flat[]=['id'=>(int)$r['material_id'],'cid'=>$cid,'name'=>$r['mat']];
}

/* ────────────────────────────── I – Procesar POST ──────────── */
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($csrf,$_POST['csrf_token']??''))             $errors[]='Token inválido.';
  if((int)($_POST['step']??0)!==4)                             $errors[]='Paso inválido.';
  $mat=filter_input(INPUT_POST,'material_id',FILTER_VALIDATE_INT);
  $thk=filter_input(INPUT_POST,'thickness'  ,FILTER_VALIDATE_FLOAT);
  if(!$mat || $mat<=0)                                         $errors[]='Seleccioná una madera válida.';
  if(!$thk || $thk<=0)                                         $errors[]='El espesor no puede ser 0.';
  if(!$errors && !in_array($mat,array_column($flat,'id'),true))$errors[]='Madera no compatible.';
  if(!$errors){
    $_SESSION+=['material_id'=>$mat,'thickness'=>$thk,'wizard_progress'=>4];
    header('Location:/wizard-stepper_git/views/steps/manual/step5.php'); exit;
  }
}

/* ────────────────────────────── J – Previos (back) ─────────── */
$prevMat=$_SESSION['material_id']??'';
$prevThk=$_SESSION['thickness']??'';
$imgUrl =$_SESSION['tool_image_url']??'';
$hasPrev = $prevMat && $prevThk>0;
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><title>Paso 4 – Madera compatible</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/step-common.css">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/step2_manual.css">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/material.css">
<style>
/* Feedback: solo visible cuando .is-invalid está presente */
.invalid-feedback{display:none;font-size:.875em;color:#dc3545;}
#thick.is-invalid + .invalid-feedback{display:block;}
#thick.is-valid  {border-color:#198754;}
</style>
</head><body>
<main class="container py-4">
<h2 class="mb-3">Paso 4 – Elegí la madera compatible</h2>

<?php if($imgUrl): ?>
  <div class="card bg-dark text-white mb-3">
    <figure class="text-center p-3 mb-0">
      <img src="<?=htmlspecialchars($imgUrl)?>" class="tool-image" alt="Fresa" onerror="this.remove()">
      <figcaption class="text-muted mt-2">Fresa seleccionada</figcaption>
    </figure>
  </div>
<?php endif; ?>

<?php if(!$rows): ?>
  <div class="alert alert-warning">Esta fresa no tiene maderas compatibles registradas.</div>
<?php endif; ?>

<?php if($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo'<li>'.htmlspecialchars($e).'</li>';?></ul></div>
<?php endif; ?>

<form id="formWood" method="post" novalidate>
  <input type="hidden" name="step" value="4">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
  <input type="hidden" name="material_id" id="material_id" value="<?=$prevMat?>">

  <!-- Buscador -->
  <div class="mb-3 position-relative">
    <label class="form-label" for="matSearch">Buscar madera (2+ letras)</label>
    <input id="matSearch" class="form-control" autocomplete="off"
           placeholder="Ej.: MDF…" <?=$rows?'':'disabled'?>>
    <div id="noMatchMsg">Sin coincidencias</div>
    <div id="searchDropdown" class="dropdown-search"></div>
  </div>

  <!-- Categorías -->
  <h5>Categoría</h5>
  <div id="catRow" class="d-flex flex-wrap mb-3">
    <?php foreach($cats as $cid=>$c): ?>
      <button type="button" class="btn btn-outline-primary btn-cat me-2 mb-2"
              data-cid="<?=$cid?>" <?=$rows?'':'disabled'?>>
        <?=htmlspecialchars($c['name'])?>
      </button>
    <?php endforeach; ?>
  </div>

  <!-- Materiales -->
  <div id="matBox" class="mb-3" style="display:none">
    <h5>Madera</h5><div id="matCol"></div>
    <div id="emptyMsg" class="text-warning mt-2" style="display:none">No hay materiales aquí</div>
  </div>

  <!-- Espesor -->
  <div id="thickGroup"
       class="mb-3"
       style="<?= $hasPrev ? '' : 'display:none' /* muestra si había previos */ ?>">
    <label class="form-label" for="thick">Espesor (mm)</label>
    <input type="number" step="0.1" min="0.1" id="thick" name="thickness"
           class="form-control"
           placeholder="Ingresá el espesor (mm)"
           value="<?= $hasPrev ? htmlspecialchars((string)$prevThk) : '' ?>">
    <div class="invalid-feedback">El espesor no puede ser 0.</div><!-- ← texto cambiado -->
  </div>

  <!-- Botón -->
  <div id="nextBox"
       class="text-end mt-4"
       style="<?= $hasPrev ? 'block' : 'none' ?>">
    <button class="btn btn-primary btn-lg">Siguiente →</button>
  </div>
</form>
</main>

<script>
/* Helpers y datos */
const norm=s=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
const cats=<?=json_encode($cats,JSON_UNESCAPED_UNICODE)?>;
const flat=<?=json_encode($flat,JSON_UNESCAPED_UNICODE)?>;
const mat2cid={};Object.entries(cats).forEach(([cid,o])=>o.mats.forEach(m=>mat2cid[m.id]=cid));

/* Refs */
const matInp = document.getElementById('material_id');
const thick  = document.getElementById('thick');
const thickGrp = document.getElementById('thickGroup');
const nextBox= document.getElementById('nextBox');
const search = document.getElementById('matSearch');
const noMatch= document.getElementById('noMatchMsg');
const ddwn   = document.getElementById('searchDropdown');
const matBox = document.getElementById('matBox');
const matCol = document.getElementById('matCol');
const emptyMsg=document.getElementById('emptyMsg');

/* Mostrar botón solo cuando hay todo OK */
function validate(){
  const ok = matInp.value && parseFloat(thick.value)>0;
  thick.classList.toggle('is-invalid', !ok && thickGrp.style.display!=='none');
  thick.classList.toggle('is-valid',   ok);
  nextBox.style.display = ok ? 'block' : 'none';
}

/* Sin advertencias si espesor oculto */
function showNoMatch(state){
  if(thickGrp.style.display==='none') return; // ← evita conflictos
  search.classList.toggle('is-invalid',state);
  noMatch.style.display=state?'block':'none';
}

/* Reset selección material */
function resetMat(){
  matCol.innerHTML=''; matBox.style.display='none';
  matInp.value=''; thick.value='';
  thickGrp.style.display='none'; nextBox.style.display='none';
  search.classList.remove('is-invalid');
  noMatch.style.display='none'; ddwn.style.display='none';
}

/* Categorías */
document.querySelectorAll('.btn-cat').forEach(btn=>{
  btn.onclick=()=>{
    document.querySelectorAll('.btn-cat').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const cid=btn.dataset.cid; resetMat();
    const list=cats[cid]?.mats||[];
    list.forEach(m=>{
      const b=document.createElement('button');
      b.type='button'; b.className='btn btn-outline-secondary btn-mat me-2 mb-2';
      b.textContent=m.name; b.dataset.mid=m.id;
      b.onclick=()=>{
        document.querySelectorAll('.btn-mat').forEach(x=>x.classList.remove('active'));
        b.classList.add('active');
        matInp.value=m.id; search.value=m.name;
        thickGrp.style.display='block'; showNoMatch(false); validate();
        ddwn.style.display='none';
      };
      matCol.appendChild(b);
    });
    emptyMsg.style.display=list.length?'none':'block';
    matBox.style.display='block';
  };
});

/* Buscador */
search.addEventListener('input',e=>{
  const v=e.target.value.trim();
  if(v.length<2){ showNoMatch(false); ddwn.style.display='none'; return; }
  const term=norm(v);
  const matches=flat.filter(m=>norm(m.name).includes(term));
  if(!matches.length){ resetMat(); showNoMatch(true); return; }
  showNoMatch(false); ddwn.innerHTML=''; ddwn.style.display='block';
  matches.forEach(m=>{
    const div=document.createElement('div'); div.className='item';
    const raw=m.name,idx=norm(raw).indexOf(term);
    div.innerHTML= idx===-1?raw:raw.slice(0,idx)+'<span class="hl">'+raw.slice(idx,idx+term.length)+'</span>'+raw.slice(idx+term.length);
    div.onclick=()=>{
      document.querySelector(`.btn-cat[data-cid='${mat2cid[m.id]}']`)?.click();
      setTimeout(()=>document.querySelector(`.btn-mat[data-mid='${m.id}']`)?.click(),0);
      ddwn.style.display='none';
    };
    ddwn.appendChild(div);
  });
});

/* Espesor input */
thick.addEventListener('input',validate);

/* Submit guard */
document.getElementById('formWood').addEventListener('submit',e=>{
  if(!(matInp.value&&parseFloat(thick.value)>0)){
    e.preventDefault(); alert('Debés elegir madera y un espesor mayor a 0.'); }
});

/* Si vuelven al paso con valores previos, validar al cargar */
validate();
</script>
</body></html>
