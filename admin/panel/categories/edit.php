<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$category_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM MaterialCategories WHERE category_id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch();

if (!$category) {
    echo "<div class='alert alert-danger'>Categoría no encontrada.</div>";
    include '../footer.php';
    exit;
}

$other_categories = $pdo->prepare("SELECT category_id, name FROM MaterialCategories WHERE category_id != ? ORDER BY name");
$other_categories->execute([$category_id]);
$parents = $other_categories->fetchAll(PDO::FETCH_ASSOC);

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    $image = $category['image'];

    if (!empty($_FILES['image']['name'])) {
        $image = 'categories/img/' . basename($_FILES['image']['name']);
        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            __DIR__ . '/img/' . basename($_FILES['image']['name'])
        );
    }

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM MaterialCategories WHERE name = ? AND category_id != ?");
    $stmtCheck->execute([$name, $category_id]);
    if ($stmtCheck->fetchColumn() > 0) {
        $error = "⚠️ Ya existe otra categoría con ese nombre.";
    } else {
        $stmt = $pdo->prepare("UPDATE MaterialCategories SET name = ?, parent_id = ?, image = ? WHERE category_id = ?");
        $stmt->execute([$name, $parent_id, $image, $category_id]);
        $message = "✅ Categoría actualizada correctamente.";
    }
}
?>

<h2>Editar Categoría</h2>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <div class="mb-3">
    <label>Nombre</label>
    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" required>
  </div>

  <div class="mb-3">
    <label>Subcategoría de (opcional)</label>
    <select name="parent_id" class="form-select">
      <option value="">(Sin padre)</option>
      <?php foreach ($parents as $p): ?>
        <option value="<?= $p['category_id'] ?>" <?= $category['parent_id'] == $p['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label>Imagen</label>
    <input type="file" name="image" class="form-control">
  </div>

  <div class="d-flex justify-content-between">
    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
  <a href="index.php" class="btn btn-secondary">Cancelar</a>
  </div>
</form>

<?php include '../footer.php'; ?>
