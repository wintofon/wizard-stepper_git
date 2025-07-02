<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

$brandTables = ['tools_sgs','tools_maykestag','tools_schneider','tools_generico'];

$series=$diameter=$shank=$fluteLen=$fullLen=$cutLen=$radius=$conical=$coated=[];
$fluteCount=$toolType=$material=$madeIn=[];
$materialIds=[];

foreach ($brandTables as $tbl){
    $rows=$pdo->query("SELECT DISTINCT series_id,diameter_mm,shank_diameter_mm,
              flute_length_mm,full_length_mm,cut_length_mm,radius,conical_angle,
              coated,flute_count,tool_type,material,made_in FROM $tbl")
              ->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r){
        $series[]=$r['series_id'];        $diameter[]=$r['diameter_mm'];
        $shank[]=$r['shank_diameter_mm']; $fluteLen[]=$r['flute_length_mm'];
        $fullLen[]=$r['full_length_mm'];  $cutLen[]=$r['cut_length_mm'];
        $radius[]=$r['radius'];           $conical[]=$r['conical_angle'];
        $coated[]=$r['coated'];           $fluteCount[]=$r['flute_count'];
        $toolType[]=$r['tool_type'];      $material[]=$r['material'];
        $madeIn[]=$r['made_in'];
    }
    $tm='toolsmaterial_'.substr($tbl,6);
    if($pdo->query("SHOW TABLES LIKE '$tm'")->rowCount()){
        $ids=$pdo->query("SELECT DISTINCT material_id FROM $tm")->fetchAll(PDO::FETCH_COLUMN);
        $materialIds=array_merge($materialIds,$ids);
    }
}

/* --- nombres de materiales --- */
$materialMap=[];
if($materialIds){
    $marks=rtrim(str_repeat('?,',count($materialIds)),',');
    $stmt=$pdo->prepare("SELECT material_id,name FROM materials WHERE material_id IN ($marks)");
    $stmt->execute($materialIds);
    foreach($stmt as $r) $materialMap[$r['material_id']]=$r['name'];
}

/* --- nombres de estrategias --- */
$strategyMap=[];
foreach($pdo->query("SELECT strategy_id,name FROM strategies ORDER BY name") as $r){
    $strategyMap[$r['strategy_id']]=$r['name'];
}

function uniqSort(array $a){ $u=array_unique($a,SORT_REGULAR); sort($u,SORT_NATURAL|SORT_FLAG_CASE); return $u;}

echo json_encode([
    'brand'               => ['SGS','MAYKESTAG','SCHNEIDER','GENERICO'],
    'series_id'           => uniqSort($series),
    'diameter_mm'         => uniqSort($diameter),
    'shank_diameter_mm'   => uniqSort($shank),
    'flute_length_mm'     => uniqSort($fluteLen),
    'full_length_mm'      => uniqSort($fullLen),
    'cut_length_mm'       => uniqSort($cutLen),
    'radius'              => uniqSort($radius),
    'conical_angle'       => uniqSort($conical),
    'coated'              => uniqSort($coated),
    'flute_count'         => uniqSort($fluteCount),
    'tool_type'           => uniqSort($toolType),
    'material'            => uniqSort($material),
    'material_id'         => $materialMap,     // id => nombre
    'strategy_id'         => $strategyMap,     // id => nombre
    'made_in'             => uniqSort($madeIn),
],JSON_UNESCAPED_UNICODE);
