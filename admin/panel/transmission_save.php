<?php
/* =========================================================================
   Guarda una herramienta nueva o actualiza una existente
   - Sube imágenes (comercial y de cotas) a /uploads
   - Actualiza estrategias y parámetros de corte por material
   - Compatible con PHP 7.4
   ========================================================================= */
require_once '../includes/db.php';   // ← brandTable() ya está definido aquí
require_once '../includes/auth.php';

/* ── solo procesar peticiones POST ───────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php'); exit;
}

/* ── identificar tabla según marca ───────────────────── */
$toolId  = $_POST['tool_id'] ?? null;
$brandId = (int)($_POST['brand_id'] ?? 0);
$tbl     = $_POST['tbl'] ?? brandTable($brandId);      // brandTable viene de includes/db.php
$mtbl    = 'toolsmaterial_' . substr($tbl, 6);         // toolsmaterial_sgs …

/* ── manejar subida de imágenes (opcional) ───────────── */
$image     = $_POST['image'] ?? null;          // ruta existente (si edito)
$imageDim  = $_POST['image_dimensions'] ?? null;

foreach (['image','image_dimensions'] as $field) {
    if (!empty($_FILES[$field]['name'])) {
        $fname = basename($_FILES[$field]['name']);
        $dest  = '../uploads/' . $fname;
        if (!is_dir('../uploads')) mkdir('../uploads', 0775, true);
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
            if ($field === 'image')           $image    = 'uploads/' . $fname;
            if ($field === 'image_dimensions')$imageDim = 'uploads/' . $fname;
        }
    }
}

/* ── columnas principales de la herramienta ─────────── */
$cols = [
  'series_id','tool_code','name','flute_count','diameter_mm',
  'shank_diameter_mm','flute_length_mm','cut_length_mm','full_length_mm',
  'rack_angle','helix','conical_angle','radius',
  'tool_type','made_in','material','coated',
  'notes','image','image_dimensions'
];

$vals = [
  $_POST['series_id']          ?? null,
  trim($_POST['tool_code']     ?? ''),
  trim($_POST['name']          ?? ''),
  $_POST['flute_count']        ?? null,
  $_POST['diameter_mm']        ?? null,
  $_POST['shank_diameter_mm']  ?? null,
  $_POST['flute_length_mm']    ?? null,
  $_POST['cut_length_mm']      ?? null,
  $_POST['full_length_mm']     ?? null,
  $_POST['rack_angle']         ?? null,
  $_POST['helix']              ?? null,
  $_POST['conical_angle']      ?? null,
  $_POST['radius']             ?? null,
  $_POST['tool_type']          ?? null,
  $_POST['made_in']            ?? null,
  $_POST['material']           ?? null,
  $_POST['coated']             ?? null,
  $_POST['notes']              ?? null,
  $image,
  $imageDim
];

/* ── INSERT o UPDATE de la herramienta ───────────────── */
if ($toolId) {                     // UPDATE
    $set = implode(', ', array_map(fn($c)=>"$c = ?", $cols));
    $pdo->prepare("UPDATE $tbl SET $set WHERE tool_id=?")
        ->execute([...$vals, $toolId]);
} else {                           // INSERT
    $ph = implode(',', array_fill(0, count($cols), '?'));
    $pdo->prepare("INSERT INTO $tbl(" . implode(',', $cols) . ") VALUES($ph)")
        ->execute($vals);
    $toolId = $pdo->lastInsertId();
}

/* ── actualizar estrategias ─────────────────────────── */
$pdo->prepare("DELETE FROM toolstrategy WHERE tool_table=? AND tool_id=?")
    ->execute([$tbl, $toolId]);

if (!empty($_POST['strategies'])) {
    $ins = $pdo->prepare("INSERT INTO toolstrategy(tool_table,tool_id,strategy_id) VALUES (?,?,?)");
    foreach ($_POST['strategies'] as $sid) $ins->execute([$tbl, $toolId, $sid]);
}

/* ── actualizar parámetros por material ─────────────── */
$pdo->prepare("DELETE FROM $mtbl WHERE tool_id=?")->execute([$toolId]);

if (!empty($_POST['materials'])) {
    $ins = $pdo->prepare("
      INSERT INTO $mtbl (tool_id,material_id,vc_m_min,fz_min_mm,fz_max_mm,ap_slot_mm,ae_slot_mm)
      VALUES (?,?,?,?,?,?,?)
    ");
    foreach ($_POST['materials'] as $m) {
        if (empty($m['material_id'])) continue;           // ignora filas vacías
        $ins->execute([
            $toolId,
            $m['material_id'],
            $m['vc_m_min']   ?? null,
            $m['fz_min_mm']  ?? null,
            $m['fz_max_mm']  ?? null,
            $m['ap_slot_mm'] ?? null,
            $m['ae_slot_mm'] ?? null
        ]);
    }
}

/* ── listo ───────────────────────────────────────────── */
header('Location: dashboard.php');
exit;
