<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/Utils/Session.php';
/**
 * File: views/steps/manual/step4.php
 * Paso 4 (Manual) – Selección de madera compatible
 * Estructura clonada de paso 1 (auto)
 */

//
// [A] Cabeceras de seguridad / anti-caching
//
sendSecurityHeaders('text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");

//
// [B] Errores y Debug
//
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors', '1'); }
else        { error_reporting(0);    ini_set('display_errors', '0'); }

require_once __DIR__.'/../../../includes/wizard_helpers.php';
if ($DEBUG && function_exists('dbg')) dbg('🔧 step4.php iniciado');

//
// [C] Sesión segura
//
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime'=>0,
        'path'    => BASE_URL . '/',
        'secure'  =>true,
        'httponly'=>true,
        'samesite'=>'Strict'
    ]);
    session_start();
    dbg('🔒 Sesión iniciada');
}

//
// [D] Control de flujo
//
if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state']!=='wizard') {
    header('Location:' . asset('index.php')); exit;
}
if ((int)($_SESSION['wizard_progress']??0) < 3) {
    header('Location:' . asset('views/steps/auto/step' . (int)$_SESSION['wizard_progress'] . '.php')); exit;
}

//
// [E] Rate-limiting 10 POST / 5 min
//
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$_SESSION['rate_limit'] ??= [];
$_SESSION['rate_limit'][$clientIp] = array_filter(
    $_SESSION['rate_limit'][$clientIp] ?? [],
    fn(int $t)=>($t+300) > time()
);
if ($_SERVER['REQUEST_METHOD']==='POST' && count($_SESSION['rate_limit'][$clientIp])>=10) {
    http_response_code(429);
    exit('<h1 style="color:red;text-align:center;margin-top:2rem;">429 – Demasiados intentos.</h1>');
}

//
// [F] CSRF-token
//
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

//
// [G] Dependencias + herramienta seleccionada
//
require_once __DIR__.'/../../../includes/db.php';
require_once __DIR__.'/../../../includes/debug.php';

if (empty($_SESSION['tool_id']) || empty($_SESSION['tool_table'])) {
    header('Location:' . asset('views/steps/auto/step2.php')); exit;
}
$toolId   = (int)$_SESSION['tool_id'];
$toolTbl  = preg_replace('/[^a-z0-9_]/i','',$_SESSION['tool_table']);

//
// [H] Cargar categorías “Madera” compatibles
//
$compatTbl = 'toolsmaterial_'.str_replace('tools_','',$toolTbl);
$sql = "
  SELECT m.material_id, m.name, c.category_id, c.name AS cat
    FROM {$compatTbl} tm
    JOIN materials            m ON m.material_id = tm.material_id
    JOIN materialcategories   c ON c.category_id = m.category_id
   WHERE tm.tool_id = :tid AND c.name LIKE 'Madera%'
   ORDER BY c.name, m.name";
$stmt=$pdo->prepare($sql);
$stmt->execute([':tid'=>$toolId]);
$mats=$stmt->fetchAll(PDO::FETCH_ASSOC);

/* Agrupar como en step1 */
$parents=[]; $children=[];
foreach ($mats as $m){
    $cid=(int)$m['category_id'];
    $parents[$cid]          = $m['cat'];                   // parent = categoría madera
    $children[$cid][] = [
        'id'  => (int)$m['material_id'],
        'cid' => $cid,
        'name'=> $m['name']
    ];
}
dbg('parents',$parents); dbg('children',$children);

//
// [I] Procesar POST (idéntico a step1)
//
$err=null;
if ($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($csrf,$_POST['csrf_token']??''))               $err='Token de seguridad inválido.';
    $mat = filter_input(INPUT_POST,'material_id',FILTER_VALIDATE_INT);
    $thk = filter_input(INPUT_POST,'thickness'  ,FILTER_VALIDATE_FLOAT);
    if(!$err && ($mat===false||$mat===null||$mat<1))               $err='Material no válido.';
    if(!$err && ($thk===false||$thk===null||$thk<=0))              $err='Espesor no válido.';
    if(!$err && !array_key_exists($mat,array_column($mats,'material_id','material_id')))
        $err='Material no válido.'; // no coincide con lista

    if(!$err){
        $_SESSION['rate_limit'][$clientIp][] = time();
        session_regenerate_id(true);
        $_SESSION['material_id']=$mat;
        $_SESSION['thickness'] =(float)$thk;
        $_SESSION['wizard_progress']=4;
        header('Location:' . asset('views/steps/manual/step5.php')); exit;
    }
}
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8">
<title>Paso 4 – Material</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('assets/css/main.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/material.css') ?>">
<script>window.BASE_URL = '<?= BASE_URL ?>';</script>
</head><body>
<main class="container py-4">
<h2 class="step-title"><i data-feather="layers"></i> Material y espesor</h2>
<p class="step-desc">Indicá el material a procesar y su espesor.</p>

<?php if($err):?>
  <div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
<?php endif;?>

