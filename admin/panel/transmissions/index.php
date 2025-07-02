<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php';

// Obtener transmisiones
$stmt = $pdo->query("SELECT * FROM transmissions ORDER BY name ASC");
$transmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Listado de Transmisiones</h2>

<div class="mb-3 d-flex justify-content-between">
  <a href="../dashboard.php" class="btn btn-outline-secondary">← Volver al panel</a>
  <a href="form.php" class="btn btn-success">➕ Nueva Transmisión</a>
</div>

<table class="table table-bordered table-hover align-middle">
  <thead class="table-dark">
    <tr>
      <th style="width: 120px;">Imagen</th>
      <th>Nombre</th>
      <th>Coef. Seguridad</th>
      <th>RPM Mín</th>
      <th>RPM Máx</th>
      <th>Avance Máx</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($transmissions as $t): ?>
      <tr>
        <td>
          <?php if (!empty($t['image']) && file_exists(__DIR__ . '/../assets/img/' . $t['image'])): ?>
            <img src="/assets/img/<?= htmlspecialchars($t['image']) ?>" alt="Imagen" class="img-fluid" style="max-height: 80px;">
          <?php else: ?>
            <em>Sin imagen</em>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($t['name']) ?></td>
        <td><?= number_format($t['coef_security'], 2) ?></td>
        <td><?= intval($t['rpm_min']) ?></td>
        <td><?= intval($t['rpm_max']) ?></td>
        <td><?= intval($t['feed_max']) ?> mm/min</td>
        <td>
          <a href="form.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
          <a href="delete.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-danger"
             onclick="return confirm('¿Estás seguro de eliminar esta transmisión?');">Eliminar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include '../footer.php'; ?>

