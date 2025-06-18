<?php
declare(strict_types=1);
/**
 * Paso 1 (Auto) – Selección de material y espesor
 * Rate-limit, cabeceras seguras, CSRF, flujo, etc.
 * Avanza a step2.php cuando:
 *   – material_id válido
 *   – thickness > 0 mm
 */
 
/* ───────────────── [A] Cabeceras de seguridad ───────────────── */
header('Content-Type: text/html; charset=UTF-8');
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=()");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");

/* ───────────────── [B] Debug opcional ───────────────── */
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors','1'); }
else        { error_reporting(0);   ini_set('display_errors','0'); }
if (!function_exists('dbg')) {
  function dbg(string $msg,$data=null){ global $DEBUG;
    if($DEBUG) error_log("[step1.php] $msg ".json_encode($data,JSON_UNESCAPED_UNICODE));
  }
}
dbg('🔧 step1.php iniciado');

/* ───────────────── [C] Sesión segura ───────────────── */
if (session_status()!==PHP_SESSION_ACTIVE){
  session_set_cookie_params([
    'lifetime'=>0,'path'=>'/wizard-stepper_git/','secure'=>true,
    'httponly'=>true,'samesite'=>'Strict'
  ]);
  session_start(); dbg('🔒 Sesión iniciada');
}

/* ───────────────── [D] Flujo: wizard_state=wizard ───────────────── */
if (empty($_SESSION['wizard_state'])||$_SESSION['wizard_state']!=='wizard'){
  dbg('❌ wizard_state no válido'); header('Location:/wizard-stepper_git/index.php'); exit;
}

/* ───────────────── [E] Rate-limit 10 POST / 5 min ───────────────── */
$ip=$_SERVER['REMOTE_ADDR']??'unk';
$_SESSION['rate_limit']=$_SESSION['rate_limit']??[];
foreach($_SESSION['rate_limit'] as $k=>$t)
  $_SESSION['rate_limit'][$k]=array_filter($t,fn($ts)=>($ts+300)>time());
if($_SERVER['REQUEST_METHOD']==='POST' && count($_SESSION['rate_limit'][$ip]??[])>=10){
  http_response_code(429); echo'<h1 style="color:red">429 – Demasiados intentos</h1>'; exit;
}

/* ───────────────── [F] CSRF token ───────────────── */
if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];

/* ───────────────── [G] Procesar POST ───────────────── */
$err=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($csrf,$_POST['csrf_token']??'')) $err='Token inválido.';
  $mat=filter_input(INPUT_POST,'material_id',FILTER_VALIDATE_INT);
  $thk=filter_input(INPUT_POST,'thickness',FILTER_VALIDATE_FLOAT);
  if(!$err && (!$mat||$mat<1))            $err='Material no válido.';
  if(!$err && (!$thk||$thk<=0))           $err='Espesor no válido.';
  if(!$err){
    $_SESSION['rate_limit'][$ip][] = time();
    session_regenerate_id(true);
    $_SESSION+=['material_id'=>$mat,'thickness'=>$thk,'wizard_progress'=>1];
    session_write_close();
    header('Location:/wizard-stepper_git/views/steps/auto/step2.php'); exit;
  }
}

