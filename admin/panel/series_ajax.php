<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

$seriesId = (int)($_GET['series_id'] ?? 0);
if (!$seriesId) {
  echo json_encode(['tools' => [], 'params' => []]);
  exit;
}

function brandTable(int $b): string {
  return match($b) {
    1 => 'tools_sgs',
    2 => 'tools_maykestag',
    3 => 'tools_schneider',
    default => 'tools_generico',
  };
}

$serie = $pdo->prepare("SELECT brand_id FROM series WHERE id=?");
$serie->execute([$seriesId]);
if (!$serie = $serie->fetch(PDO::FETCH_ASSOC)) {
  echo json_encode(['tools' => [], 'params' => []]);
  exit;
}
$brandId = (int)$serie['brand_id'];

$toolTbl = brandTable($serie['brand_id']);
$matTbl = 'toolsmaterial_' . substr($toolTbl, 6);

// Obtener herramientas con sus estrategias
$tools = $pdo->prepare("SELECT t.tool_id, t.tool_code,
         t.diameter_mm, t.shank_diameter_mm, t.flute_length_mm, t.cut_length_mm,
         t.full_length_mm, t.conical_angle, t.flute_count, t.radius, t.coated,
         t.rack_angle, t.helix, t.material, t.made_in,
         t.image,
         GROUP_CONCAT(ts.strategy_id) AS strategy_ids
    FROM {$toolTbl} t
    LEFT JOIN toolstrategy ts
           ON ts.tool_table = ? AND ts.tool_id = t.tool_id
   WHERE t.series_id = ?
GROUP BY t.tool_id
ORDER BY t.diameter_mm");
$tools->execute([$toolTbl, $seriesId]);
$tools = $tools->fetchAll(PDO::FETCH_ASSOC);

// Obtener parámetros por material
$pm = $pdo->prepare("SELECT tm.tool_id, tm.material_id, tm.rating,
         tm.vc_m_min, tm.fz_min_mm, tm.fz_max_mm, tm.ap_slot_mm, tm.ae_slot_mm
    FROM {$toolTbl} t
    JOIN {$matTbl} tm ON tm.tool_id = t.tool_id
   WHERE t.series_id = ?");
$pm->execute([$seriesId]);
$params = [];
foreach ($pm as $r) {
  $mid = $r['material_id'];
  $params[$mid]['rating'] = (int)$r['rating'];
  $params[$mid]['rows'][$r['tool_id']] = [
    'vc' => $r['vc_m_min'], 'fz_min' => $r['fz_min_mm'], 'fz_max' => $r['fz_max_mm'],
    'ap' => $r['ap_slot_mm'], 'ae' => $r['ae_slot_mm']
  ];
}

echo json_encode([
  'brand_id' => $brandId,
  'tools'    => $tools,
  'params'   => $params
], JSON_UNESCAPED_UNICODE);
