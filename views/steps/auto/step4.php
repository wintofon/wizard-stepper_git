<?php
declare(strict_types=1);
/**
 * File: C:\xampp\htdocs\wizard-stepper_git\views\steps\auto\step4.php
 *
 * Paso 4 (Auto) – Confirmar herramienta seleccionada
 * • POST desde step3.php o GET con brand+code
 * • Validación de CSRF y flujo (wizard_progress ≥ 3)
 * • Guarda tool_id, tool_table en sesión y avanza a step5.php
 */

// ──────────────────────────────────────────────────────────────
// [A] Cabeceras de seguridad / anti-caching
// ──────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=UTF-8');
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Permissions-Policy: geolocation=(), microphone=()");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline';");

// ──────────────────────────────────────────────────────────────
// [B] Errores y debug
// ──────────────────────────────────────────────────────────────
$DEBUG = filter_input(INPUT_GET, 'debug', FILTER_VALIDATE_BOOLEAN);
(@$DEBUG) ? ini_set('display_errors', '1') : ini_set('display_errors', '0');
error_reporting($DEBUG ? E_ALL : 0);
require_once __DIR__ . '/../../../includes/wizard_helpers.php';
if ($DEBUG && function_exists('dbg')) dbg('🔧 step4.php iniciado');

// ──────────────────────────────────────────────────────────────
// [C] Sesión segura
// ──────────────────────────────────────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/wizard-stepper_git/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
    dbg('🔒 Sesión iniciada');
}

// ──────────────────────────────────────────────────────────────
// [D] Validar flujo (wizard_progress ≥ 3)
// ──────────────────────────────────────────────────────────────
if (empty($_SESSION['wizard_state']) || $_SESSION['wizard_state'] !== 'wizard') {
    header('Location: /wizard-stepper_git/index.php'); exit;
}
if (($_SESSION['wizard_progress'] ?? 0) < 3) {
    header('Location: /wizard-stepper_git/views/steps/auto/step3.php'); exit;
}

// ──────────────────────────────────────────────────────────────
// [E] Dependencias
// ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../../includes/db.php';

// ──────────────────────────────────────────────────────────────
// [F] Helpers
// ──────────────────────────────────────────────────────────────
function tblClean(string $raw): ?string {
    $clean = strtolower(preg_replace('/[^a-z0-9_]/i', '', $raw));
    return in_array($clean, ['tools_sgs','tools_maykestag','tools_schneider','tools_generico'], true)
        ? $clean : null;
}
function fetchTool(PDO $pdo,string $tbl,string $by,$val):?array{
    $where = $by==='id' ? 't.tool_id = ?' : 't.tool_code = ?';
    $sql   = "SELECT t.*, s.code AS serie, b.name AS brand
                FROM {$tbl} t
                JOIN series s ON t.series_id = s.id
                JOIN brands b ON s.brand_id  = b.id
               WHERE {$where}";
    $st=$pdo->prepare($sql); $st->execute([$val]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ──────────────────────────────────────────────────────────────
// [G] Entrada POST / GET / sesión
// ──────────────────────────────────────────────────────────────
$error = null; $tool = null;

// G.1 POST desde step3
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['tool_id'],$_POST['tool_table'],$_POST['step'])) {
    if ((int)$_POST['step']!==3)           $error='Paso inválido.';
    elseif (!hash_equals($_SESSION['csrf_token']??'',$_POST['csrf_token']??'')) $error='Token CSRF inválido.';
    else {
        $id  = filter_input(INPUT_POST,'tool_id',FILTER_VALIDATE_INT);
        $tbl = tblClean($_POST['tool_table']??'');
        if(!$id)              $error='ID inválido.';
        elseif(!$tbl)         $error='Tabla inválida.';
        else                  $tool=fetchTool($pdo,$tbl,'id',$id);
        if(!$tool)            $error="No se encontró la herramienta #{$id}.";
        else{
            session_regenerate_id(true);
            $_SESSION['tool_id']=$id; $_SESSION['tool_table']=$tbl; $_SESSION['wizard_progress']=4;
        }
    }
}
// G.2 GET brand+code
elseif($_SERVER['REQUEST_METHOD']==='GET' && isset($_GET['brand'],$_GET['code'])){
    $map=['SGS'=>'tools_sgs','MAYKESTAG'=>'tools_maykestag','SCHNEIDER'=>'tools_schneider','GENERICO'=>'tools_generico'];
    $brand=strtoupper(trim($_GET['brand'])); $code=trim($_GET['code']);
    if(!isset($map[$brand]))    $error='Marca inválida.';
    else{
        $tbl=$map[$brand]; $tool=fetchTool($pdo,$tbl,'code',$code);
        if(!$tool)         $error="No se encontró la fresa {$code}.";
        else{
            session_regenerate_id(true);
            $_SESSION['tool_id']=(int)$tool['tool_id']; $_SESSION['tool_table']=$tbl; $_SESSION['wizard_progress']=4;
        }
    }
}
// G.3 sesión previa
elseif(!empty($_SESSION['tool_id']) && !empty($_SESSION['tool_table'])){
    $tbl=tblClean($_SESSION['tool_table']); $tool=$tbl?fetchTool($pdo,$tbl,'id',$_SESSION['tool_id']):null;
    if(!$tool){ $error='La herramienta guardada ya no existe.'; session_unset(); }
}
// G.4 sin datos
else $error='Faltan parámetros para confirmar la herramienta.';

