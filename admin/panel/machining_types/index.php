<?php
// admin/machining_types.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php';

$mt = $pdo->query("SELECT * FROM machining_types ORDER BY name")
          ->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Tipos de Mecanizado</h2>
<div class="mb-3 d-flex justify-content-between">
  <a href="../dashboard.php" class="btn btn-outline-secondary">← Panel</a>
  <a href="form.php" class="btn btn-success">➕ Nuevo Tipo</a>
</div>

<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>Código</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr>
  </thead>
  <tbody>
    <?php foreach($mt as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['code']) ?></td>
        <td><?= htmlspecialchars($m['name']) ?></td>
        <td><?= htmlspecialchars($m['description']) ?></td>
        <td>
          <a href="edit.php?id=<?= $m['machining_type_id'] ?>"
             class="btn btn-sm btn-secondary">Editar</a>
          <a href="delete.php?id=<?= $m['machining_type_id'] ?>"
             class="btn btn-sm btn-danger"
             onclick="return confirm('¿Eliminar este tipo?');">
            Eliminar
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include '../footer.php'; ?>

