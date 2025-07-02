<?php
// admin/tools_facets.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

// Tablas por marca
$brandTables = ['tools_sgs','tools_maykestag','tools_schneider','tools_generico'];

// Arrays auxiliares
$series       = [];
$diameter     = [];
$shank        = [];
$fluteLen     = [];
$fullLen      = [];
$cutLen       = [];
$radius       = [];
$conical      = [];
$coated       = [];
$fluteCount   = [];
$toolType     = [];
$material     = [];
$madeIn       = [];
$materialId   = [];
$strategyId   = [];

// 1) Recorro cada tabla principal
foreach ($brandTables as $tbl) {
    $rows = $pdo
      ->query("SELECT DISTINCT
                 series_id,
                 diameter_mm,
                 shank_diameter_mm,
                 flute_length_mm,
                 full_length_mm,
                 cut_length_mm,
                 radius,
                 conical_angle,
                 coated,
                 flute_count,
                 tool_type,
                 material,
                 made_in
               FROM $tbl")
      ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $series[]     = $r['series_id'];
        $diameter[]   = $r['diameter_mm'];
        $shank[]      = $r['shank_diameter_mm'];
        $fluteLen[]   = $r['flute_length_mm'];
        $fullLen[]    = $r['full_length_mm'];
        $cutLen[]     = $r['cut_length_mm'];
        $radius[]     = $r['radius'];
        $conical[]    = $r['conical_angle'];
        $coated[]     = $r['coated'];
        $fluteCount[] = $r['flute_count'];
        $toolType[]   = $r['tool_type'];
        $material[]   = $r['material'];
        $madeIn[]     = $r['made_in'];
    }

    // material_id de la tabla toolsmaterial_xxx
    $mt = 'toolsmaterial_'.substr($tbl, 6);
    $mids = $pdo
      ->query("SELECT DISTINCT material_id FROM $mt")
      ->fetchAll(PDO::FETCH_COLUMN);
    $materialId = array_merge($materialId, $mids);
}

// 2) Estrategias (global, no por marca)
$strategyId = $pdo
  ->query("SELECT DISTINCT strategy_id FROM toolstrategy")
  ->fetchAll(PDO::FETCH_COLUMN);

// Helper para unificar y ordenar
function uniqSort(array $a) {
  $u = array_unique($a, SORT_REGULAR);
  sort($u, SORT_NATURAL|SORT_FLAG_CASE);
  return $u;
}

// 3) Componer JSON
$output = [
  'brand'              => ['SGS','MAYKESTAG','SCHNEIDER','GENERICO'],
  'series_id'          => uniqSort($series),
  'diameter_mm'        => uniqSort($diameter),
  'shank_diameter_mm'  => uniqSort($shank),
  'flute_length_mm'    => uniqSort($fluteLen),
  'full_length_mm'     => uniqSort($fullLen),
  'cut_length_mm'      => uniqSort($cutLen),
  'radius'             => uniqSort($radius),
  'conical_angle'      => uniqSort($conical),
  'coated'             => uniqSort($coated),
  'flute_count'        => uniqSort($fluteCount),
  'tool_type'          => uniqSort($toolType),
  'material'           => uniqSort($material),
  'material_id'        => uniqSort($materialId),
  'strategy_id'        => uniqSort($strategyId),
  'made_in'            => uniqSort($madeIn),
];

// 4) Devolver
echo json_encode($output, JSON_UNESCAPED_UNICODE);
