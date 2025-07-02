<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
include 'header.php';

// Traer categorías
$query = "
    SELECT c.*, p.name AS parent_name
    FROM MaterialCategories c
    LEFT JOIN MaterialCategories p ON c.parent_id = p.category_id
    ORDER BY c.name
";
$categories = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Listado de Categorías de Material</h2>


<div class="mb-3 d-flex justify-content-between">
  <a href="dashboard.php" class="btn btn-outline-secondary">← Volver al panel</a>
  <a href="category_form.php" class="btn btn-success">➕ Nueva Categoría</a>
</div>



<?php if (empty($categories)): ?>
  <div class="alert alert-warning">⚠️ No hay categorías cargadas.</div>
<?php else: ?>
<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>Imagen</th>
            <th>Nombre</th>
            <th>Padre</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td style="width: 100px">
                    <?php if ($c['image']): ?>
                        <img src="<?= htmlspecialchars($c['image']) ?>" alt="Imagen" class="img-fluid">
                    <?php else: ?>
                        <em>Sin imagen</em>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?= htmlspecialchars($c['parent_name'] ?? '–') ?></td>
                <td>
                    <a href="category_edit.php?id=<?= $c['category_id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                    <a href="category_delete.php?id=<?= $c['category_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro que querés eliminar esta categoría?');">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>