<?php
// ✅ [REFACTORED] Cambiado de ubicación de módulos de materiales – actualizado paths
// admin/dashboard.php
require_once '../includes/db.php';
require_once '../includes/auth.php';
include 'header.php';

/* ── Contar fresas en todas las tablas de marca ─────────────── */
$toolTables = ['tools_sgs','tools_maykestag','tools_schneider','tools_generico'];
$toolCount  = 0;
foreach ($toolTables as $t) {
    $toolCount += (int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
}

/* ── Otras métricas ─────────────────────────────────────────── */
$seriesCount      = $pdo->query("SELECT COUNT(*) FROM series")->fetchColumn();      // 👈 NUEVO
$materialCount    = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
$categoryCount    = $pdo->query("SELECT COUNT(*) FROM materialcategories")->fetchColumn();
$transmissionCount= $pdo->query("SELECT COUNT(*) FROM transmissions")->fetchColumn();
$machiningCount   = $pdo->query("SELECT COUNT(*) FROM machining_types")->fetchColumn();
$strategyCount    = $pdo->query("SELECT COUNT(*) FROM strategies")->fetchColumn();

/* ── últimas 5 fresas ───────────────────────────────────────── */
$u = [];
foreach($toolTables as $t) {
  $brand = strtoupper(substr($t,6));
  $u[] = "SELECT tool_id,tool_code,name,material,tool_type,diameter_mm,flute_count,'$brand' AS brand,'$t' AS tbl 
          FROM $t";
}
$tools = $pdo
  ->query(implode(' UNION ALL ',$u)." ORDER BY tool_id DESC LIMIT 5")
  ->fetchAll();

/* ── mensajes de bulk upload ────────────────────────────────── */
$bulkOk  = $_GET['bulk_ok']  ?? null;
$bulkDup = $_GET['bulk_dup'] ?? null;
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container py-4">
  <h1 class="mb-4 fw-bold">📊 Panel de Control</h1>

  <?php if($bulkOk!==null): ?>
    <div class="alert alert-success">
      ✅ Importadas <strong><?= (int)$bulkOk ?></strong> fresas.
      <?php if($bulkDup): ?> Omitidos <strong><?= (int)$bulkDup ?></strong> duplicados.<?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ─── FRESAS ─────────────────────────────────────────────── -->
  <h4>Fresas</h4>
  <div class="row g-3 mb-5">
    <!-- Total fresas -->
    <div class="col-6 col-md-4 col-xl-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body text-center">
          <h6 class="card-title fw-semibold">Total de fresas</h6>
          <p class="display-6"><?= $toolCount ?></p>
          <div class="d-grid gap-2">
            <a href="tools.php" class="btn btn-outline-primary btn-sm">🔍 Ver</a>
            <a href="tool_form.php" class="btn btn-outline-success btn-sm">➕ Nuevo</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Carga masiva -->
    <div class="col-6 col-md-4 col-xl-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body text-center">
          <h6 class="card-title fw-semibold">Carga masiva</h6>
          <p class="display-6"><i class="bi bi-file-earmark-arrow-up"></i></p>
          <div class="d-grid gap-2">
            <a href="bulk_upload.php" class="btn btn-outline-primary btn-sm">📁 Subir CSV</a>
          </div>
        </div>
      </div>
    </div>

    <!-- NUEVO: Series -->
    <div class="col-6 col-md-4 col-xl-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body text-center">
          <h6 class="card-title fw-semibold">Series</h6>
          <p class="display-6"><?= $seriesCount ?></p>
          <div class="d-grid gap-2">
            <a href="series_edit.php" class="btn btn-outline-primary btn-sm">🔍 Ver / Editar</a>
            <a href="series_form.php" class="btn btn-outline-success btn-sm">➕ Nueva</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── MATERIALES ─────────────────────────────────────────── -->
  <h4>Materiales</h4>
  <div class="row g-3 mb-5">
    <div class="col-6 col-md-4 col-xl-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body text-center">
          <h6 class="card-title fw-semibold">Materiales</h6>
          <p class="display-6"><?= $materialCount ?></p>
          <div class="d-grid gap-2">
            <a href="/main/admin/panel/materials/materials.php" class="btn btn-outline-primary btn-sm">🔍 Ver</a>
            <a href="/main/admin/panel/materials/material_form.php" class="btn btn-outline-success btn-sm">➕ Nuevo</a>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body text-center">
          <h6 class="card-title fw-semibold">Categorías</h6>
          <p class="display-6"><?= $categoryCount ?></p>
          <div class="d-grid gap-2">
            <a href="categories.php" class="btn btn-outline-primary btn-sm">🔍 Ver</a>
            <a href="category_form.php" class="btn btn-outline-success btn-sm">➕ Nuevo</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── MÁQUINA ───────────────────────────────────────────── -->
  <h4>Mecánica de máquina</h4>
  <div class="row g-3 mb-5">
    <div class="col-6 col-md-4 col-xl-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body text-center">
          <h6 class="card-title fw-semibold">Transmisiones</h6>
          <p class="display-6"><?= $transmissionCount ?></p>
          <div class="d-grid gap-2">
            <a href="transmissions.php" class="btn btn-outline-primary btn-sm">🔍 Ver</a>
            <a href="transmission_form.php" class="btn btn-outline-success btn-sm">➕ Nuevo</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── MECANIZADO ─────────────────────────────────────────── -->
  <h4>Mecanizado</h4>
  <div class="row g-3 mb-5">
    <div class="col-6 col-md-4 col-xl-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body text-center">
          <h6 class="card-title fw-semibold">Tipos mecanizado</h6>
          <p class="display-6"><?= $machiningCount ?></p>
          <div class="d-grid gap-2">
            <a href="machining_types.php" class="btn btn-outline-primary btn-sm">🔍 Ver</a>
            <a href="machining_types_form.php" class="btn btn-outline-success btn-sm">➕ Nuevo</a>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-3">
      <div class="card h-100 shadow-sm">
        <div class="card-body text-center">
          <h6 class="card-title fw-semibold">Estrategias</h6>
          <p class="display-6"><?= $strategyCount ?></p>
          <div class="d-grid gap-2">
            <a href="strategies.php" class="btn btn-outline-primary btn-sm">🔍 Ver</a>
            <a href="strategy_form.php" class="btn btn-outline-success btn-sm">➕ Nuevo</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ─── Últimas fresas ─────────────────────────────────────── -->
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">🛠 Últimas fresas cargadas</div>
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Código</th><th>Nombre</th><th>Ø</th><th>Filos</th>
            <th>Material</th><th>Tipo</th><th>Marca</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($tools as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['tool_code']) ?></td>
              <td><?= htmlspecialchars($t['name']) ?></td>
              <td><?= $t['diameter_mm'] ?></td>
              <td><?= $t['flute_count'] ?></td>
              <td><?= htmlspecialchars($t['material']) ?></td>
              <td><?= htmlspecialchars($t['tool_type']) ?></td>
              <td><span class="badge bg-info text-dark"><?= $t['brand'] ?></span></td>
              <td>
                <a href="tool_edit.php?tbl=<?= $t['tbl'] ?>&id=<?= $t['tool_id'] ?>" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-pencil-square"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
