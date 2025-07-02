<?php
// ✅ [REFACTORED] Cambiado de ubicación a /main/admin/panel/materials – actualizado paths
require_once '../../../../admin/includes/db.php';
require_once '../../../../admin/includes/auth.php';

if (!isset($_GET['id'])) {
    header('Location: materials.php');
    exit;
}

$material_id = $_GET['id'];

// Eliminar el material
$pdo->prepare('DELETE FROM Materials WHERE material_id = ?')->execute([$material_id]);

header('Location: materials.php');
exit;
