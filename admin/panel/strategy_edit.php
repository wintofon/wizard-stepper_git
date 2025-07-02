<?php
// admin/strategy_edit.php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
include 'header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
  header('Location: strategies.php');
  exit;
}

// Recuperar la estrategia
$stmt = $pdo->prepare("SELECT * FROM strategies WHERE strategy_id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) {
  echo "<div class='alert alert-danger'>Estrategia no encontrada.</div>";
  include 'footer.php';
  exit;
}

// Opciones de tipos de mecanizado
$machTypes = $pdo
  ->query("SELECT machining_type_id,name FROM machining_types ORDER BY name")
  ->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Editar Estrategia</h2>
<form action="strategy_save.php" method="POST" enctype="multipart/form-data">
  <input type="hidden" name="strategy_id" value="<?= $s['strategy_id'] ?>">
  <input type="hidden" name="old_image" value="<?= htmlspecialchars($s['image']) ?>">

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label">Nombre</label>
      <input type="text" name="name" class="form-control" required
             value="<?= htmlspecialchars($s['name']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Tipo Mecanizado</label>
      <select name="machining_type_id" class="form-select" required>
        <?php foreach($machTypes as $m): ?>
          <option value="<?= $m['machining_type_id'] ?>"
            <?= $m['machining_type_id']==$s['machining_type_id']?'selected':''?>>
            <?= htmlspecialchars($m['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Imagen</label>
      <?php if ($s['image']): ?>
        <div class="mb-1">
          <img src="<?= htmlspecialchars($s['image']) ?>" style="max-height:60px" class="img-thumbnail">
        </div>
      <?php endif; ?>
      <input type="file" name="image" class="form-control">
    </div>
  </div>

  <button class="btn btn-primary">💾 Guardar</button>
  <a href="strategies.php" class="btn btn-secondary">Cancelar</a>
</form>
<?php include 'footer.php'; ?>
