<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
include __DIR__.'/header.php';

$series = $pdo->query("SELECT id, code FROM series ORDER BY code")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();
$types  = $pdo->query("SELECT code, name FROM tooltypes ORDER BY name")->fetchAll();
$mats   = $pdo->query("SELECT material_id, name FROM materials ORDER BY name")->fetchAll();
$strats = $pdo->query("SELECT strategy_id, name FROM strategies ORDER BY name")->fetchAll();
$seriesId = $_GET['id'] ?? '';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  .thumb { width: 40px; height: 40px; object-fit: contain; border: 1px solid #ccc; }
  .thumb:hover { transform: scale(2); z-index: 30; position: relative; box-shadow: 0 0 6px #000; }
  .sortable { cursor: pointer; }
  .table-wrap { overflow-x: auto; }
  tr.alt { background: #e9f3fb; }
</style>

<div class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <a href="dashboard.php" class="btn btn-outline-secondary">← Volver</a>
    <div class="d-flex gap-3">
      <select id="seriesSel" class="form-select">
        <option value="">-- elige serie --</option>
        <?php foreach($series as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $s['id'] == $seriesId ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['code']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button id="saveBtn" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
    </div>
  </div>

  <form id="seriesForm">
    <input type="hidden" name="series_id" id="series_id" value="<?= htmlspecialchars($seriesId) ?>">

    <div class="border rounded p-3 mb-4">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Marca</label>
          <select name="brand_id" class="form-select" required>
            <?php foreach($brands as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo</label>
          <select name="tool_type" class="form-select">
            <?php foreach($types as $t): ?>
              <option value="<?= htmlspecialchars($t['code']) ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="table-wrap mb-4">
      <table id="geoTbl" class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <?php
              $hdr = ['Ø','Código','⌀ cabo','L útil','L filo','L total','∠ cónico','Filos','Radio','Rack','Helix','Mat.','Origen','Recub.','✖'];
              foreach($hdr as $h) echo "<th class='sortable'>".htmlspecialchars($h)."</th>";
            ?>
          </tr>
        </thead>
        <tbody id="geoBody"></tbody>
      </table>
      <button id="addTool" type="button" class="btn btn-outline-primary btn-sm">➕ Agregar fresa</button>
    </div>

    <div id="materialsWrap"></div>
    <button id="addMat" type="button" class="btn btn-outline-success btn-sm mb-5">➕ Agregar material</button>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const catalogStrats = <?= json_encode(array_column($strats,'name','strategy_id')) ?>;
let counter = 0;

// genera una fila + la de estrategias debajo
function geoRow(t, alt) {
  const id = t.tool_id;
  const pre = `tools[${id}]`;
  const rowClass = alt ? 'alt' : '';
  const strategies = (t.strategy_ids||'').split(',').filter(x=>x);

  let stratHTML = `<td colspan="15"><strong>Estrategias:</strong><div class="row">`;
  for (let sid in catalogStrats) {
    const checked = strategies.includes(sid) ? 'checked' : '';
    stratHTML += `<div class="col-auto form-check form-check-inline">
      <input type="checkbox" class="form-check-input" name="${pre}[strategies][]" value="${sid}" ${checked}>
      <label class="form-check-label">${catalogStrats[sid]}</label>
    </div>`;
  }
  stratHTML += `</div><div class="mt-2"><strong>Imagen:</strong>
    <input name="${pre}[image]" class="form-control d-inline w-auto" value="${t.image||''}">
  </div></td>`;

  return `
    <tr class="${rowClass}" data-tid="${id}">
      <td><input name="${pre}[diameter_mm]" class="form-control" value="${t.diameter_mm||''}"></td>
      <td><input name="${pre}[tool_code]" class="form-control" value="${t.tool_code||''}"></td>
      <td><input name="${pre}[shank_diameter_mm]" class="form-control" value="${t.shank_diameter_mm||''}"></td>
      <td><input name="${pre}[flute_length_mm]" class="form-control" value="${t.flute_length_mm||''}"></td>
      <td><input name="${pre}[cut_length_mm]" class="form-control" value="${t.cut_length_mm||''}"></td>
      <td><input name="${pre}[full_length_mm]" class="form-control" value="${t.full_length_mm||''}"></td>
      <td><input name="${pre}[conical_angle]" class="form-control" value="${t.conical_angle||''}"></td>
      <td><input name="${pre}[flute_count]" class="form-control" value="${t.flute_count||''}"></td>
      <td><input name="${pre}[radius]" class="form-control" value="${t.radius||''}"></td>
      <td><input name="${pre}[rack_angle]" class="form-control" value="${t.rack_angle||''}"></td>
      <td><input name="${pre}[helix]" class="form-control" value="${t.helix||''}"></td>
      <td><input name="${pre}[material]" class="form-control" value="${t.material||''}"></td>
      <td><input name="${pre}[made_in]" class="form-control" value="${t.made_in||''}"></td>
      <td><input name="${pre}[coated]" class="form-control" value="${t.coated||''}"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger delTool">✖</button></td>
    </tr>
    <tr class="${rowClass}">${stratHTML}</tr>`;
}

// al cambiar serie, pido el JSON y dibujo filas
$('#seriesSel').on('change', function(){
  const sid = $(this).val();
  $('#series_id').val(sid);
  $('#geoBody').empty();
  if (!sid) return;
  $.getJSON('series_ajax.php', { series_id: sid }, function(res){
    (res.tools||[]).forEach((t,i)=> {
      $('#geoBody').append( geoRow(t, i%2===1) );
    });
  });
});

// botón de borrar fila
$(document).on('click', '.delTool', function(){
  const tr = $(this).closest('tr');
  tr.next('tr').remove();
  tr.remove();
});

// agregar fresa vacía
$('#addTool').on('click', function(){
  const id = 'new_' + (++counter);
  const alt = ($('#geoBody tr').length/2)%2===1;
  $('#geoBody').append( geoRow({tool_id:id}, alt) );
});

// carga inicial si hay una serie seleccionada
$(function(){
  if ($('#seriesSel').val()) {
    $('#seriesSel').trigger('change');
  }
});
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
