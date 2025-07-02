<?php
// admin/machining_types_edit.php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
include 'header.php';

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: machining_types.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM machining_types WHERE machining_type_id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) {
  echo "<div class='alert alert-danger'>Tipo no encontrado.</div>";
  include 'footer.php'; exit;
}
?>

<h2>Editar Tipo de Mecanizado</h2>
<form action="machining_types_save.php" method="POST">
  <input type="hidden" name="machining_type_id" value="<?= $id ?>">
  <div class="mb-3">
    <label class="form-label">Código</label>
    <input type="text" name="code" class="form-control" required
           value="<?= htmlspecialchars($m['code']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input type="text" name="name" class="form-control" required
           value="<?= htmlspecialchars($m['name']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Descripción</label>
    <textarea name="description" class="form-control"><?= htmlspecialchars($m['description']) ?></textarea>
  </div>
  <button class="btn btn-primary">💾 Guardar</button>
  <a href="machining_types.php" class="btn btn-secondary">Cancelar</a>
</form>
<?php include 'footer.php'; ?>
