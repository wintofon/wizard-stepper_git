<?php
// admin/tool_edit.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php'; // loads Bootstrap & Bootstrap-Icons

// avoid redeclaration
if (!function_exists('brandTable')) {
    function brandTable(int $id): string {
        return match($id) {
            1=>'tools_sgs',
            2=>'tools_maykestag',
            3=>'tools_schneider',
            default=>'tools_generico',
        };
    }
}

// get & validate params
$tbl    = $_GET['tbl']    ?? null;
$toolId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$tbl || !$toolId) {
    header('Location: dashboard.php');
    exit;
}

// fetch tool
$stmt = $pdo->prepare("SELECT * FROM {$tbl} WHERE tool_id = ?");
$stmt->execute([$toolId]);
$tool = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tool) {
    header('Location: dashboard.php');
    exit;
}

// catalogs
$brands     = $pdo->query("SELECT id,name FROM brands ORDER BY name")->fetchAll();
$toolTypes  = $pdo->query("SELECT code,name FROM tooltypes ORDER BY name")->fetchAll();
$materials  = $pdo->query("SELECT material_id,name FROM materials ORDER BY name")->fetchAll();
$strategies = $pdo->query("SELECT strategy_id,name FROM strategies ORDER BY name")->fetchAll();

// selected strategies
$ts = $pdo->prepare("SELECT strategy_id FROM toolstrategy WHERE tool_table=? AND tool_id=?");
$ts->execute([$tbl,$toolId]);
$selectedStrategies = $ts->fetchAll(PDO::FETCH_COLUMN);
// Parámetros + rating
$mtbl = 'toolsmaterial_' . substr($tbl, 6);
$pm = $pdo->prepare("
    SELECT material_id, rating, vc_m_min, fz_min_mm, fz_max_mm, ap_slot_mm, ae_slot_mm
      FROM {$mtbl}
     WHERE tool_id = ?
");
$pm->execute([$toolId]);
$params = $pm->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
  .star { cursor:pointer; font-size:1.25rem; color:#ddd; }
  .star.filled { color:#0d6efd; }
</style>

<div class="mb-3 d-flex justify-content-between">
  <a href="dashboard.php" class="btn btn-outline-secondary">← Volver al panel</a>
  <button form="editForm" class="btn btn-success">💾 Guardar Cambios</button>
</div>

<h2 class="mb-4">✏️ Editar Fresa</h2>
<form id="editForm" method="POST" action="tool_save.php" enctype="multipart/form-data" class="vstack gap-3">
  <input type="hidden" name="tool_id" value="<?= $toolId ?>">
  <input type="hidden" name="tbl" value="<?= htmlspecialchars($tbl) ?>">

  <!-- Marca -->
  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label">Marca</label>
      <select name="brand_id" id="brand" class="form-select" required>
        <?php foreach($brands as $b): ?>
          <option value="<?= $b['id'] ?>" <?= $b['id']==$tool['series_id'] /* series_id holds brand? adjust if needed */?'selected':''?>>
            <?= htmlspecialchars($b['name']) ?>
          </option>
        <?php endforeach;?>
      </select>
    </div>

    <!-- Series as free text -->
    <div class="col-md-4">
      <label class="form-label">Serie (ID)</label>
      <input type="text" name="series_id" class="form-control"
             value="<?= htmlspecialchars($tool['series_id']) ?>" required>
    </div>

    <div class="col-md-4">
      <label class="form-label">Código interno</label>
      <input type="text" name="tool_code" class="form-control"
             value="<?= htmlspecialchars($tool['tool_code']) ?>" required>
    </div>
  </div>

  <!-- Nombre y geometría -->
  <div class="row g-2">
    <div class="col-md-6">
      <label class="form-label">Nombre</label>
      <input type="text" name="name" class="form-control"
             value="<?= htmlspecialchars($tool['name']) ?>" required>
    </div>
    <div class="col-md-2">
      <label class="form-label">Ø (mm)</label>
      <input type="number" step="0.001" name="diameter_mm" class="form-control"
             value="<?= $tool['diameter_mm'] ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Filos</label>
      <input type="number" name="flute_count" class="form-control"
             value="<?= $tool['flute_count'] ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Largo total</label>
      <input type="number" step="0.001" name="full_length_mm" class="form-control"
             value="<?= $tool['full_length_mm'] ?>">
    </div>
  </div>

  <!-- Más campos -->
  <div class="row g-2">
    <div class="col-md-3">
      <label class="form-label">Ø Mango</label>
      <input type="number" step="0.001" name="shank_diameter_mm" class="form-control"
             value="<?= $tool['shank_diameter_mm'] ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Largo filo</label>
      <input type="number" step="0.001" name="cut_length_mm" class="form-control"
             value="<?= $tool['cut_length_mm'] ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Helix (°)</label>
      <input type="number" step="0.1" name="helix" class="form-control"
             value="<?= $tool['helix'] ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Recubrimiento</label>
      <input type="text" name="coated" class="form-control"
             value="<?= htmlspecialchars($tool['coated']) ?>">
    </div>
  </div>

  <!-- Tool type / Made in / Material herr. -->
  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label">Tipo</label>
      <select name="tool_type" class="form-select">
        <?php foreach($toolTypes as $t): ?>
          <option value="<?= $t['code'] ?>" <?= $t['code']==$tool['tool_type']?'selected':''?>>
            <?= htmlspecialchars($t['name']) ?>
          </option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Origen</label>
      <input type="text" name="made_in" class="form-control"
             value="<?= htmlspecialchars($tool['made_in']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Material herr.</label>
      <input type="text" name="material" class="form-control"
             value="<?= htmlspecialchars($tool['material']) ?>">
    </div>
  </div>

  <!-- Notes -->
  <div>
    <label class="form-label">Notas</label>
    <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($tool['notes']) ?></textarea>
  </div>

  <!-- Images -->
  <div class="row g-2">
    <div class="col-md-6">
      <label class="form-label">Imagen principal</label>
      <input type="file" name="image" class="form-control">
      <?php if($tool['image']): ?>
        <img src="../<?= htmlspecialchars($tool['image']) ?>" class="img-thumbnail mt-2" style="max-height:80px">
      <?php endif;?>
    </div>
    <div class="col-md-6">
      <label class="form-label">Imagen dimensiones</label>
      <input type="file" name="image_dimensions" class="form-control">
      <?php if($tool['image_dimensions']): ?>
        <img src="../<?= htmlspecialchars($tool['image_dimensions']) ?>" class="img-thumbnail mt-2" style="max-height:80px">
      <?php endif;?>
    </div>
  </div>

  <hr>
  <h4>✅ Estrategias compatibles</h4>
  <div class="d-flex flex-wrap gap-3 mb-3">
    <?php foreach ($strategies as $s): ?>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="strategies[]" value="<?= $s['strategy_id'] ?>"
          <?= in_array($s['strategy_id'],$selectedStrategies)?'checked':'' ?>>
        <label class="form-check-label"><?= htmlspecialchars($s['name']) ?></label>
      </div>
    <?php endforeach; ?>
  </div>

  <hr>
  <h5>📐 Parámetros por Material</h5>
  <table class="table table-bordered" id="paramTable">
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
    <tbody>
      <?php foreach ($params as $row): ?>
        <tr>
          <td>
            <select name="materials[<?= $row['material_id'] ?>][material_id]" class="form-select">
              <?php foreach ($materials as $m): ?>
                <option value="<?= $m['material_id'] ?>"
                  <?= $m['material_id']==$row['material_id']?'selected':'' ?>>
                  <?= htmlspecialchars($m['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <?php for ($i = 1; $i <= 3; $i++): ?>
              <i class="bi bi-star star <?= $i <= $row['rating'] ? 'filled' : '' ?>" data-value="<?= $i ?>"></i>
            <?php endfor; ?>
            <input type="hidden" name="materials[<?= $row['material_id'] ?>][rating]" value="<?= $row['rating'] ?>">
          </td>
          <td><input type="number" step="0.01" name="materials[<?= $row['material_id'] ?>][vc_m_min]" class="form-control" value="<?= $row['vc_m_min'] ?>"></td>
          <td><input type="number" step="0.01" name="materials[<?= $row['material_id'] ?>][fz_min_mm]" class="form-control" value="<?= $row['fz_min_mm'] ?>"></td>
          <td><input type="number" step="0.01" name="materials[<?= $row['material_id'] ?>][fz_max_mm]" class="form-control" value="<?= $row['fz_max_mm'] ?>"></td>
          <td><input type="number" step="0.01" name="materials[<?= $row['material_id'] ?>][ap_slot_mm]" class="form-control" value="<?= $row['ap_slot_mm'] ?>"></td>
          <td><input type="number" step="0.01" name="materials[<?= $row['material_id'] ?>][ae_slot_mm]" class="form-control" value="<?= $row['ae_slot_mm'] ?>"></td>
          <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">✖</button></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <button type="button" class="btn btn-success btn-sm" onclick="addParamRow()">➕ Agregar</button>

  <div class="text-end mt-4">
    <button class="btn btn-primary">💾 Guardar</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
  </div>
</form>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).on('click', '.star', function() {
  const $star = $(this);
  const $td = $star.closest('td');
  const value = parseInt($star.data('value'));
  $td.find('.star').each(function() {
    const v = parseInt($(this).data('value'));
    $(this).toggleClass('filled', v <= value);
  });
  $td.find('input[type=hidden]').val(value);
});

// Función para añadir fila nueva
const materials = <?= json_encode($materials) ?>;
function addParamRow() {
  const id = Date.now();
  const opts = materials.map(m => `<option value="${m.material_id}">${m.name}</option>`).join('');
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

// Series dinámicas
function loadSeries(brandId) {
  $.getJSON('../api_series.php', { brand_id: brandId }, r => {
    const $s = $('#series').empty();
    r.forEach(v => $s.append(`<option value="${v.id}">${v.code}</option>`));
    $s.val(<?= json_encode($tool['series_id']) ?>);
  });
}
$('#brand').on('change', e => loadSeries(e.target.value));
$(function(){ loadSeries($('#brand').val()); });
</script>

<?php include '../../includes/footer.php'; ?>
