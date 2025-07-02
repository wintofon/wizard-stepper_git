<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
include 'header.php';

$toolCount        = $pdo->query("SELECT COUNT(*) FROM tools")->fetchColumn();
$materialCount    = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
$strategyCount    = $pdo->query("SELECT COUNT(*) FROM strategies")->fetchColumn();
$categoryCount    = $pdo->query("SELECT COUNT(*) FROM materialcategories")->fetchColumn();
$transmissionCount = $pdo->query("SELECT COUNT(*) FROM transmissions")->fetchColumn();

$tools = $pdo->query("SELECT * FROM tools ORDER BY tool_id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>📊 Panel de Control</h2>

<div class="row mb-4 g-3">
  <div class="col-md-4">
    <div class="card border-primary h-100">
      <div class="card-body text-primary text-center">
        <h5 class="card-title">Fresas registradas</h5>
        <p class="card-text display-6"><?= $toolCount ?></p>
        <a href="tools.php" class="btn btn-sm btn-primary">Ver fresas</a>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-success h-100">
      <div class="card-body text-success text-center">
        <h5 class="card-title">Materiales cargados</h5>
        <p class="card-text display-6"><?= $materialCount ?></p>
        <a href="materials.php" class="btn btn-sm btn-success">Ver materiales</a>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-warning h-100">
      <div class="card-body text-warning text-center">
        <h5 class="card-title">Estrategias</h5>
        <p class="card-text display-6"><?= $strategyCount ?></p>
        <a href="strategies.php" class="btn btn-sm btn-warning">Ver estrategias</a>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-dark h-100">
      <div class="card-body text-center">
        <h5 class="card-title">Categorías</h5>
        <p class="card-text display-6"><?= $categoryCount ?></p>
        <a href="categories.php" class="btn btn-sm btn-dark">Ver categorías</a>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-info h-100">
      <div class="card-body text-info text-center">
        <h5 class="card-title">Transmisiones</h5>
        <p class="card-text display-6"><?= $transmissionCount ?></p>
        <a href="transmissions.php" class="btn btn-sm btn-info">Ver transmisiones</a>
      </div>
    </div>
  </div>
</div>

<h4 class="mt-5">🛠 Últimas Fresas Cargadas</h4>

<table class="table table-hover">
  <thead class="table-light">
    <tr>
      <th>Código</th>
      <th>Nombre</th>
      <th>Tipo</th>
      <th>Diámetro</th>
      <th>Filos</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($tools as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['tool_code']) ?></td>
        <td><?= htmlspecialchars($t['name']) ?></td>
        <td><?= htmlspecialchars($t['tool_type']) ?></td>
        <td><?= $t['diameter_mm'] ?> mm</td>
        <td><?= $t['flute_count'] ?></td>
        <td>
          <a href="tool_edit.php?id=<?= $t['tool_id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include '../includes/footer.php'; ?>
