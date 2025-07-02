<?php
// admin/strategies.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php';

// Traer estrategias + su tipo de mecanizado
$sql = "
  SELECT 
    s.strategy_id,
    s.name,
    s.image,
    mt.name AS machining_type
  FROM strategies s
  LEFT JOIN machining_types mt 
    ON s.machining_type_id = mt.machining_type_id
  ORDER BY s.name
";
$strategies = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Listado de Estrategias</h2>
<div class="mb-3 d-flex justify-content-between">
  <a href="../dashboard.php" class="btn btn-outline-secondary">← Volver al panel</a>
  <a href="form.php" class="btn btn-success">➕ Nueva Estrategia</a>
</div>

<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr>
      <th>Imagen</th>
      <th>Nombre</th>
      <th>Tipo Mecanizado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($strategies as $s): ?>
      <tr>
        <td style="width:100px">
          <?php if ($s['image']): ?>
            <img src="<?= htmlspecialchars($s['image']) ?>" class="img-fluid" alt="">
          <?php else: ?>
            <em>Sin imagen</em>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($s['name']) ?></td>
        <td><?= htmlspecialchars($s['machining_type'] ?? '–') ?></td>
        <td>
          <a href="edit.php?id=<?= $s['strategy_id'] ?>" class="btn btn-sm btn-secondary">
            Editar
          </a>
          <a href="delete.php?id=<?= $s['strategy_id'] ?>"
             class="btn btn-sm btn-danger"
             onclick="return confirm('¿Eliminar esta estrategia?');">
            Eliminar
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include '../footer.php'; ?>
