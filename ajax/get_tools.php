<?php
declare(strict_types=1);

// (1) Sesión y seguridad
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

// (2) Forzamos devolver JSON
header('Content-Type: application/json');

// (3) Cargar BD
require_once __DIR__ . '/../includes/db.php';  // Ajusta si hiciera falta

// (4) Comprobar sesión
$matRaw = $_SESSION['material_id'] ?? null;
$strRaw = $_SESSION['strategy_id'] ?? null;
if (!is_numeric($matRaw) || !is_numeric($strRaw)) {
    echo json_encode([]);
    exit;
}
$material_id = (int) $matRaw;
$strategy_id = (int) $strRaw;

// (5) Tablas válidas
$toolTables = [
    'tools_sgs'       => 'toolsmaterial_sgs',
    'tools_maykestag' => 'toolsmaterial_maykestag',
    'tools_schneider' => 'toolsmaterial_schneider',
    'tools_generico'  => 'toolsmaterial_generico',
];

$pdo = db();
$allFresas = [];

foreach ($toolTables as $toolTbl => $matTbl) {
    // Validar nombres de tabla
    if (!preg_match('/^[a-z0-9_]+$/', $toolTbl) || !preg_match('/^[a-z0-9_]+$/', $matTbl)) {
        continue;
    }

    $sql = "
      SELECT
        t.tool_id,
        s.code            AS serie,
        b.name            AS brand,
        t.tool_code       AS tool_code,
        t.name            AS name,
        t.image           AS image,
        t.diameter_mm     AS diameter_mm,
        t.shank_diameter_mm,
        t.flute_length_mm,
        t.cut_length_mm,
        t.flute_count,
        m.rating          AS rating
      FROM {$toolTbl} AS t
      INNER JOIN {$matTbl} AS m
        ON t.tool_id = m.tool_id
      INNER JOIN series           AS s ON t.series_id = s.id
      INNER JOIN brands           AS b ON s.brand_id  = b.id
      INNER JOIN toolstrategy     AS ts
        ON ts.tool_id    = t.tool_id
       AND ts.tool_table = ?
      WHERE m.material_id  = ?
        AND ts.strategy_id = ?
        AND m.rating      > 0
      ORDER BY m.rating DESC
    ";
    try {
        $stmt = $pdo->prepare($sql);
        if ($stmt === false) {
            continue;
        }
        $stmt->execute([$toolTbl, $material_id, $strategy_id]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['source_table'] = $toolTbl;
            $allFresas[] = $row;
        }
    } catch (\PDOException $e) {
        // En producción registra en un log, aquí simples omitimos
        continue;
    }
}

// (6) Devolver JSON
echo json_encode($allFresas);
