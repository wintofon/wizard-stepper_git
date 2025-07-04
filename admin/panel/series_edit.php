<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
include __DIR__.'/header.php';

$series = $pdo->query("SELECT id, code FROM series ORDER BY code")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();
$parents = $pdo->query("SELECT category_id, name FROM materialcategories WHERE parent_id IS NULL ORDER BY name")->fetchAll();
$mats = $pdo->query("SELECT m.material_id, m.name, c.parent_id
                     FROM materials m
                     LEFT JOIN materialcategories c ON m.category_id = c.category_id
                     ORDER BY m.name")->fetchAll();
$strats = $pdo->query("SELECT strategy_id, name FROM strategies ORDER BY name")->fetchAll();
$materialNames = [];
$materialParents = [];
foreach ($mats as $m) {
    $materialNames[$m['material_id']] = $m['name'];
    $materialParents[$m['material_id']] = $m['parent_id'];
}
$seriesId = $_GET['id'] ?? '';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  .thumb { width: 40px; height: 40px; object-fit: contain; border: 1px solid #ccc; }
  .thumb:hover { transform: scale(2); z-index: 30; position: relative; box-shadow: 0 0 6px #000; }
  .sortable { cursor: pointer; }
  .table-wrap { overflow-x: auto; }
  tr.alt { background: #e9f3fb; }
  tr.tool-strategies { display: none; }
  .star { cursor:pointer; color:#ddd; font-size:1.2rem; }
  .star.filled { color:#0d6efd; }
  .rating-stars { white-space:nowrap; }
</style>

<div class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <a href="dashboard.php" class="btn btn-outline-secondary">← Volver al panel</a>
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

    <div id="geoWrap" class="table-wrap mb-4">
      <div class="d-flex justify-content-end mb-2">
        <button id="editGeo" type="button" class="btn btn-outline-primary btn-sm me-2">Editar</button>
        <button id="saveGeo" type="button" class="btn btn-success btn-sm" style="display:none">Guardar</button>
      </div>
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
      <button id="addTool" type="button" class="btn btn-outline-primary btn-sm" style="display:none">➕ Agregar fresa</button>
    </div>

    <div class="mb-3">
      <label class="form-label me-2">Filtrar por padre de material</label>
      <select id="parentFilter" class="form-select form-select-sm d-inline-block w-auto">
        <option value="">-- todas --</option>
        <?php foreach($parents as $p): ?>
          <option value="<?= $p['category_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div id="bulkOps" class="mb-3">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <span>Global:</span>
        <?php foreach($strats as $s): ?>
          <label class="form-check form-check-inline mb-0">
            <input type="checkbox" class="form-check-input bulk-strategy" value="<?= $s['strategy_id'] ?>">
            <span class="form-check-label"><?= htmlspecialchars($s['name']) ?></span>
          </label>
        <?php endforeach; ?>
        <select id="bulkRating" class="form-select form-select-sm w-auto">
          <option value="">Rating</option>
          <?php for($i=1;$i<=3;$i++): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
          <?php endfor; ?>
        </select>
        <button type="button" id="applyBulk" class="btn btn-outline-primary btn-sm">Aplicar</button>
      </div>
    </div>

    <div id="materialsWrap"></div>
    <button id="addMat" type="button" class="btn btn-outline-success btn-sm mb-5" style="display:none">➕ Agregar material</button>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const catalogStrats = <?= json_encode(array_column($strats,'name','strategy_id')) ?>;
const materials = <?= json_encode($materialNames) ?>;
const materialParents = <?= json_encode($materialParents) ?>;
let counter = 0;
let matCounter = 0;
const allMaterialOptions = Object.entries(materials)
  .map(([id, name]) => `<option value="${id}">${name}</option>`)
  .join('');

function updateMaterialSelects(pid) {
  $('#materialsWrap select[name$="[material_id]"]').each(function () {
    const selected = $(this).val();
    const opts = Object.entries(materials)
      .filter(([id]) => !pid || materialParents[id] == pid || id === selected)
      .map(([id, name]) => `<option value="${id}"${id === selected ? ' selected' : ''}>${name}</option>`)
      .join('');
    $(this).html(opts);
  });
}

function toggleGeo(edit){
  $('#geoTbl input, #geoTbl select, #geoTbl textarea, #geoTbl button.delTool').prop('disabled', !edit);
  $('#addTool').toggle(edit);
  $('#saveGeo').toggle(edit);
  $('#editGeo').toggle(!edit);
  checkAddMat();
}

function toggleMat($blk, edit){
  $blk.find('input,select,textarea,button.delMat').prop('disabled', !edit);
  $blk.find('.saveMat').toggle(edit);
  $blk.find('.editMat').toggle(!edit);
  checkAddMat();
}

function checkAddMat(){
  if($('#saveGeo').is(':visible') || $('.saveMat:visible').length){
    $('#addMat').show();
  }else{
    $('#addMat').hide();
  }
}

function saveAll($btn, after){
  if(!confirm('¿Guardar cambios?')) return;
  $btn.prop('disabled', true);
  $.post('series_save.php', $('#seriesForm').serialize(), function(res){
    if(res.success){
      alert('Datos guardados');
      if(after) after();
    }else{
      alert('Error: '+ (res.error || ''));
    }
  }, 'json').fail(function(jqXHR){
    let msg = 'Error de conexión';
    if(jqXHR.responseText){
      msg += ': ' + jqXHR.responseText.trim();
    }
    alert(msg);
  }).always(function(){ $btn.prop('disabled', false); });
}

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
    <tr class="${rowClass} tool-strategies">${stratHTML}</tr>`;
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
    const ratingStars = [1,2,3].map(i=>`<i class="bi bi-star star ${i<=data.rating?'filled':''}" data-value="${i}"></i>`).join('');
    const matName = materials[mid] || mid;
    $wrap.append(`
      <div class="mb-4 mat-block" data-parent="${materialParents[mid]||''}">
        <div class="d-flex align-items-center mb-2">
          <strong class="me-2">${matName}</strong>
          <span class="rating-stars me-2">${ratingStars}<input type="hidden" name="materials[${mid}][rating]" value="${data.rating}"></span>
          <button type="button" class="btn btn-outline-primary btn-sm me-2 editMat">Editar</button>
          <button type="button" class="btn btn-success btn-sm saveMat" style="display:none">Guardar</button>
        </div>
        <div class="mb-2"><strong>Estrategias:</strong> ${stratOpts}</div>
        <table class="table table-sm table-bordered">
          <thead class="table-light"><tr><th>Ø</th>${hdr.map(h=>`<th>${h}</th>`).join('')}</tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`);
    const $blk = $('#materialsWrap .mat-block:last');
    $blk.find('select[name$="[material_id]"]').on('change', function(){
      const mid2 = $(this).val();
      $blk.attr('data-parent', materialParents[mid2]||'');
      $('#parentFilter').trigger('change');
    });
    $('#parentFilter').trigger('change');
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
    toggleGeo(false);
    $('#materialsWrap .mat-block').each(function(){ toggleMat($(this), false); });
    checkAddMat();
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
  saveAll($(this));
});

$('#editGeo').on('click', function(){
  toggleGeo(true);
  checkAddMat();
});

$('#saveGeo').on('click', function(){
  const $btn = $(this);
  saveAll($btn, () => toggleGeo(false));
  checkAddMat();
});

$(document).on('click', '.editMat', function(){
  const $blk = $(this).closest('.mat-block');
  toggleMat($blk, true);
  checkAddMat();
});

$(document).on('click', '.saveMat', function(){
  const $blk = $(this).closest('.mat-block');
  const $btn = $(this);
  saveAll($btn, () => toggleMat($blk, false));
  checkAddMat();
});

// agregar bloque de material vacío
$('#addMat').on('click', function(){
  const mid = 'new_' + (++matCounter);
  const opts = allMaterialOptions;
  const ratingStars = [1,2,3].map(i=>`<i class="bi bi-star star" data-value="${i}"></i>`).join('');
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
  const html = `
    <div class="mb-4 mat-block" data-parent="">
      <div class="d-flex align-items-center mb-2">
        <select name="materials[${mid}][material_id]" class="form-select me-2 w-auto">${opts}</select>
        <span class="rating-stars me-2">${ratingStars}<input type="hidden" name="materials[${mid}][rating]" value="0"></span>
        <button type="button" class="btn btn-outline-primary btn-sm me-2 editMat">Editar</button>
        <button type="button" class="btn btn-success btn-sm saveMat" style="display:none">Guardar</button>
        <button type="button" class="btn btn-outline-danger btn-sm delMat">✖</button>
      </div>
      <div class="mb-2"><strong>Estrategias:</strong> ${stratOpts}</div>
      <table class="table table-sm table-bordered">
        <thead class="table-light"><tr><th>Ø</th>${hdr.map(h=>`<th>${h}</th>`).join('')}</tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
  const $blk = $(html).appendTo('#materialsWrap');
  $blk.find('select[name$="[material_id]"]').on('change', function(){
    const mid2 = $(this).val();
    $blk.attr('data-parent', materialParents[mid2]||'');
    $('#parentFilter').trigger('change');
  });
  toggleMat($blk, true);
  checkAddMat();
  $('#parentFilter').trigger('change');
});

$(document).on('click', '.delMat', function(){
  $(this).closest('.mat-block').remove();
  checkAddMat();
});

// al cambiar estrategia de material, aplicar a todas las fresas
$(document).on('change', '.mat-strategy', function(){
  const sid = $(this).val();
  const checked = $(this).is(':checked');
  $('#geoBody input[type=checkbox][value="'+sid+'"]').prop('checked', checked);
});

// rating con estrellas
$(document).on('click', '.rating-stars .star', function(){
  const $s = $(this);
  const $wrap = $s.closest('.rating-stars');
  const val = parseInt($s.data('value'));
  $wrap.find('.star').each(function(){
    $(this).toggleClass('filled', parseInt($(this).data('value')) <= val);
  });
  $wrap.find('input[type=hidden]').val(val);
});

// filtro por padre
$('#parentFilter').on('change', function(){
  const pid = $(this).val();
  updateMaterialSelects(pid);
  $('.mat-block').each(function(){
    const p = $(this).data('parent');
    if(!pid || pid==p){
      $(this).show();
    } else {
      $(this).hide();
    }
  });
});

// aplicar cambios globales
$('#applyBulk').on('click', function(){
  if(!confirm('¿Aplicar cambios globales?')) return;
  const rating = $('#bulkRating').val();
  if(rating){
    $('.rating-stars').each(function(){
      const $r = $(this);
      $r.find('input[type=hidden]').val(rating);
      $r.find('.star').each(function(){
        $(this).toggleClass('filled', parseInt($(this).data('value')) <= rating);
      });
    });
  }
  $('#bulkOps input.bulk-strategy').each(function(){
    const sid = $(this).val();
    const checked = $(this).is(':checked');
    $('.mat-block input.mat-strategy[value="'+sid+'"]').prop('checked', checked).trigger('change');
  });
});

// agregar fresa vacía
$('#addTool').on('click', function(){
  const id = 'new_' + (++counter);
  const alt = ($('#geoBody tr').length/2)%2===1;
  $('#geoBody').append( geoRow({tool_id:id}, alt) );
});
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
