<?php
/**
 * File: views/steps/manual/step4.php
 * Paso 4 (Manual) – Elegí la madera compatible
 * --------------------------------------------------------------
 * Versión simplificada: **SIN** validar que el espesor sea > 0.
 * Solo se comprueba que el campo no quede vacío.
 * (Patrón y comentarios igual que el paso 1.)
 */

declare(strict_types=1);

/* ───────── 1) Sesión segura ───────── */
if (session_status()!==PHP_SESSION_ACTIVE){
    session_start([
        'cookie_secure'=>true,
        'cookie_httponly'=>true,
        'cookie_samesite'=>'Strict'
    ]);
}

/* ───────── 2) Flujo del wizard ───────── */
if(empty($_SESSION['wizard_state'])||$_SESSION['wizard_state']!=='wizard'){
    header('Location:/wizard-stepper_git/views/steps/auto/step1.php');exit;
}
if((int)($_SESSION['wizard_progress']??0)<3){
    header('Location:/wizard-stepper_git/views/steps/auto/step'.(int)$_SESSION['wizard_progress'].'.php');exit;
}

/* ───────── 3) Debug opcional ───────── */
$DEBUG=isset($_GET['debug'])&&$_GET['debug']==='1';
if($DEBUG&&is_readable(__DIR__.'/../../../includes/debug.php')){
    require_once __DIR__.'/../../../includes/debug.php';
    dbg('Step4: progress='.($_SESSION['wizard_progress']??'?'));
}else{ if(!function_exists('dbg')){function dbg(){}} }

/* ───────── 4) DB ───────── */
require_once __DIR__.'/../../../includes/db.php';

/* ───────── 5) CSRF ───────── */
$_SESSION['csrf_token']=$_SESSION['csrf_token']??bin2hex(random_bytes(32));
$csrfToken=$_SESSION['csrf_token'];

/* ───────── 6) Datos herramienta ───── */
$toolId =(int)($_SESSION['tool_id']??0);
$toolTbl=preg_replace('/[^a-z0-9_]/i','',$_SESSION['tool_table']??'');

/* ───────── 7) Maderas compatibles ─── */
$compat='toolsmaterial_'.str_replace('tools_','',$toolTbl);
$stmt=$pdo->prepare("
 SELECT m.material_id,m.name mat,c.category_id,c.name cat
   FROM {$compat} tm
   JOIN materials m ON m.material_id=tm.material_id
   JOIN materialcategories c ON c.category_id=m.category_id
  WHERE tm.tool_id=:tid AND c.name LIKE 'Madera%'
  ORDER BY c.name,m.name");
$stmt->execute([':tid'=>$toolId]);
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);

/* Agrupar */
$cats=[];$flat=[];
foreach($rows as $r){$cid=(int)$r['category_id'];
  $cats[$cid]['name']=$r['cat'];
  $cats[$cid]['mats'][]=['id'=>(int)$r['material_id'],'name'=>$r['mat']];
  $flat[]=['id'=>(int)$r['material_id'],'cid'=>$cid,'name'=>$r['mat']];
}

/* ───────── 8) POST ───────── */
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($csrfToken,$_POST['csrf_token']??''))          $errors[]='Token inválido.';
  if((int)($_POST['step']??0)!==4)                               $errors[]='Paso inválido.';
  $mat=filter_input(INPUT_POST,'material_id',FILTER_VALIDATE_INT);
  $thk=filter_input(INPUT_POST,'thickness',FILTER_VALIDATE_FLOAT);
  if(!$mat||$mat<=0)                                             $errors[]='Seleccioná una madera válida.';
  if($thk===false||$thk===null)                                  $errors[]='Ingresá un espesor.';
  if(!$errors&&!in_array($mat,array_column($flat,'id'),true))    $errors[]='Madera no compatible.';
  if(!$errors){
      $_SESSION+=['material_id'=>$mat,'thickness'=>$thk,'wizard_progress'=>4];
      header('Location:/wizard-stepper_git/views/steps/manual/step5.php');exit;
  }
}

