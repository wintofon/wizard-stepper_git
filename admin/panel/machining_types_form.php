<?php
// admin/machining_types_form.php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
include 'header.php';
?>

<h2>Nuevo Tipo de Mecanizado</h2>
<form action="machining_types_save.php" method="POST">
  <div class="mb-3">
    <label class="form-label">Código</label>
    <input type="text" name="code" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input type="text" name="name" class="form-control" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Descripción</label>
    <textarea name="description" class="form-control"></textarea>
  </div>
  <button class="btn btn-primary">💾 Guardar</button>
  <a href="machining_types.php" class="btn btn-secondary">Cancelar</a>
</form>
<?php include 'footer.php'; ?>
