<?php
/**
 * File: step2.php
 *
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * @TODO Extend documentation.
 */
/**
 * Paso 2 – Confirmar herramienta seleccionada
 * - Acepta POST (del paso 1), GET (?brand&code) o los datos
 *   persistidos en $_SESSION (fallback).
 * - Agrega campo oculto step=2 para que el Stepper no marque
 *   “Paso inválido”.
 * - Funciones dbg() registran información en el log de errores.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/db.php';

/* ---------- util debug ---------------------------------------------------- */
if (!function_exists('dbg')) {
    function dbg(string $tag, $data = null): void {
        $txt = '['.date('H:i:s')."] {$tag} "
             . (is_scalar($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE));
        error_log($txt);
    }
}

/* ---------- sesión -------------------------------------------------------- */
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
    dbg('SESSION','iniciada por step2');
}

/* ---------- tablas válidas ------------------------------------------------ */
$brandTables = [
  'SGS'       => 'tools_sgs',
  'MAYKESTAG' => 'tools_maykestag',
  'SCHNEIDER' => 'tools_schneider',
  'GENERICO'  => 'tools_generico',
];

/* ---------- helpers ------------------------------------------------------- */
function tblClean(string $raw, array $allowed): ?string {
    $raw = preg_replace('/[^a-z0-9_]/i', '', $raw);
    return in_array($raw, $allowed, true) ? $raw : null;
}
function fetchTool(PDO $pdo, string $tbl, string $where, $val): ?array {
    $sql = "SELECT t.*, s.code AS serie, b.name AS brand
              FROM {$tbl} t
              JOIN series  s ON t.series_id = s.id
              JOIN brands  b ON s.brand_id  = b.id
             WHERE {$where}";
    $st  = $pdo->prepare($sql);
    $st->execute([$val]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ---------- lógica -------------------------------------------------------- */
$error = null;  $tool = null;

/* 1) viene de paso 1 */
if ($_SERVER['REQUEST_METHOD']==='POST'
    && isset($_POST['tool_id'], $_POST['tool_table'])) {

    dbg('POST-IN', $_POST);

    $toolId = (int)$_POST['tool_id'];
    $tbl    = tblClean($_POST['tool_table'], $brandTables);

    $tool   = $tbl ? fetchTool($pdo, $tbl, 't.tool_id = ?', $toolId) : null;

    if (!$tbl)          $error = 'Tabla de herramientas inválida.';
    elseif (!$tool)     $error = "No se encontró la herramienta #$toolId.";
    else {
        $_SESSION['tool_id']    = $toolId;
        $_SESSION['tool_table'] = $tbl;
    }
}
/* 2) acceso externo */
elseif (isset($_GET['brand'], $_GET['code'])) {

    dbg('GET-IN', $_GET);

    $tbl  = $brandTables[strtoupper(trim($_GET['brand']))] ?? null;
    $code = trim($_GET['code']);
    $tool = $tbl ? fetchTool($pdo, $tbl, 't.tool_code = ?', $code) : null;

    if (!$tbl)          $error = 'Marca inválida.';
    elseif (!$tool)     $error = "No se encontró la fresa $code.";
    else {
        $_SESSION['tool_id']    = (int)$tool['tool_id'];
        $_SESSION['tool_table'] = $tbl;
    }
}
/* 3) datos ya guardados */
elseif (!empty($_SESSION['tool_id']) && !empty($_SESSION['tool_table'])) {

    dbg('SESSION-IN', $_SESSION);

    $tbl  = tblClean($_SESSION['tool_table'], $brandTables);
    $tool = $tbl ? fetchTool($pdo, $tbl, 't.tool_id = ?', (int)$_SESSION['tool_id']) : null;

    if (!$tool) {
        $error = 'La herramienta guardada ya no existe.';
        unset($_SESSION['tool_id'], $_SESSION['tool_table']);
    }
}
/* 4) nada disponible */
else $error = 'Faltan parámetros (tool_id + table o brand + code).';

dbg('STEP-2', $error ? ['ERR'=>$error] : ['OK'=>$tool['tool_id']??'-']);

/* ---------- fallback longitud total -------------------------------------- */
if ($tool) {
    $tool['length_total_mm'] ??= $tool['full_length_mm'] ?? 0;
    if (!empty($_SESSION['tool_image_url'])) {
        $tool['image_url'] = rtrim((string)$_SESSION['tool_image_url'], '/');
    } elseif (!empty($tool['image'])) {
        $tool['image_url'] = asset((string)$tool['image']);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Paso 2 – Confirmar herramienta</title>
  <?php
    $styles = [
      'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
      'assets/css/components/_step2.css',
      'assets/css/objects/step-common.css',
    ];
    $embedded = defined('WIZARD_EMBEDDED') && WIZARD_EMBEDDED;
    include __DIR__ . '/../../partials/styles.php';
  ?>
  <?php if (!$embedded): ?>
  <script>
    window.BASE_URL = <?= json_encode(BASE_URL) ?>;
    window.BASE_HOST = <?= json_encode(BASE_HOST) ?>;
  </script>
  <?php endif; ?>
</head>
<body>
<div class="container py-4">
  <h2 class="step-title"><i data-feather="check-circle"></i> Confirmar herramienta</h2>
  <p class="step-desc">Revisá los datos de la fresa elegida.</p>

  <?php if ($error): ?>
      <div class="alert alert-danger mt-3">
        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
      </div>
      <!-- Botón eliminado por nuevo flujo sin regreso -->

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
          <div>
            <h4><?= htmlspecialchars($tool['tool_code']) ?> – <?= htmlspecialchars($tool['name']) ?></h4>
            <p class="mb-1"><strong>Marca:</strong> <?= htmlspecialchars($tool['brand']) ?>
               &nbsp;|&nbsp; <strong>Serie:</strong> <?= htmlspecialchars($tool['serie']) ?></p>
            <p class="mb-1"><strong>Ø:</strong> <?= (float)$tool['diameter_mm'] ?> mm
               &nbsp;|&nbsp; <strong>Filos:</strong> <?= (int)$tool['flute_count'] ?></p>
            <p class="mb-1"><strong>Tipo:</strong> <?= htmlspecialchars($tool['tool_type']) ?></p>
            <p class="mb-0"><strong>Long. corte:</strong> <?= (float)$tool['cut_length_mm'] ?> mm
               &nbsp;|&nbsp; <strong>Total:</strong> <?= (float)$tool['length_total_mm'] ?> mm</p>
          </div>
        </div>
      </div>

      <!-- ********  ¡campo step=2 añadido!  ******** -->
        <form action="step4_select_strategy.php"
              method="post"
              class="mt-4">
        <input type="hidden" name="step"       value="2">
        <input type="hidden" name="tool_id"    value="<?= $tool['tool_id'] ?>">
        <input type="hidden" name="tool_table" value="<?= htmlspecialchars($_SESSION['tool_table']) ?>">
          <div class="text-start mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
              Siguiente <i data-feather="arrow-right" class="ms-1"></i>
            </button>
          </div>
      </form>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
