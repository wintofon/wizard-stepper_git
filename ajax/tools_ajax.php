<?php
// tools_ajax.php - Devuelve lista de herramientas filtradas (sin autenticación)
require_once __DIR__ . '/../src/Config/AppConfig.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../src/Utils/ToolService.php';
header('Content-Type: application/json; charset=utf-8');

$brandTables = [
  'SGS'       => 'tools_sgs',
  'MAYKESTAG' => 'tools_maykestag',
  'SCHNEIDER' => 'tools_schneider',
  'GENERICO'  => 'tools_generico'
];

// Map series_id => code
$seriesMap = [];
foreach ($pdo->query("SELECT id, code FROM series") as $s) {
    $seriesMap[$s['id']] = $s['code'];
}

// 1) Normalize GET filters
$f = [];
$qText = '';
foreach ($_GET as $k => $v) {
    if ($k === 'q') {
        $qText = trim($v);
        continue;
    }
    $f[$k] = is_array($v) ? array_map('trim', $v) : [trim($v)];
}

// 2) Determine tables to query based on brands selected (or all)
$tables = isset($f['brand'])
        ? array_values(array_intersect_key($brandTables, array_flip($f['brand'])))
        : array_values($brandTables);

// 3) Build UNION SQL
$union = [];
foreach ($tables as $t) {
    $brand = strtoupper(substr($t, 6));
    $union[] = "
      SELECT
        tool_id, series_id, tool_code, name, tool_type, material,
        diameter_mm, shank_diameter_mm, flute_length_mm, cut_length_mm,
        full_length_mm, rack_angle, helix, conical_angle, radius,
        coated, flute_count, made_in,
        '$brand' AS brand, '$t' AS tbl, 3 AS rating
      FROM $t
    ";
}
$baseSql = implode(' UNION ALL ', $union);

// 4) Build WHERE clauses for filters
$where = [];
$bind  = [];
$addIn = function ($col, $vals) use (&$where, &$bind) {
    $ph = rtrim(str_repeat('?,', count($vals)), ',');
    $where[] = "U.$col IN ($ph)";
    array_push($bind, ...$vals);
};
foreach ([
    'series_id','diameter_mm','shank_diameter_mm','flute_length_mm',
    'cut_length_mm','full_length_mm','flute_count','tool_type','material',
    'radius','conical_angle','coated','made_in','rack_angle','helix'
] as $c) {
    if (!empty($f[$c])) {
        $addIn($c, $f[$c]);
    }
}
if ($qText !== '') {
    $where[] = "(U.name LIKE ? OR U.tool_code LIKE ?)";
    $bind[]  = "%{$qText}%";
    $bind[]  = "%{$qText}%";
}

// 5) Material filter with subquery
$whereMat = [];
$bindMat  = [];
if (!empty($f['material_id'])) {
    $ph = rtrim(str_repeat('?,', count($f['material_id'])), ',');
    foreach ($tables as $t) {
        $tm = 'toolsmaterial_' . substr($t, 6);
        $whereMat[] = "(U.tbl=? AND U.tool_id IN (
                          SELECT tool_id FROM $tm WHERE material_id IN ($ph)
                       ))";
        $bindMat[] = $t;
        foreach ($f['material_id'] as $mid) {
            $bindMat[] = $mid;
        }
    }
    $where[] = '(' . implode(' OR ', $whereMat) . ')';
}

// 6) Strategy filter with subquery
$whereStrat = [];
$bindStr    = [];
if (!empty($f['strategy_id'])) {
    $ph = rtrim(str_repeat('?,', count($f['strategy_id'])), ',');
    foreach ($tables as $t) {
        $whereStrat[] = "(U.tbl=? AND U.tool_id IN (
                            SELECT tool_id FROM toolstrategy
                             WHERE tool_table=? AND strategy_id IN ($ph)
                          ))";
        $bindStr[] = $t;
        $bindStr[] = $t;
        foreach ($f['strategy_id'] as $sid) {
            $bindStr[] = $sid;
        }
    }
    $where[] = '(' . implode(' OR ', $whereStrat) . ')';
}

// 7) Final SQL
$sql = "SELECT * FROM (\n$baseSql\n) AS U";
if ($where) {
    $sql .= "\nWHERE " . implode("\n  AND ", $where);
}
$sql .= "\nORDER BY U.tool_id DESC";

// 8) Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($bind, $bindMat, $bindStr));

// 9) Build output array
$out = [];
foreach ($stmt as $row) {
    // Map series code
    $row['series_code'] = $seriesMap[$row['series_id']] ?? '-';

    // Fetch full details
    $det = $pdo->prepare("SELECT * FROM {$row['tbl']} WHERE tool_id=?");
    $det->execute([$row['tool_id']]);
    $row['details'] = $det->fetch(PDO::FETCH_ASSOC);
    $row['details']['image_url'] = ToolService::getToolImageUrl(
        $pdo,
        $row['tbl'],
        (int) $row['tool_id']
    );

    // Fetch parameters per material
    $tm = 'toolsmaterial_' . substr($row['tbl'], 6);
    $p  = $pdo->prepare("
        SELECT m.name, vc_m_min, fz_min_mm, fz_max_mm,
               ap_slot_mm, ae_slot_mm, rating
          FROM $tm tm
          JOIN materials m ON m.material_id = tm.material_id
         WHERE tool_id=?
    ");
    $p->execute([$row['tool_id']]);
    $row['params'] = array_map(fn($r) => [
        'material' => $r['name'],
        'vc'       => $r['vc_m_min'],
        'fzmin'    => $r['fz_min_mm'],
        'fzmax'    => $r['fz_max_mm'],
        'ap'       => $r['ap_slot_mm'],
        'ae'       => $r['ae_slot_mm'],
        'rating'   => $r['rating']
    ], $p->fetchAll(PDO::FETCH_ASSOC));

    // Fetch strategies
    $s = $pdo->prepare("
        SELECT s.name
          FROM toolstrategy ts
          JOIN strategies s ON s.strategy_id = ts.strategy_id
         WHERE ts.tool_table = ? AND ts.tool_id = ?
    ");
    $s->execute([$row['tbl'], $row['tool_id']]);
    $row['strategies'] = $s->fetchAll(PDO::FETCH_COLUMN);

    $out[] = $row;
}

// 10) Output JSON
echo json_encode($out, JSON_UNESCAPED_UNICODE);
