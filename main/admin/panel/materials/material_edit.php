<?php
// ✅ [REFACTORED] Cambiado de ubicación a /main/admin/panel/materials – actualizado paths
require_once '../../../../admin/includes/db.php';
require_once '../../../../admin/includes/auth.php';
include '../../../../admin/panel/header.php';

if (!isset($_GET['id'])) {
    header('Location: materials.php');
    exit;
}

$material_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM Materials WHERE material_id = ?");
$stmt->execute([$material_id]);
$material = $stmt->fetch();

if (!$material) {
    echo "<div class='alert alert-danger'>Material no encontrado.</div>";
    include '../../../../admin/includes/footer.php';
    exit;
}

$categories = $pdo->query("SELECT category_id, name FROM MaterialCategories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $kc11       = $_POST['kc11'];
    $mc         = $_POST['mc'];
    $angle_ramp = $_POST['angle_ramp'];
    $image = $material['image'];

    if (!empty($_FILES['image']['name'])) {
        $image = 'materials/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/' . $image);
    }

    // Verificar duplicado
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Materials WHERE name = ? AND material_id != ?");
    $stmtCheck->execute([$name, $material_id]);
    if ($stmtCheck->fetchColumn() > 0) {
        $error = "⚠️ Ya existe otro material con ese nombre.";
    } else {
        $stmt = $pdo->prepare("UPDATE Materials SET name = ?, category_id = ?, kc11 = ?, mc = ?, angle_ramp = ?, image = ? WHERE material_id = ?");
        $stmt->execute([$name, $category_id, $kc11, $mc, $angle_ramp, $image, $material_id]);
        $message = "✅ Material actualizado correctamente.";
    }
}
?>

<h2>Editar Material</h2>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label>Nombre</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($material['name']) ?>" required>
    </div>

    <div class="mb-3">
        <label>Categoría</label>
        <select name="category_id" class="form-select">
            <option value="">(Sin categoría superior)</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>" <?= $cat['category_id'] == $material['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Kc11 (N/mm²)</label>
        <input type="number" step="0.1" name="kc11" class="form-control" value="<?= $material['kc11'] ?>">
    </div>

    <div class="mb-3">
        <label>mc</label>
        <input type="number" step="0.01" name="mc" class="form-control" value="<?= $material['mc'] ?>">
    </div>

    <div class="mb-3">
        <label>Ángulo de rampa (°)</label>
        <input type="number" step="1" name="angle_ramp" class="form-control" value="<?= $material['angle_ramp'] ?>">
    </div>

    <div class="mb-3">
        <label>Imagen</label>
        <input type="file" name="image" class="form-control">
    </div>

    <div class="d-flex justify-content-between">
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <a href="materials.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<?php include '../../../../admin/includes/footer.php'; ?>
