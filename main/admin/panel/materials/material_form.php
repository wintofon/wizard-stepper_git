<?php
// ✅ [REFACTORED] Cambiado de ubicación a /main/admin/panel/materials – actualizado paths
require_once '../../../../admin/includes/db.php';
require_once '../../../../admin/includes/auth.php';
include '../../../../admin/panel/header.php';

$material_id = $_GET['id'] ?? null;
$categories = $pdo->query("SELECT * FROM materialcategories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$data = [
  'name'       => '',
  'category_id'=> '',
  'kc11'       => '',
  'mc'         => '',
  'angle_ramp' => '',
  'image'      => ''
];

if ($material_id) {
  $stmt = $pdo->prepare("SELECT * FROM materials WHERE material_id = ?");
  $stmt->execute([$material_id]);
  $data = $stmt->fetch();
  if (!$data) {
    echo "<div class='alert alert-danger'>Material no encontrado.</div>";
    include '../../../../admin/includes/footer.php';
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
    <label>Kc11 (N/mm²)</label>
    <input type="number" step="0.1" name="kc11" class="form-control" value="<?= $data['kc11'] ?>">
  </div>

  <div class="mb-3">
    <label>mc</label>
    <input type="number" step="0.01" name="mc" class="form-control" value="<?= $data['mc'] ?>">
  </div>

  <div class="mb-3">
    <label>Ángulo de rampa (°)</label>
    <input type="number" step="1" name="angle_ramp" class="form-control" value="<?= $data['angle_ramp'] ?>">
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

<?php include '../../../../admin/includes/footer.php'; ?>
