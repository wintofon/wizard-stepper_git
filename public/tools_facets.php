<?php
/**
 * File: tools_facets.php
 * Genera filtros únicos para explorador de fresas
 * --------------------------------------------------------------
 * ▸ Devuelve JSON con todos los campos únicos necesarios
 * ▸ Robusto ante errores: manejo silencioso en prod
 * ▸ Logging en errores críticos
 * --------------------------------------------------------------
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// DEBUG MODE (activá en local dev)
$DEBUG = isset($_GET['debug']);

// Mostrar errores solo si está en modo debug
ini_set('display_errors', $DEBUG ? '1' : '0');
error_reporting(E_ALL);

// Logger básico (si querés activar logs reales)
function logError(string $msg): void {
  error_log("[tools_facets.php] $msg");
}

// Incluir conexión
require_once __DIR__ . '/../includes/db.php';
if (!isset($pdo)) {
  http_response_code(500);
  echo json_encode(['error' => 'No se pudo inicializar la base de datos']);
  exit;
}

// Listado de tablas de marcas válidas
$brandTables = ['tools_sgs', 'tools_maykestag', 'tools_schneider', 'tools_generico'];

// Inicializar campos únicos
$series = $diameter = $shank = $fluteLen = $fullLen = $cutLen = $radius = $conical = $coated = [];
$fluteCount = $toolType = $material = $madeIn = [];
$materialIds = [];

foreach ($brandTables as $tbl) {
  try {
    $rows = $pdo->query("
      SELECT DISTINCT series_id,
        diameter_mm, shank_diameter_mm, flute_length_mm,
        full_length_mm, cut_length_mm, radius,
        conical_angle, coated, flute_count,
        tool_type, material, made_in
      FROM {$tbl}
    ")->fetchAll(PDO::FETCH_ASSOC);

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

    // Material IDs (si existen las tablas)
    $matTable = 'toolsmaterial_' . substr($tbl, 6);
    $check = $pdo->query("SHOW TABLES LIKE '$matTable'");
    if ($check && $check->rowCount()) {
      $ids = $pdo->query("SELECT DISTINCT material_id FROM $matTable")->fetchAll(PDO::FETCH_COLUMN);
      $materialIds = array_merge($materialIds, $ids);
    }

  } catch (Throwable $e) {
    logError("Error procesando tabla $tbl: " . $e->getMessage());
  }
}

// Mapear series_id → código
$seriesMap = [];
try {
  $stmt = $pdo->query("SELECT id, code FROM series ORDER BY code");
  foreach ($stmt as $row) {
    $seriesMap[(int)$row['id']] = $row['code'];
  }
} catch (Throwable $e) {
  logError("Error cargando series: " . $e->getMessage());
}

// Mapear material_id → nombre
$materialMap = [];
if (!empty($materialIds)) {
  $materialIds = array_unique($materialIds);
  $marks = implode(',', array_fill(0, count($materialIds), '?'));

  try {
    $stmt = $pdo->prepare("SELECT material_id, name FROM materials WHERE material_id IN ($marks)");
    $stmt->execute($materialIds);
    foreach ($stmt as $row) {
      $materialMap[$row['material_id']] = $row['name'];
    }
  } catch (Throwable $e) {
    logError("Error cargando materiales: " . $e->getMessage());
  }
}

// Mapear estrategia_id → nombre
$strategyMap = [];
try {
  $res = $pdo->query("SELECT strategy_id, name FROM strategies ORDER BY name");
  $strategyMap = array_column($res->fetchAll(PDO::FETCH_ASSOC), 'name', 'strategy_id');
} catch (Throwable $e) {
  logError("Error cargando estrategias: " . $e->getMessage());
}

// Función para filtrar y ordenar valores únicos
function uniqSorted(array $arr): array {
  $arr = array_filter($arr, fn($v) => $v !== null && $v !== '');
  $arr = array_unique($arr, SORT_REGULAR);
  sort($arr, SORT_NATURAL | SORT_FLAG_CASE);
  return $arr;
}

// Preparar salida
$out = [
  'brand'             => ['SGS', 'MAYKESTAG', 'SCHNEIDER', 'GENERICO'],
  'series_id'         => $seriesMap,
  'diameter_mm'       => uniqSorted($diameter),
  'shank_diameter_mm' => uniqSorted($shank),
  'flute_length_mm'   => uniqSorted($fluteLen),
  'full_length_mm'    => uniqSorted($fullLen),
  'cut_length_mm'     => uniqSorted($cutLen),
  'radius'            => uniqSorted($radius),
  'conical_angle'     => uniqSorted($conical),
  'coated'            => uniqSorted($coated),
  'flute_count'       => uniqSorted($fluteCount),
  'tool_type'         => uniqSorted($toolType),
  'material'          => uniqSorted($material),
  'material_id'       => $materialMap,
  'made_in'           => uniqSorted($madeIn),
  'strategy_id'       => $strategyMap
];

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
