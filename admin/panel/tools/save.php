<?php
// admin/tool_save.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

/* ---------- helpers ---------- */
/* Evita redeclaración si ya existe en otro archivo */
if (!function_exists('brandTable')) {
    function brandTable(int $id): string {
        return match($id) {
            1 => 'tools_sgs',
            2 => 'tools_maykestag',
            3 => 'tools_schneider',
            default => 'tools_generico',
        };
    }
}

/* ---------- sanity ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$toolId  = $_POST['tool_id'] ?? null;
$brandId = (int) ($_POST['brand_id'] ?? 0);
$tbl     = $_POST['tbl'] ?? brandTable($brandId);
$mtbl    = 'toolsmaterial_' . substr($tbl, 6);

/* ---------- subir imágenes ---------- */
function handleUpload(string $field): ?string {
    if (empty($_FILES[$field]['name'])) {
        // Si ya venía en el POST (para edición), lo devolvemos
        return $_POST[$field] ?? null;
    }
    $uploadDir = __DIR__ . '/../assets/img/tools/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $filename = basename($_FILES[$field]['name']);
    $dest     = $uploadDir . $filename;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        // Guardamos ruta relativa en BD
        return 'assets/img/tools/' . $filename;
    }
    return null;
}

$image     = handleUpload('image');
$imageDim  = handleUpload('image_dimensions');

/* ---------- columnas comunes ---------- */
$cols = [
    'series_id','tool_code','name','flute_count','diameter_mm',
    'shank_diameter_mm','flute_length_mm','cut_length_mm','full_length_mm',
    'rack_angle','helix','conical_angle','radius',
    'tool_type','made_in','material','coated',
    'notes','image','image_dimensions'
];

$vals = [
    $_POST['series_id']           ?? null,
    trim($_POST['tool_code']      ?? ''),
    trim($_POST['name']           ?? ''),
    $_POST['flute_count']         ?? null,
    $_POST['diameter_mm']         ?? null,
    $_POST['shank_diameter_mm']   ?? null,
    $_POST['flute_length_mm']     ?? null,
    $_POST['cut_length_mm']       ?? null,
    $_POST['full_length_mm']      ?? null,
    $_POST['rack_angle']          ?? null,
    $_POST['helix']               ?? null,
    $_POST['conical_angle']       ?? null,
    $_POST['radius']              ?? null,
    $_POST['tool_type']           ?? null,
    $_POST['made_in']             ?? null,
    $_POST['material']            ?? null,
    $_POST['coated']              ?? null,
    $_POST['notes']               ?? null,
    $image,
    $imageDim
];

/* ---------- insert / update principal ---------- */
if ($toolId) {
    $set = implode(', ', array_map(fn($c) => "$c = ?", $cols));
    $pdo->prepare("UPDATE {$tbl} SET {$set} WHERE tool_id = ?")
        ->execute([...$vals, $toolId]);
} else {
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $pdo->prepare("INSERT INTO {$tbl}(" . implode(',', $cols) . ") VALUES({$placeholders})")
        ->execute($vals);
    $toolId = $pdo->lastInsertId();
}

/* ---------- sincronizar estrategias ---------- */
$pdo->prepare("DELETE FROM toolstrategy WHERE tool_table = ? AND tool_id = ?")
    ->execute([$tbl, $toolId]);
if (!empty($_POST['strategies'])) {
    $insertStrat = $pdo->prepare(
        "INSERT INTO toolstrategy (tool_table, tool_id, strategy_id)
         VALUES (?, ?, ?)"
    );
    foreach ($_POST['strategies'] as $sid) {
        $insertStrat->execute([$tbl, $toolId, $sid]);
    }
}

/* ---------- parámetros de material + rating ---------- */
$pdo->prepare("DELETE FROM {$mtbl} WHERE tool_id = ?")
    ->execute([$toolId]);
if (!empty($_POST['materials'])) {
    $ins = $pdo->prepare(
        "INSERT INTO {$mtbl}
          (tool_id, material_id, rating, vc_m_min, fz_min_mm, fz_max_mm, ap_slot_mm, ae_slot_mm)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($_POST['materials'] as $m) {
        if (empty($m['material_id'])) continue;
        $ins->execute([
            $toolId,
            $m['material_id'],
            $m['rating']     ?? 0,
            $m['vc_m_min']   ?? null,
            $m['fz_min_mm']  ?? null,
            $m['fz_max_mm']  ?? null,
            $m['ap_slot_mm'] ?? null,
            $m['ae_slot_mm'] ?? null,
        ]);
    }
}

header('Location: dashboard.php');
exit;
