<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
include __DIR__.'/header.php';

$series = $pdo->query("SELECT id, code FROM series ORDER BY code")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();
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
      <button id="searchBtn" class="btn btn-primary" style="display:none">
        <i class="bi bi-search"></i> Buscar fresas
      </button>
      <button id="saveBtn" class="btn btn-success"><i class="bi bi-save"></i> Guardar</button>
    </div>
  </div>

  <form id="seriesForm">
    <input type="hidden" name="series_id" id="series_id" value="<?= htmlspecialchars($seriesId) ?>">

    <div class="border rounded p-3 mb-4">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Marca</label>
          <select id="brandSel" name="brand_id" class="form-select" required>
            <?php foreach($brands as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
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
const materials = <?= json_encode(array_column($mats,'name','material_id')) ?>;
let counter = 0;
let matCounter = 0;

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

function loadSeries(brandId, selected){
  $('#seriesSel').html('<option value="">-- elige serie --</option>');
  if(!brandId) return;
  $.getJSON('api_series.php', { brand_id: brandId }, function(list){
    list.forEach(s => {
      const opt = $('<option>').val(s.id).text(s.code);
      if(selected && selected==s.id) opt.prop('selected', true);
      $('#seriesSel').append(opt);
    });
  });
}

function renderParams(params, tools){
  const $wrap = $('#materialsWrap').empty();
  const cols = ['vc','fz_min','fz_max','ap','ae'];
  const hdr = ['Vc','Fz min','Fz max','ap','ae'];
  const stratOpts = Object.entries(catalogStrats)
    .map(([sid,name]) => `<div class="form-check form-check-inline">
        <input type="checkbox" class="form-check-input mat-strategy" value="${sid}">
        <label class="form-check-label">${name}</label>
      </div>`).join('');
  Object.entries(params).forEach(([mid, data]) => {
    if(!data || data.rating <= 0) return;
    let rows = '';
    tools.forEach(t => {
      const r = data.rows && data.rows[t.tool_id] ? data.rows[t.tool_id] : {};
      rows += `<tr><td>${t.diameter_mm||''}</td>`+
        cols.map(c=>`<td><input name="materials[${mid}][rows][${t.tool_id}][${c}]" class="form-control form-control-sm" value="${r[c]??''}"></td>`).join('')+
        `</tr>`;
    });
    const ratingSel = [1,2,3].map(i=>`<option value="${i}" ${i==data.rating?'selected':''}>${i}</option>`).join('');
    const matName = materials[mid] || mid;
    $wrap.append(`
      <div class="mb-4 mat-block">
        <div class="d-flex align-items-center mb-2">
          <strong class="me-2">${matName}</strong>
          <select name="materials[${mid}][rating]" class="form-select form-select-sm w-auto me-2">${ratingSel}</select>
        </div>
        <div class="mb-2"><strong>Estrategias:</strong> ${stratOpts}</div>
        <table class="table table-sm table-bordered">
          <thead class="table-light"><tr><th>Ø</th>${hdr.map(h=>`<th>${h}</th>`).join('')}</tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`);
  });
}

function fetchSeriesTools(){
  const sid = $('#seriesSel').val();
  if (!sid) return;
  $('#geoBody').empty();
  $('#materialsWrap').empty();
  $.getJSON('series_ajax.php', { series_id: sid }, function(res){
    $('#brandSel').val(res.brand_id);
    loadSeries(res.brand_id, sid);
    (res.tools||[]).forEach((t,i)=> {
      $('#geoBody').append( geoRow(t, i%2===1) );
    });
    renderParams(res.params||{}, res.tools||[]);
  });
}

// al cambiar serie mostrar y cargar fresas
$('#seriesSel').on('change', function(){
  const sid = $(this).val();
  $('#series_id').val(sid);
  if (sid) {
    $('#searchBtn').show();
    fetchSeriesTools();
  } else {
    $('#geoBody').empty();
    $('#materialsWrap').empty();
    $('#searchBtn').hide();
  }
});

// buscar fresas de la serie seleccionada
$('#searchBtn').on('click', function(e){
  e.preventDefault();
  fetchSeriesTools();
});

// cambio de marca => cargar series
$('#brandSel').on('change', function(){
  loadSeries(this.value);
  $('#seriesSel').val('');
  $('#geoBody').empty();
  $('#materialsWrap').empty();
  $('#searchBtn').hide();
});

$(function(){
  const sid = $('#seriesSel').val();
  if(sid){
    $('#searchBtn').show();
    fetchSeriesTools();
  } else {
    loadSeries($('#brandSel').val());
    $('#searchBtn').hide();
  }
});

// botón de borrar fila
$(document).on('click', '.delTool', function(){
  const tr = $(this).closest('tr');
  tr.next('tr').remove();
  tr.remove();
});

// guardar serie y parámetros
$('#saveBtn').on('click', function(e){
  e.preventDefault();
  const $btn = $(this).prop('disabled', true);
  $.post('series_save.php', $('#seriesForm').serialize(), function(res){
    if(res.success){
      alert('Datos guardados');
    }else{
      alert('Error: '+ (res.error || ''));}
  }, 'json').fail(function(){
    alert('Error de conexión');
  }).always(function(){ $btn.prop('disabled', false); });
});

// agregar bloque de material vacío
$('#addMat').on('click', function(){
  const mid = 'new_' + (++matCounter);
  const opts = Object.entries(materials)
    .map(([id,name]) => `<option value="${id}">${name}</option>`).join('');
  const ratingSel = [1,2,3].map(i=>`<option value="${i}">${i}</option>`).join('');
  const cols = ['vc','fz_min','fz_max','ap','ae'];
  const hdr = ['Vc','Fz min','Fz max','ap','ae'];
  const stratOpts = Object.entries(catalogStrats)
    .map(([sid,name]) => `<div class="form-check form-check-inline">
        <input type="checkbox" class="form-check-input mat-strategy" value="${sid}">
        <label class="form-check-label">${name}</label>
      </div>`).join('');
  let rows = '';
  $('#geoBody tr[data-tid]').each(function(){
    const tid = $(this).data('tid');
    const dia = $(this).find('input[name$="[diameter_mm]"]').val() || '';
    rows += `<tr><td>${dia}</td>`+
      cols.map(c=>`<td><input name="materials[${mid}][rows][${tid}][${c}]" class="form-control form-control-sm"></td>`).join('')+
      `</tr>`;
  });
  $('#materialsWrap').append(`
    <div class="mb-4 mat-block">
      <div class="d-flex align-items-center mb-2">
        <select name="materials[${mid}][material_id]" class="form-select me-2 w-auto">${opts}</select>
        <select name="materials[${mid}][rating]" class="form-select form-select-sm w-auto me-2">${ratingSel}</select>
        <button type="button" class="btn btn-outline-danger btn-sm delMat">✖</button>
      </div>
      <div class="mb-2"><strong>Estrategias:</strong> ${stratOpts}</div>
      <table class="table table-sm table-bordered">
        <thead class="table-light"><tr><th>Ø</th>${hdr.map(h=>`<th>${h}</th>`).join('')}</tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`);
});

$(document).on('click', '.delMat', function(){
  $(this).closest('.mat-block').remove();
});

// al cambiar estrategia de material, aplicar a todas las fresas
$(document).on('change', '.mat-strategy', function(){
  const sid = $(this).val();
  const checked = $(this).is(':checked');
  $('#geoBody input[type=checkbox][value="'+sid+'"]').prop('checked', checked);
});

// agregar fresa vacía
$('#addTool').on('click', function(){
  const id = 'new_' + (++counter);
  const alt = ($('#geoBody tr').length/2)%2===1;
  $('#geoBody').append( geoRow({tool_id:id}, alt) );
});
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
