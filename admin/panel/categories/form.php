<?php
require_once '../../includes/db.php';
include '../header.php';

$message = "";
$error = "";

// Cargar categorías existentes
$parent_categories = $pdo->query("SELECT category_id, name FROM MaterialCategories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

    // Verificar si ya existe la categoría
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM MaterialCategories WHERE name = ?");
    $stmt->execute([$name]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $error = "⚠️ Ya existe una categoría con ese nombre.";
    } else {
        $image = '';

        if (!empty($_FILES['image']['name'])) {
            $image = 'categories/img/' . basename($_FILES['image']['name']);
            move_uploaded_file(
                $_FILES['image']['tmp_name'],
                __DIR__ . '/img/' . basename($_FILES['image']['name'])
            );
        }

        $stmt = $pdo->prepare("INSERT INTO MaterialCategories (name, parent_id, image) VALUES (?, ?, ?)");
        $stmt->execute([$name, $parent_id, $image]);

        $message = "✅ Categoría cargada exitosamente.";
    }
}
?>

<h2>Agregar Nueva Categoría</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label>Nombre de la Categoría</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Subcategoría de (opcional)</label>
        <select name="parent_id" class="form-select">
            <option value="">(Sin padre)</option>
            <?php foreach ($parent_categories as $parent): ?>
                <option value="<?= htmlspecialchars($parent['category_id']) ?>"><?= htmlspecialchars($parent['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Imagen de la Categoría</label>
        <input type="file" name="image" class="form-control">
    </div>

    <div class="d-flex justify-content-between">
        <button type="submit" class="btn btn-primary">Guardar Categoría</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>
