<?php
// admin/tools_form.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php'; // Carga Bootstrap y Bootstrap-Icons

// Catálogos
$brands     = $pdo->query("SELECT id,name FROM brands ORDER BY name")->fetchAll();
$toolTypes  = $pdo->query("SELECT code,name FROM tooltypes ORDER BY name")->fetchAll();
$materials  = $pdo->query("SELECT material_id,name FROM materials ORDER BY name")->fetchAll();
$strategies = $pdo->query("SELECT strategy_id,name FROM strategies ORDER BY name")->fetchAll();
?>
<!doctype html>
<html lang="es"><head>
  <meta charset="utf-8">
  <title>Nueva fresa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .star { cursor:pointer; font-size:1.25rem; color:#ddd; }
    .star.filled { color:#0d6efd; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="mb-3 d-flex justify-content-between">
    <a href="dashboard.php" class="btn btn-outline-secondary">← Volver al panel</a>
    <button form="createForm" class="btn btn-success">💾 Guardar Fresa</button>
  </div>

  <h2 class="mb-4">🆕 Alta de Fresa</h2>
  <form id="createForm" method="POST" action="tool_save.php" enctype="multipart/form-data" class="vstack gap-3">
    <!-- Marca / Serie / Código interno -->
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Marca</label>
        <select id="brand" name="brand_id" class="form-select" required>
          <?php foreach($brands as $b): ?>
            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
          <?php endforeach;?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Serie (ID)</label>
        <input type="text" name="series_id" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Código interno</label>
        <input type="text" name="tool_code" class="form-control" required>
      </div>
    </div>

    <!-- Nombre y geometría básica -->
    <div class="row g-2">
      <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Ø (mm)</label>
        <input type="number" step="0.001" name="diameter_mm" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Filos</label>
        <input type="number" name="flute_count" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Largo total</label>
        <input type="number" step="0.001" name="full_length_mm" class="form-control">
      </div>
    </div>

    <!-- Más geometría y propiedades -->
    <div class="row g-2 mt-2">
      <div class="col-md-3">
        <label class="form-label">Ø Mango</label>
        <input type="number" step="0.001" name="shank_diameter_mm" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Largo filo</label>
        <input type="number" step="0.001" name="cut_length_mm" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Helix (°)</label>
        <input type="number" step="0.1" name="helix" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Recubrimiento</label>
        <input type="text" name="coated" class="form-control" value="Sin recubrir">
      </div>
    </div>

    <!-- Tipo, Origen, Material herramienta -->
    <div class="row g-2 mt-2">
      <div class="col-md-4">
        <label class="form-label">Tipo</label>
        <select name="tool_type" class="form-select">
          <?php foreach($toolTypes as $t): ?>
            <option value="<?= $t['code'] ?>"><?= htmlspecialchars($t['name']) ?></option>
          <?php endforeach;?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Origen</label>
        <input type="text" name="made_in" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Material herr.</label>
        <input type="text" name="material" class="form-control">
      </div>
    </div>

    <!-- Notas -->
    <div>
      <label class="form-label">Notas</label>
      <textarea name="notes" class="form-control" rows="2"></textarea>
    </div>

    <!-- Imágenes -->
    <div class="row g-2">
      <div class="col-md-6">
        <label class="form-label">Imagen principal</label>
        <input type="file" name="image" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label">Imagen dimensiones</label>
        <input type="file" name="image_dimensions" class="form-control">
      </div>
    </div>

    <hr>
    <!-- Estrategias -->
    <h5>✅ Estrategias compatibles</h5>
    <div class="d-flex flex-wrap gap-3 mb-3">
      <?php foreach($strategies as $s): ?>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="strategies[]" value="<?= $s['strategy_id'] ?>">
          <label class="form-check-label"><?= htmlspecialchars($s['name']) ?></label>
        </div>
      <?php endforeach;?>
    </div>

    <hr>
    <!-- Parámetros por material -->
    <h5>📐 Parámetros por Material</h5>
    <table class="table table-bordered align-middle" id="paramTable">
      <thead class="table-light">
        <tr>
          <th>Material</th>
          <th>Rating</th>
          <th>Vc</th>
          <th>Fz min</th>
          <th>Fz max</th>
          <th>ap</th>
          <th>ae</th>
          <th></th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
    <button type="button" class="btn btn-success btn-sm" onclick="addParamRow()">➕ Agregar Fila</button>

    <div class="text-end mt-4">
      <button type="submit" class="btn btn-primary">💾 Guardar</button>
      <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
// Lista de materiales para el selector
const materials = <?= json_encode($materials) ?>;

// Función para añadir una nueva fila de parámetros
function addParamRow() {
  const id = Date.now();
  const opts = materials.map(m =>
    `<option value="${m.material_id}">${m.name}</option>`
  ).join('');
  $('#paramTable tbody').append(`
    <tr>
      <td><select name="materials[new_${id}][material_id]" class="form-select">${opts}</select></td>
      <td>
        ${[1,2,3].map(i =>
          `<i class="bi bi-star star" data-value="${i}"></i>`
        ).join('')}
        <input type="hidden" name="materials[new_${id}][rating]" value="0">
      </td>
      <td><input type="number" step="0.01" name="materials[new_${id}][vc_m_min]" class="form-control"></td>
      <td><input type="number" step="0.01" name="materials[new_${id}][fz_min_mm]" class="form-control"></td>
      <td><input type="number" step="0.01" name="materials[new_${id}][fz_max_mm]" class="form-control"></td>
      <td><input type="number" step="0.01" name="materials[new_${id}][ap_slot_mm]" class="form-control"></td>
      <td><input type="number" step="0.01" name="materials[new_${id}][ae_slot_mm]" class="form-control"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✖</button></td>
    </tr>
  `);
}

// Manejo del clic en estrellas para rating
$(document).on('click', '.star', function(){
  const $star = $(this);
  const $cell = $star.closest('td');
  const val = parseInt($star.data('value'));
  $cell.find('.star').each(function(){
    $(this).toggleClass('filled', parseInt($(this).data('value')) <= val);
  });
  $cell.find('input[type=hidden]').val(val);
});
</script>
<?php include '../../includes/footer.php'; ?>
