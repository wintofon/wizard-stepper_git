<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Sólo por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bulk_upload.php');
    exit;
}

// Validar marca
$brandId = (int)($_POST['brand_id'] ?? 0);
if (!$brandId) {
    header('Location: bulk_upload.php?error=Marca inválida');
    exit;
}
$tbl = brandTable($brandId);
if (!$tbl) {
    header('Location: bulk_upload.php?error=Marca inválida');
    exit;
}

// Construir nombre de tabla de parámetros
$mtbl = 'toolsmaterial_' . substr($tbl, 6);

// Validar subida
if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    header('Location: bulk_upload.php?error=Error al subir el archivo');
    exit;
}

// Abrir CSV
$tmp = $_FILES['csv']['tmp_name'];
$fh  = @fopen($tmp, 'r');
if (!$fh) {
    header('Location: bulk_upload.php?error=No se pudo leer el CSV');
    exit;
}

$sep = ';';
$expected = [
    'series_id','tool_code','name','flute_count','diameter_mm',
    'shank_diameter_mm','flute_length_mm','cut_length_mm','full_length_mm',
    'rack_angle','helix','conical_angle','radius',
    'tool_type','made_in','material','coated','notes',
    'material_id','vc_m_min','fz_min_mm','fz_max_mm','ap_slot_mm','ae_slot_mm'
];

// Leer cabecera
$header = fgetcsv($fh, 0, $sep);
if ($header === false || $header !== $expected) {
    fclose($fh);
    header('Location: bulk_upload.php?error=Cabecera CSV incorrecta');
    exit;
}

try {
    $pdo->beginTransaction();

    // Preparar INSERT para herramientas
    $colsTool = implode(',', array_slice($expected, 0, 18));
    $phTool   = implode(',', array_fill(0, 18, '?'));
    $insTool  = $pdo->prepare("INSERT INTO $tbl($colsTool) VALUES($phTool)");

    // Preparar INSERT para parámetros de corte
    $colsPar  = 'tool_id,material_id,vc_m_min,fz_min_mm,fz_max_mm,ap_slot_mm,ae_slot_mm';
    $phPar    = implode(',', array_fill(0, 7, '?'));
    $insPar   = $pdo->prepare("INSERT INTO $mtbl($colsPar) VALUES($phPar)");

    $inserted = 0;
    $duplicates = 0;

    while (($row = fgetcsv($fh, 0, $sep)) !== false) {
        // Saltar líneas vacías
        if (count($row) < count($expected)) {
            continue;
        }
        // Normalizar valores vacíos
        $row = array_map(fn($v) => $v === '' ? null : $v, $row);

        // Comprobar duplicado por tool_code
        $code = $row[1];
        $chk = $pdo->prepare("SELECT 1 FROM $tbl WHERE tool_code = ? LIMIT 1");
        $chk->execute([$code]);
        if ($chk->fetchColumn()) {
            $duplicates++;
            continue;
        }

        // Insertar herramienta
        $insTool->execute(array_slice($row, 0, 18));
        $newId = $pdo->lastInsertId();
        $inserted++;

        // Insertar parámetros si existe material_id
        if (!empty($row[18])) {
            $params = [
                $newId,
                $row[18], // material_id
                $row[19], $row[20], $row[21], $row[22], $row[23]
            ];
            $insPar->execute($params);
        }
    }

    $pdo->commit();
    fclose($fh);

    // Redirigir con resumen
    header("Location: bulk_upload.php?bulk_ok={$inserted}&bulk_dup={$duplicates}");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    fclose($fh);
    header('Location: bulk_upload.php?error=Error al procesar el CSV');
    exit;
}
