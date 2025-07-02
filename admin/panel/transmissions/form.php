<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php';

$editMode = false;
$transmission = [
    'id' => '',
    'name' => '',
    'coef_security' => 1.0,
    'rpm_min' => 3000,
    'rpm_max' => 18000,
    'feed_max' => 5000,
    'image' => ''
];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM transmissions WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $transmission = $stmt->fetch();
    if (!$transmission) {
        die("Transmisión no encontrada.");
    }
}
?>

<h2><?= $editMode ? '✏️ Editar Transmisión' : '➕ Nueva Transmisión' ?></h2>

<form method="POST" action="transmission_save.php" enctype="multipart/form-data">
  <input type="hidden" name="id" value="<?= $transmission['id'] ?>">

  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($transmission['name']) ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Coeficiente de Seguridad</label>
    <input type="number" step="0.01" name="coef_security" class="form-control" required min="0" max="1" value="<?= $transmission['coef_security'] ?>">
  </div>

  <div class="row mb-3">
    <div class="col-md-4">
      <label class="form-label">RPM mínima</label>
      <input type="number" name="rpm_min" class="form-control" required min="500" value="<?= $transmission['rpm_min'] ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">RPM máxima</label>
      <input type="number" name="rpm_max" class="form-control" required min="1000" value="<?= $transmission['rpm_max'] ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Avance máximo (mm/min)</label>
      <input type="number" name="feed_max" class="form-control" required min="100" value="<?= $transmission['feed_max'] ?>">
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label">Imagen (JPG o PNG)</label>
    <input type="file" name="image" accept=".jpg,.jpeg,.png" class="form-control">
    <?php if ($transmission['image']): ?>
      <div class="mt-2">
        <img src="../assets/img/<?= $transmission['image'] ?>" alt="" height="80">
      </div>
    <?php endif; ?>
  </div>

  <button type="submit" class="btn btn-success">Guardar</button>
  <a href="index.php" class="btn btn-secondary">← Volver</a>
</form>

<?php include '../../includes/footer.php'; ?>