/* ───────── 9) Previos ───────── */
$prevMat=$_SESSION['material_id']??'';
$prevThk=$_SESSION['thickness']??'';
$imgUrl =$_SESSION['tool_image_url']??'';
$hasPrev=$prevMat!==''&&$prevThk!=='';
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><title>Paso 4 – Madera compatible</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/step-common.css">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/step2_manual.css">
<link rel="stylesheet" href="/wizard-stepper_git/assets/css/material.css">
<style>
.invalid-feedback{display:none;font-size:.875em;color:#dc3545;}
#thick.is-invalid + .invalid-feedback{display:block;}
#thick.is-valid{border-color:#198754;}
</style>
</head><body>
<main class="container py-4">
<h2 class="mb-3">Paso 4 – Elegí la madera compatible</h2>

<?php if($imgUrl):?>
  <div class="card bg-dark text-white mb-3">
    <img src="<?=htmlspecialchars($imgUrl)?>" class="card-img-top tool-image" alt="Fresa" onerror="this.remove()">
    <div class="card-body p-2"><p class="card-text text-center text-muted small mb-0">Fresa seleccionada</p></div>
  </div>
<?php endif;?>

<?php if(!$rows):?>
  <div class="alert alert-warning">Esta fresa no tiene maderas compatibles registradas.</div>
<?php endif;?>

<?php if($errors):?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo'<li>'.htmlspecialchars($e).'</li>';?></ul></div>
<?php endif;?>

<form id="formWood" method="post" novalidate>
  <input type="hidden" name="step" value="4">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
  <input type="hidden" name="material_id" id="material_id" value="<?=$prevMat?>">

  <!-- Buscador -->
  <div class="mb-3 position-relative">
    <label class="form-label" for="matSearch">Buscar madera (2+ letras)</label>
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
  <div id="thickGroup" class="mb-3" style="<?= $hasPrev ? '' : 'display:none' ?>">
    <label for="thick" class="form-label">Espesor (mm)</label>
    <input type="number" step="0.1" id="thick" name="thickness"
           class="form-control"
           placeholder="Ingresá el espesor (mm)"
           value="<?= $hasPrev ? htmlspecialchars((string)$prevThk) : '' ?>">
    <div class="invalid-feedback">Ingresá un espesor.</div>
  </div>

  <!-- Botón -->
  <div id="nextBox" class="text-end mt-4" style="<?= $hasPrev ? 'block' : 'none' ?>">
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

/* Validación: sólo exige que no esté vacío */
function validate(){
  if(thickGrp.style.display==='none'){ nextBox.style.display='none'; return; }
  const filled=thick.value.trim()!=='';       // cualquiera >,=,0 sirve
  thick.classList.toggle('is-invalid', !filled);
  thick.classList.toggle('is-valid',   filled);
  nextBox.style.display = filled && matInp.value ? 'block' : 'none';
}
function showNoMatch(state){
  if(thickGrp.style.display==='none') return;
  search.classList.toggle('is-invalid',state);
  noMatch.style.display=state?'block':'none';
}
function hideDD(){ ddwn.style.display='none'; ddwn.innerHTML=''; }
function resetMat(){
  matCol.innerHTML=''; matBox.style.display='none';
  matInp.value=''; thick.value=''; thickGrp.style.display='none';
  nextBox.style.display='none'; search.classList.remove('is-invalid');
  noMatch.style.display='none'; hideDD();
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
        thickGrp.style.display='block'; showNoMatch(false); validate(); hideDD();
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
  if(v.length<2){ showNoMatch(false); hideDD(); return; }
  const term=norm(v);
  const matches=flat.filter(m=>norm(m.name).includes(term));
  if(!matches.length){ resetMat(); showNoMatch(true); return; }
  showNoMatch(false); ddwn.innerHTML=''; ddwn.style.display='block';
  matches.forEach(m=>{
    const div=document.createElement('div'); div.className='item';
    const raw=m.name,idx=norm(raw).indexOf(term);
    div.innerHTML= idx===-1?raw
      : raw.slice(0,idx)+'<span class="hl">'+raw.slice(idx,idx+term.length)+'</span>'+raw.slice(idx+term.length);
    div.onclick=()=>{
      document.querySelector(`.btn-cat[data-cid='${mat2cid[m.id]}']`)?.click();
      setTimeout(()=>document.querySelector(`.btn-mat[data-mid='${m.id}']`)?.click(),0);
      hideDD();
    }; ddwn.appendChild(div);
  });
});
search.addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault(); hideDD();}});
search.addEventListener('blur',()=>setTimeout(hideDD,100));

/* Espesor input */
thick.addEventListener('input',validate);

/* Submit guard */
document.getElementById('formWood').addEventListener('submit',e=>{
  if(!(matInp.value&&thick.value.trim()!=='')){
    e.preventDefault(); alert('Seleccioná madera y completá el espesor.'); }
});

/* pageshow */
window.addEventListener('pageshow',()=>{
  if(matInp.value&&thick.value.trim()!==''){
    thickGrp.style.display='block'; validate();
  }
});

/* init */
validate();
</script>
</body></html>
