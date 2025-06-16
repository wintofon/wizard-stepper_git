<?php
// tools_search.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Recibir filtros y búsqueda
$q = $_GET['q'] ?? '';
$diameter = $_GET['diameter'] ?? '';

$brandTables = [
    'tools_sgs',
    'tools_maykestag',
    'tools_schneider',
    'tools_generico'
];

// Preparar SQL dinámico
$results = [];
foreach ($brandTables as $table) {
    $sql = "SELECT t.tool_id, t.name, t.tool_code, t.diameter_mm, s.code as series_code, b.name as brand, t.image, t.flute_count, t.cut_length_mm, t.shank_diameter_mm, t.source_table 
            FROM {$table} t
            JOIN series s ON t.series_id = s.id
            JOIN brands b ON s.brand_id = b.id
            WHERE 1=1 ";

    $params = [];
    if ($q !== '') {
        $sql .= " AND (t.name LIKE ? OR t.tool_code LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($diameter !== '') {
        $sql .= " AND t.diameter_mm = ?";
        $params[] = $diameter;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['source_table'] = $table;
        $results[] = $row;
    }
}

// Retornar JSON
echo json_encode($results, JSON_UNESCAPED_UNICODE);
