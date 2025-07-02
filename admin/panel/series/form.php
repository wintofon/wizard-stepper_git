<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php';

$brands = $pdo->query("SELECT id,name FROM brands ORDER BY name")->fetchAll();
?>
<div class="container py-4">
  <div class="mb-3 d-flex justify-content-between">
    <a href="dashboard.php" class="btn btn-outline-secondary">← Volver</a>
  </div>

  <h2>➕ Nueva Serie</h2>
  <form method="POST" action="save.php" class="vstack gap-3">
    <input type="hidden" name="action" value="create">
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Marca</label>
        <select name="brand_id" class="form-select" required>
          <?php foreach($brands as $b): ?>
            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Código de serie</label>
        <input type="text" name="code" class="form-control" required>
      </div>
    </div>
    <button class="btn btn-primary mt-3">💾 Crear serie</button>
  </form>
</div>
<?php include '../../includes/footer.php'; ?>
