<?php
/**
 * File: step4.php – Paso 4 (Manual)
 * Cambios mínimos: solo se añaden clases .mat-label a los encabezados
 * y se incluye un pequeño <style> para que tomen el color celeste corporativo.
 */

declare(strict_types=1);

if (!function_exists('respondError')) {
    function respondError(int $code, string $msg): void {
        http_response_code($code);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
        } else {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        exit;
    }
}

require_once __DIR__ . '/../../../src/Utils/Session.php';
require_once __DIR__ . '/../../../includes/wizard_helpers.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/debug.php';

sendSecurityHeaders('text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;");

$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors', '1'); }
else        { error_reporting(0);    ini_set('display_errors', '0'); }

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime'=>0,
        'path'    => BASE_URL . '/',
        'secure'  =>true,
        'httponly'=>true,
        'samesite'=>'Strict'
    ]);
    session_start();
}

if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state']!=='wizard') {
    header('Location:' . asset('wizard.php')); exit;
}
if ((int)($_SESSION['wizard_progress']??0) < 3) {
    header('Location:' . asset('views/steps/auto/step' . (int)$_SESSION['wizard_progress'] . '.php')); exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'na';
$_SESSION['rate_limit'][$ip] = array_filter($_SESSION['rate_limit'][$ip] ?? [], fn($t)=>($t+300)>time());
if ($_SERVER['REQUEST_METHOD']==='POST' && count($_SESSION['rate_limit'][$ip])>=10){
    respondError(200,'429 – Demasiados intentos.');
}

$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

if (empty($_SESSION['tool_id']) || empty($_SESSION['tool_table'])) {
    header('Location:' . asset('views/steps/auto/step2.php')); exit;
}
$toolId  = (int)$_SESSION['tool_id'];
$toolTbl = preg_replace('/[^a-z0-9_]/i','',$_SESSION['tool_table']);

$compatTbl='toolsmaterial_'.str_replace('tools_','',$toolTbl);
$sql="SELECT m.material_id,m.name,c.category_id,c.name AS cat
      FROM {$compatTbl} tm
      JOIN materials m ON m.material_id=tm.material_id
      JOIN materialcategories c ON c.category_id=m.category_id
      WHERE tm.tool_id=:tid AND c.name LIKE 'Madera%'
      ORDER BY c.name,m.name";
$stmt=$pdo->prepare($sql);
$stmt->execute([':tid'=>$toolId]);
$mats=$stmt->fetchAll(PDO::FETCH_ASSOC);
$parents=$children=[];
foreach($mats as $m){
  $cid=(int)$m['category_id'];
  $parents[$cid]=$m['cat'];
  $children[$cid][]=['id'=>(int)$m['material_id'],'cid'=>$cid,'name'=>$m['name']];
}

$err=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($csrf,$_POST['csrf_token']??'')) $err='Token inválido.';
    $mat=filter_input(INPUT_POST,'material_id',FILTER_VALIDATE_INT);
    $thk=filter_input(INPUT_POST,'thickness',  FILTER_VALIDATE_FLOAT);
    if(!$err && (!$mat||$mat<1))  $err='Material no válido.';
    if(!$err && (!$thk||$thk<=0)) $err='Espesor no válido.';
    if(!$err && !array_key_exists($mat,array_column($mats,'material_id','material_id')))
        $err='Material no válido.';
    if(!$err){
        $_SESSION['rate_limit'][$ip][] = time();
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
<?php
$styles=[
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
  'assets/css/generic/material.css',
  'assets/css/objects/step-common.css'
];
include __DIR__.'/../../partials/styles.php';
?>
<style>
.mat-label{color:var(--step6-accent,#7fdcff);font-weight:600;font-size:1.25rem;margin-bottom:.35rem}
.mat-label small{font-size:.9rem}
</style>
</head><body>
<main class="container py-4">
<h2 class="step-title"><i data-feather="layers"></i> Material y espesor</h2>
<p class="step-desc">Indicá el material a procesar y su espesor.</p>
<?php if($err):?><div class="alert alert-danger"><?=htmlspecialchars($err)?></div><?php endif;?>
<form id="formMat" method="post" novalidate>
<input type="hidden" name="step" value="4">
<input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
<input type="hidden" name="material_id" id="material_id" value="">

<!-- Buscador -->
<div class="mb-3 position-relative">
<label for="matSearch" class="form-label mat-label">Buscar material <small>(2+ letras)</small></label>
<input id="matSearch" class="form-control" autocomplete="off" placeholder="Ej.: MDF…">
<div id="no-match-msg">Material no encontrado</div>
<div id="searchDropdown" class="dropdown-search"></div>
</div>

<p class="mat-label">— o elegí por categoría —</p>

<!-- Categorías -->
<h5 class="mat-label">Categoría</h5>
<div id="catRow" class="d-flex flex-wrap mb-3">
<?php foreach($parents as $pid=>$name):?>
<button type="button" class="btn btn-outline-primary btn-cat" data-pid="<?=$pid?>"><?=htmlspecialchars($name)?></button>
<?php endforeach;?>
</div>

<!-- Materiales -->
<div id="matBox" class="mb-3" style="display:none">
<h5 class="mat-label">Material</h5><div id="matCol"></div>
</div>

<!-- Espesor -->
<div id="thickGroup" class="mb-3" style="display:none">
<label for="thick" class="form-label mat-label">Espesor</label>
<div class="input-group">
<input type="number" id="thick" name="thickness" class="form-control" step="0.1" min="0.1" required>
<span class="input-group-text">mm</span>
</div>
</div>

<!-- Siguiente -->
<div id="next-button-container" class="text-start mt-4" style="display:none">
<button id="btn-next" class="btn btn-primary btn-lg">Siguiente <i data-feather="arrow-right" class="ms-1"></i></button>
</div>
</form>
</main>
<script>
function normalizeText(s){return s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();}
const parent