/* ───────────────── [H] Cargar lista de materiales ───────────────── */
require_once __DIR__.'/../../../includes/db.php';
$cats=$pdo->query("SELECT category_id,name,parent_id FROM materialcategories ORDER BY parent_id,name")->fetchAll(PDO::FETCH_ASSOC);
$mats=$pdo->query("SELECT material_id,name,category_id FROM materials ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
/* agrupar */
$parents=[];$children=[];
foreach($cats as $c) if($c['parent_id']===null) $parents[$c['category_id']]=$c['name'];
foreach($mats as $m){
  $cid=$m['category_id'];
  $pid=array_values(array_filter($cats,fn($c)=>$c['category_id']===$cid))[0]['parent_id']??$cid;
  $children[$pid][]=['id'=>$m['material_id'],'cid'=>$cid,'name'=>$m['name']];
}
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8">
<title>Paso 1 – Material</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/material.css">
<style>
/* feedback dinámico solo para el espesor */
#thick.is-invalid{ border-color:#dc3545; }
#thick.is-valid  { border-color:#198754; }
.invalid-feedback{display:block;font-size:.875em;color:#dc3545;}
</style>
</head><body>
<main class="container py-4">

<h2 class="mb-3">Paso 1 – Elegí el material y el espesor</h2>

<?php if($err): ?><div class="alert alert-danger"><?=htmlspecialchars($err)?></div><?php endif; ?>

<form id="formMat" method="post" novalidate>
  <input type="hidden" name="step" value="1">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
  <input type="hidden" name="material_id" id="material_id">

  <!-- Buscador -->
  <div class="mb-3 position-relative">
    <label for="matSearch" class="form-label">Buscar material (2+ letras)</label>
    <input id="matSearch" class="form-control" autocomplete="off" placeholder="Ej.: MDF, Aluminio…">
    <div id="noMatchMsg">Material no encontrado</div>
    <div id="searchDropdown" class="dropdown-search"></div>
  </div>

  <!-- Categorías -->
  <h5>Categoría</h5>
  <div id="catRow" class="d-flex flex-wrap mb-3">
    <?php foreach($parents as $pid=>$pname): if(!empty($children[$pid])): ?>
      <button type="button" class="btn btn-outline-primary btn-cat" data-pid="<?=$pid?>">
        <?=htmlspecialchars($pname)?>
      </button>
    <?php endif; endforeach; ?>
  </div>

  <!-- Materiales -->
  <div id="matBox" class="mb-3" style="display:none">
    <h5>Material</h5><div id="matCol"></div>
  </div>

  <!-- Espesor -->
  <div id="thickGroup" class="mb-3" style="display:none">
    <label for="thick" class="form-label">Espesor (mm)</label>
    <input type="number" id="thick" name="thickness" class="form-control" step="0.1" min="0.1" required>
    <div class="invalid-feedback"></div>
  </div>

  <!-- Botón -->
  <div id="nextWrap" class="text-end mt-4" style="display:none">
    <button class="btn btn-primary btn-lg">Siguiente →</button>
  </div>
</form>

<pre id="debug" class="bg-dark text-info p-2 mt-4"></pre>

<script>
/* helpers */
const normalize=s=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();

/* datos PHP → JS */
const parents  = <?=json_encode($parents ,JSON_UNESCAPED_UNICODE)?>;
const children = <?=json_encode($children,JSON_UNESCAPED_UNICODE)?>;
const matsFlat = <?=json_encode($mats    ,JSON_UNESCAPED_UNICODE)?>;

/* DOM refs */
const catRow  = document.getElementById('catRow');
const matBox  = document.getElementById('matBox');
const matCol  = document.getElementById('matCol');
const matInp  = document.getElementById('material_id');
const thick   = document.getElementById('thick');
const thickGp = document.getElementById('thickGroup');
const nextW   = document.getElementById('nextWrap');
const search  = document.getElementById('matSearch');
const noMatch = document.getElementById('noMatchMsg');
const dd      = document.getElementById('searchDropdown');

/* mapa material→parent */
const mat2pid={};
Object.entries(children).forEach(([pid,arr])=>arr.forEach(m=>mat2pid[m.id]=pid));

/* UI reset */
function resetMat(){
  matCol.innerHTML=''; matBox.style.display='none';
  matInp.value=''; thick.value=''; thickGp.style.display='none';
  thick.classList.remove('is-valid','is-invalid');
  nextW.style.display='none'; search.classList.remove('is-invalid'); noMatch.style.display='none';
}
/* validación viva */
function validate(){
  const okMat = !!matInp.value;
  const okThk = parseFloat(thick.value)>0;
  thick.classList.toggle('is-invalid',!okThk);
  thick.classList.toggle('is-valid'  , okThk);
  nextW.style.display = okMat && okThk ? 'block' : 'none';
}
/* dropdown helpers (idénticos al original) */
function hideDD(){ dd.style.display='none'; dd.innerHTML=''; }
function showDD(matches){
  dd.innerHTML=''; matches.forEach(m=>{
    const item=document.createElement('div'); item.className='item';
    const term=normalize(search.value.trim()); const raw=m.name;
    const idx=normalize(raw).indexOf(term);
    item.innerHTML=idx!==-1?raw.slice(0,idx)+'<span class="hl">'+raw.slice(idx,idx+term.length)+'</span>'+raw.slice(idx+term.length):raw;
    item.onclick=()=>{ const pid=mat2pid[m.material_id];
      document.querySelector(`.btn-cat[data-pid='${pid}']`).click();
      setTimeout(()=>document.querySelector(`.btn-mat[data-mid='${m.material_id}']`).click(),0);
      hideDD();
    };
    dd.appendChild(item);
  });
  dd.style.display='block';
}
/* material botones */
catRow.querySelectorAll('.btn-cat').forEach(btn=>{
  btn.onclick=()=>{
    catRow.querySelectorAll('.btn-cat').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active'); resetMat();
    (children[btn.dataset.pid]||[]).forEach(m=>{
      const b=document.createElement('button');
      b.className='btn btn-outline-secondary btn-mat'; b.textContent=m.name; b.dataset.mid=m.id;
      b.onclick=()=>{ matCol.querySelectorAll('.btn-mat').forEach(x=>x.classList.remove('active'));
        b.classList.add('active'); matInp.value=m.id; search.value=m.name;
        thickGp.style.display='block'; validate(); hideDD(); };
      matCol.appendChild(b);
    });
    matBox.style.display='block'; hideDD();
  };
});
/* búsqueda */
search.addEventListener('input',e=>{
  const val=e.target.value.trim(); if(val.length<2){search.classList.remove('is-invalid');noMatch.style.display='none';hideDD();return;}
  const matches=matsFlat.filter(m=>normalize(m.name).includes(normalize(val)));
  if(!matches.length){resetMat();search.classList.add('is-invalid');noMatch.style.display='block';hideDD();return;}
  search.classList.remove('is-invalid');noMatch.style.display='none';showDD(matches);
});
search.addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault(); hideDD();}});
search.addEventListener('blur',()=>setTimeout(hideDD,100));
/* espesor input */
thick.addEventListener('input',validate);
/* submit final */
document.getElementById('formMat').addEventListener('submit',e=>{
  if(!(matInp.value&&parseFloat(thick.value)>0)){e.preventDefault();alert('Elegí material y espesor >0 mm');}
});
</script>
</main></body></html>