<form id="formMat" method="post" novalidate>
  <input type="hidden" name="step" value="4">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
  <input type="hidden" name="material_id" id="material_id" value="">

  <!-- 1) Buscador -->
  <div class="mb-3 position-relative">
    <label for="matSearch" class="form-label">Buscar material (2+ letras)</label>
    <input id="matSearch" class="form-control" autocomplete="off" placeholder="Ej.: MDF…">
    <div id="no-match-msg">Material no encontrado</div>
    <div id="searchDropdown" class="dropdown-search"></div>
  </div>

  <!-- 2) Categorías -->
  <h5>Categoría</h5>
  <div id="catRow" class="d-flex flex-wrap mb-3">
    <?php foreach($parents as $pid=>$pname):?>
      <button type="button" class="btn btn-outline-primary btn-cat" data-pid="<?=$pid?>">
        <?=htmlspecialchars($pname)?>
      </button>
    <?php endforeach;?>
  </div>

  <!-- 3) Materiales -->
  <div id="matBox" class="mb-3" style="display:none">
    <h5>Material</h5><div id="matCol"></div>
  </div>

  <!-- 4) Espesor -->
  <div id="thickGroup" class="mb-3" style="display:none">
    <label for="thick" class="form-label">Espesor (mm)</label>
    <input type="number" id="thick" name="thickness" class="form-control" step="0.1" min="0.1" required>
  </div>

  <!-- 5) Botón “Siguiente” -->
  <div id="next-button-container" class="text-end mt-4" style="display:none">
    <button id="btn-next" class="btn btn-primary btn-lg">
      Siguiente <i data-feather="arrow-right" class="ms-1"></i>
    </button>
  </div>
</form>

<pre id="debug" class="bg-dark text-info p-2 mt-4"></pre>
</main>

<script>
function normalizeText(s){return s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();}

const parents  = <?=json_encode($parents,JSON_UNESCAPED_UNICODE)?>;
const children = <?=json_encode($children,JSON_UNESCAPED_UNICODE)?>;
const matsFlat = <?=json_encode($mats,    JSON_UNESCAPED_UNICODE)?>;

const matBox=document.getElementById('matBox');
const matCol=document.getElementById('matCol');
const matInp=document.getElementById('material_id');
const thick=document.getElementById('thick');
const thickGrp=document.getElementById('thickGroup');
const nextCont=document.getElementById('next-button-container');
const search=document.getElementById('matSearch');
const noMatch=document.getElementById('no-match-msg');
const dropdown=document.getElementById('searchDropdown');

const matToPid={};
Object.entries(children).forEach(([pid,list])=>list.forEach(m=>matToPid[m.id]=pid));

function resetMat(){
  matCol.innerHTML='';matBox.style.display='none';
  matInp.value='';thick.value='';thickGrp.style.display='none';
  nextCont.style.display='none';search.classList.remove('is-invalid');noMatch.style.display='none';
}
function validate(){ nextCont.style.display=(matInp.value && parseFloat(thick.value)>0)?'block':'none';}
function noMatchMsg(st){search.classList.toggle('is-invalid',st);noMatch.style.display=st?'block':'none';}
function hideDD(){dropdown.style.display='none';dropdown.innerHTML='';}
function showDropdown(list){
  dropdown.innerHTML='';list.forEach(m=>{
    const term=normalizeText(search.value.trim());
    const raw=m.name, idx=normalizeText(raw).indexOf(term);
    const item=document.createElement('div');
    item.className='item';
    item.innerHTML=idx==-1?raw:
      raw.slice(0,idx)+'<span class="hl">'+raw.slice(idx,idx+term.length)+'</span>'+raw.slice(idx+term.length);
    item.onclick=()=>{
      document.querySelector(`.btn-cat[data-pid='${matToPid[m.material_id]}']`)?.click();
      setTimeout(()=>document.querySelector(`.btn-mat[data-mid='${m.material_id}']`)?.click(),0);
      hideDD();
    };
    dropdown.appendChild(item);
  });
  dropdown.style.display='block';
}

/* Categorías */
document.querySelectorAll('.btn-cat').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.querySelectorAll('.btn-cat').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active'); resetMat();
    const pid=btn.dataset.pid;
    (children[pid]||[]).forEach(m=>{
      const b=document.createElement('button');
      b.type='button';b.className='btn btn-outline-secondary btn-mat';
      b.textContent=m.name;b.dataset.mid=m.id;
      b.addEventListener('click',()=>{
        document.querySelectorAll('.btn-mat').forEach(x=>x.classList.remove('active'));
        b.classList.add('active');
        matInp.value=m.id;search.value=m.name;noMatchMsg(false);
        thickGrp.style.display='block';validate();hideDD();
      });
      matCol.appendChild(b);
    });
    matBox.style.display='block';
  });
});

/* Buscador */
search.addEventListener('input',e=>{
  const v=e.target.value.trim();
  if(v.length<2){noMatchMsg(false);hideDD();return;}
  const list=matsFlat.filter(m=>normalizeText(m.name).includes(normalizeText(v)));
  if(!list.length){resetMat();noMatchMsg(true);return;}
  noMatchMsg(false);showDropdown(list);
});
search.addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault();}});
search.addEventListener('blur',()=>setTimeout(hideDD,100));

/* Espesor */
thick.addEventListener('input',validate);

/* Submit */
document.getElementById('formMat').addEventListener('submit',e=>{
  if(!matInp.value || parseFloat(thick.value)<=0){
    e.preventDefault();
    alert('Debés elegir un material válido y un espesor mayor a 0 antes de continuar.');
  }
});
</script>
</body></html>
