<?php
// admin/strategy_form.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php';

// Traer tipos de mecanizado para el select
$machTypes = $pdo
  ->query("SELECT machining_type_id,name FROM machining_types ORDER BY name")
  ->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Nueva Estrategia</h2>
<form action="strategy_save.php" method="POST" enctype="multipart/form-data">
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label">Nombre</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Tipo Mecanizado</label>
      <select name="machining_type_id" class="form-select" required>
        <option value="">(Selecciona)</option>
        <?php foreach($machTypes as $m): ?>
          <option value="<?= $m['machining_type_id'] ?>">
            <?= htmlspecialchars($m['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Imagen</label>
      <input type="file" name="image" class="form-control">
    </div>
  </div>
  <button class="btn btn-primary">💾 Guardar</button>
  <a href="index.php" class="btn btn-secondary">Cancelar</a>
</form>
<?php include '../footer.php'; ?>