// ──────────────────────────────────────────────────────────────
// [H] Normalizar datos e imagen
// ──────────────────────────────────────────────────────────────
if($tool){
    $tool['length_total_mm']??=$tool['full_length_mm']??0;
    if(!empty($tool['image'])) $tool['image_url']='/wizard-stepper_git/'.ltrim($tool['image'],'/');
}

// ──────────────────────────────────────────────────────────────
// [I] HTML
// ──────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Paso 4 – Confirmar herramienta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Reutilizamos el mismo CSS del paso manual para un look idéntico -->
  <link rel="stylesheet" href="/wizard-stepper_git/assets/css/step2_manual.css">
</head>
<body class="bg-dark text-white">

<div class="container py-4">
  <h2 class="text-info"><i class="bi bi-tools"></i> Confirmar herramienta</h2>

  <?php if ($error): ?>
      <div class="alert alert-danger mt-3">
        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
      </div>

  <?php else: ?>
      <div class="card bg-dark text-white mt-3">
        <?php if (!empty($tool['image_url'])): ?>
          <figure class="text-center p-3 mb-0">
            <img
              src="<?= htmlspecialchars($tool['image_url']) ?>"
              alt="Imagen de la herramienta seleccionada"
              class="tool-image"
              onerror="this.style.display='none'"
            >
            <figcaption class="text-muted mt-2">Fresa seleccionada</figcaption>
          </figure>
        <?php endif; ?>

        <div class="card-body">
          <h4><?= htmlspecialchars($tool['tool_code']) ?> – <?= htmlspecialchars($tool['name']) ?></h4>
          <p class="mb-1"><strong>Marca:</strong> <?= htmlspecialchars($tool['brand']) ?>
             &nbsp;|&nbsp; <strong>Serie:</strong> <?= htmlspecialchars($tool['serie']) ?></p>
          <p class="mb-1"><strong>Ø:</strong> <?= (float)$tool['diameter_mm'] ?> mm
             &nbsp;|&nbsp; <strong>Filos:</strong> <?= (int)$tool['flute_count'] ?></p>
          <p class="mb-1"><strong>Tipo:</strong> <?= htmlspecialchars($tool['tool_type'] ?? '-') ?></p>
          <p class="mb-0"><strong>Long. corte:</strong> <?= (float)$tool['cut_length_mm'] ?> mm
             &nbsp;|&nbsp; <strong>Total:</strong> <?= (float)$tool['length_total_mm'] ?> mm</p>
        </div>
      </div>

      <!-- Campo oculto step=4 para que el Stepper no marque error -->
      <form action="step5.php" method="post" class="mt-4 text-end">
        <input type="hidden" name="step"       value="4">
        <input type="hidden" name="tool_id"    value="<?= $tool['tool_id'] ?>">
        <input type="hidden" name="tool_table" value="<?= htmlspecialchars($_SESSION['tool_table']) ?>">
        <button type="submit" class="btn btn-primary btn-lg">
          Siguiente →
        </button>
      </form>
  <?php endif; ?>
</div>

<!-- Consola interna -->
<pre id="debug" class="debug-box"></pre>
</body>
</html>
