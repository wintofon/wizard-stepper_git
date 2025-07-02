<?php
// ✅ [REFACTORED] Cambiado de ubicación a /main/admin/panel/materials – actualizado paths
require_once '../../../../admin/includes/db.php';
require_once '../../../../admin/includes/auth.php';
include '../../../../admin/panel/header.php';

// Traer materiales
$query = "
    SELECT m.*, c.name AS category_name
    FROM Materials m
    LEFT JOIN MaterialCategories c ON m.category_id = c.category_id
    ORDER BY m.name
";
$materials = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Listado de Materiales</h2>


<div class="mb-3 d-flex justify-content-between">
  <a href="dashboard.php" class="btn btn-outline-secondary">← Volver al panel</a>
  <a href="material_form.php" class="btn btn-success">➕ Nuevo Material</a>
</div>


<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>Imagen</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Kc11 (N/mm²)</th>
            <th>mc</th>
            <th>Áng. rampa (°)</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($materials as $mat): ?>
            <tr>
                <td style="width: 100px">
                    <?php if ($mat['image']): ?>
                        <img src="<?= htmlspecialchars($mat['image']) ?>" alt="Imagen" class="img-fluid">
                    <?php else: ?>
                        <em>Sin imagen</em>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($mat['name']) ?></td>
                <td><?= htmlspecialchars($mat['category_name'] ?? '–') ?></td>
                <td><?= htmlspecialchars($mat['kc11']) ?></td>
                <td><?= htmlspecialchars($mat['mc']) ?></td>
                <td><?= htmlspecialchars($mat['angle_ramp']) ?></td>
                <td>
                    <a href="material_edit.php?id=<?= $mat['material_id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                    <a href="material_delete.php?id=<?= $mat['material_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este material?');">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include '../../../../admin/includes/footer.php'; ?>
