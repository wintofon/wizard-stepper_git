php
require_once '..includesdb.php';
require_once '..includesauth.php';

if (!isset($_GET['id'])) {
    header('Location materials.php');
    exit;
}

$material_id = $_GET['id'];

 Eliminar el material
$pdo-prepare(DELETE FROM Materials WHERE material_id = )-execute([$material_id]);

header('Location materials.php');
exit;