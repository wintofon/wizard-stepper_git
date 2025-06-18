<?php
declare(strict_types=1);
/**
 * File: views/steps/manual/step4.php
 *
 * Paso 4 (Manual) – Selección de madera compatible
 * ------------------------------------------------
 * • El mensaje de espesor cambió a “El espesor no puede ser 0”.
 * • Al mostrar el campo espesor NO aparece alerta; sólo se muestra si
 *   el usuario ingresa 0 o un valor negativo.
 * • pageshow re-sincroniza al volver con el historial (bfcache), así
 *   no hace falta recargar.
 */

/* ─────────────────── A) Cabeceras ─────────────────────────── */
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

/* ─────────────────── B) Sesión segura ─────────────────────── */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime'=>0,
        'path'    =>'/wizard-stepper_git/',
        'secure'  =>true,
        'httponly'=>true,
        'samesite'=>'Strict'
    ]);
    session_start();
}

/* ─────────────────── C) Flujo correcto ────────────────────── */
if (empty($_SESSION['wizard_progress']) || (int)$_SESSION['wizard_progress'] < 3) {
    header('Location:/wizard-stepper_git/views/steps/auto/step1.php'); exit;
}

/* ─────────────────── D) Dependencias ──────────────────────── */
require_once __DIR__.'/../../../includes/db.php';
require_once __DIR__.'/../../../includes/debug.php';

/* CSRF */
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

/* Herramienta previa */
if (empty($_SESSION['tool_id']) || empty($_SESSION['tool_table'])) {
    header('Location:/wizard-stepper_git/views/steps/auto/step2.php'); exit;
}
$toolId   = (int)$_SESSION['tool_id'];
$toolTbl  = preg_replace('/[^a-z0-9_]/i','',$_SESSION['tool_table']);

/* ─────────────────── E) Maderas compatibles ───────────────── */
$compat = 'toolsmaterial_'.str_replace('tools_','',$toolTbl);
$stmt   = $pdo->prepare("
  SELECT m.material_id, m.name mat, c.category_id, c.name cat
    FROM {$compat} tm
    JOIN materials            m ON m.material_id = tm.material_id
    JOIN materialcategories   c ON c.category_id = m.category_id
   WHERE tm.tool_id = :tid AND c.name LIKE 'Madera%'
   ORDER BY c.name, m.name");
$stmt->execute([':tid'=>$toolId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Agrupar para UI */
$cats=[]; $flat=[];
foreach ($rows as $r) {
    $cid=(int)$r['category_id'];
    $cats[$cid]['name']=$r['cat'];
    $cats[$cid]['mats'][]=['id'=>(int)$r['material_id'],'name'=>$r['mat']];
    $flat[]=['id'=>(int)$r['material_id'],'cid'=>$cid,'name'=>$r['mat']];
}

/* ─────────────────── F) POST ─────────────────────────────── */
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($csrf, $_POST['csrf_token']??''))                $errors[]='Token inválido.';
    if ((int)($_POST['step']??0)!==4)                                 $errors[]='Paso inválido.';
    $mat = filter_input(INPUT_POST,'material_id',FILTER_VALIDATE_INT);
    $thk = filter_input(INPUT_POST,'thickness'  ,FILTER_VALIDATE_FLOAT);
    if(!$mat || $mat<=0)                                              $errors[]='Seleccioná una madera válida.';
    if(!$thk || $thk<=0)                                              $errors[]='El espesor no puede ser 0.';
    if(!$errors && !in_array($mat,array_column($flat,'id'),true))     $errors[]='Madera no compatible.';
    if(!$errors){
        $_SESSION += ['material_id'=>$mat,'thickness'=>$thk,'wizard_progress'=>4];
        header('Location:/wizard-stepper_git/views/steps/manual/step5.php'); exit;
    }
}

/* ─────────────────── G) Previos ───────────────────────────── */
$prevMat = $_SESSION['material_id']??'';
$prevThk = $_SESSION['thickness']??'';
$imgUrl  = $_SESSION['tool_image_url']??'';
$hasPrev = $prevMat && $prevThk>0;
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8">
<title>Paso 4 – Madera compatible</title>
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
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
<?php endif; ?>

<form id="formWood" method="post" novalidate>
  <input type="hidden" name="step" value="4">
  <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
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
  <div id="thickGroup" class="mb-3" style="<?= $hasPrev ? '' : 'display:none' ?>">
    <label class="form-label" for="thick">Espesor (mm)</label>
    <input type="number" step="0.1" min="0.1" id="thick" name="thickness"
           class="form-control"
           placeholder="Ingresá el espesor (mm)"
           value="<?= $hasPrev ? htmlspecialchars((string)$prevThk) : '' ?>">
    <div class="invalid-feedback">El espesor no puede ser 0.</div>
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
const thick =document.getElementById('thick');
const thickGrp=document.getElementById('thickGroup');
const nextBox=document.getElementById('nextBox');
const search =document.getElementById('matSearch');
const noMatch=document.getElementById('noMatchMsg');
const ddwn   =document.getElementById('searchDropdown');
const matBox =document.getElementById('matBox');
const matCol =document.getElementById('matCol');
const emptyMsg=document.getElementById('emptyMsg');

/* Validación espesor: sin alerta al aparecer, sí si vale 0/negativo */
function validate(){
  if(thickGrp.style.display==='none'){ nextBox.style.display='none'; return; }
  const val=thick.value.trim();
  const num=parseFloat(val);
  const okMat=!!matInp.value;
  const okThk=val!=='' && num>0;
  const showInv=val!=='' && (!num || num<=0);
  thick.classList.toggle('is-invalid',showInv);
  thick.classList.toggle('is-valid',okThk);
  nextBox.style.display= okMat && okThk ? 'block':'none';
}

/* Sin advertencias “sin coincidencias” hasta que vea espesor */
function showNoMatch(state){
  if(thickGrp.style.display==='none') return;
  search.classList.toggle('is-invalid',state);
  noMatch.style.display=state?'block':'none';
}
function hideDD
