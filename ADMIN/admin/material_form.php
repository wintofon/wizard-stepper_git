<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
include 'header.php';

$material_id = $_GET['id'] ?? null;
$categories = $pdo->query("SELECT * FROM materialcategories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$data = [
  'name' => '',
  'category_id' => '',
  'spec_energy' => '',
  'image' => ''
];

if ($material_id) {
  $stmt = $pdo->prepare("SELECT * FROM materials WHERE material_id = ?");
  $stmt->execute([$material_id]);
  $data = $stmt->fetch();
  if (!$data) {
    echo "<div class='alert alert-danger'>Material no encontrado.</div>";
    include '../includes/footer.php';
    exit;
  }
}
?>

<h2><?= $material_id ? 'Editar Material' : 'Nuevo Material' ?></h2>

<form action="material_save.php" method="POST" enctype="multipart/form-data">
  <?php if ($material_id): ?>
    <input type="hidden" name="material_id" value="<?= $material_id ?>">
  <?php endif; ?>

  <div class="mb-3">
    <label>Nombre del material</label>
    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($data['name']) ?>">
  </div>

  <div class="mb-3">
    <label>Categoría</label>
    <select name="category_id" class="form-select" required>
      <option value="">-- Seleccionar categoría --</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['category_id'] ?>" <?= $data['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label>Energía específica (J/mm³)</label>
    <input type="number" step="0.1" name="spec_energy" class="form-control" value="<?= $data['spec_energy'] ?>">
  </div>

  <div class="mb-3">
    <label>Imagen</label>
    <input type="file" name="image" class="form-control">
    <?php if ($data['image']): ?>
      <div class="mt-2">
        <strong>Imagen actual:</strong><br>
        <img src="<?= htmlspecialchars($data['image']) ?>" class="img-thumbnail" style="max-width: 200px;">
      </div>
    <?php endif; ?>
  </div>

  <button type="submit" class="btn btn-primary">💾 Guardar material</button>
  <a href="materials.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php include '../includes/footer.php'; ?>