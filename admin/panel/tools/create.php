<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
include '../header.php';

/* catálogos */
$brands     = $pdo->query("SELECT id,name FROM brands ORDER BY name")->fetchAll();
$materials  = $pdo->query("SELECT material_id,name FROM materials ORDER BY name")->fetchAll();
$strategies = $pdo->query("SELECT strategy_id,name FROM strategies ORDER BY name")->fetchAll();
$toolTypes  = $pdo->query("SELECT code,name FROM tooltypes ORDER BY name")->fetchAll();
?>

<h2>🆕 Crear Nueva Fresa</h2>

<form method="POST" action="save.php" class="vstack gap-3">

<input type="hidden" name="tool_id" value="">

<!-- Marca / Serie / Código -->
<div class="row g-2">
  <div class="col-md-4">
    <label>Marca</label>
    <select id="brand" name="brand_id" class="form-select" required>
      <?php foreach ($brands as $b): ?>
        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-4">
    <label>Serie</label>
    <select id="series" name="series_id" class="form-select" required></select>
  </div>

  <div class="col-md-4">
    <label>Código interno</label>
    <input name="tool_code" class="form-control" required>
  </div>
</div>

<!-- Geometría básica -->
<div class="row g-2">
  <div class="col-md-2"><label>Nombre</label><input name="name" class="form-control" required></div>
  <div class="col-md-2"><label>Ø (mm)</label><input type="number" step="0.01" name="diameter_mm" class="form-control" required></div>
  <div class="col-md-2"><label>Filos</label><input type="number" name="flute_count" class="form-control" required></div>
  <div class="col-md-2"><label>D1 (mm)</label><input type="number" step="0.01" name="d1_mm" class="form-control"></div>
  <div class="col-md-2"><label>L2 (mm)</label><input type="number" step="0.01" name="l2_mm" class="form-control"></div>
</div>

<div class="row g-2">
  <div class="col-md-2"><label>Largo total</label><input type="number" step="0.01" name="overall_length_mm" class="form-control"></div>
  <div class="col-md-2"><label>Ø Mango</label><input type="number" step="0.01" name="shank_diameter_mm" class="form-control"></div>
  <div class="col-md-4">
    <label>Tipo de fresa</label>
    <select name="tool_type" class="form-select">
      <?php foreach ($toolTypes as $t): ?>
        <option value="<?= $t['code'] ?>"><?= htmlspecialchars($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4"><label>Material principal</label><input name="material" class="form-control"></div>
</div>

<label class="mt-2">Notas</label>
<textarea name="notes" class="form-control"></textarea>

<hr>
<h4>✅ Estrategias compatibles</h4>
<div class="d-flex flex-wrap gap-3">
  <?php foreach ($strategies as $s): ?>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="strategy_ids[]" value="<?= $s['strategy_id'] ?>">
      <label class="form-check-label"><?= htmlspecialchars($s['name']) ?></label>
    </div>
  <?php endforeach; ?>
</div>

<hr>
<h4>📐 Parámetros por Material</h4>
<table class="table table-sm table-bordered" id="materialTable">
  <thead class="table-light">
    <tr><th>Material</th><th>Vc</th><th>Fz min</th><th>Fz max</th><th>ap</th><th>ae</th><th>Eliminar</th></tr>
  </thead>
  <tbody></tbody>
</table>
<button type="button" class="btn btn-success btn-sm" onclick="addMaterial()">➕ Agregar Material</button>

<div class="d-flex justify-content-end mt-4 gap-2">
  <button class="btn btn-primary">💾 Guardar Fresa</button>
  <a href="../dashboard.php" class="btn btn-secondary">Cancelar</a>
</div>
</form>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
/* cargar series dinámicamente */
function loadSeries(brand){
  $.getJSON('../api_series.php',{brand_id:brand}, r=>{
    const sel=$("#series").empty();
    r.forEach(s=>sel.append(`<option value='${s.id}'>${s.code}</option>`));
  });
}
$("#brand").on("change",e=>loadSeries(e.target.value));
$(function(){ loadSeries($("#brand").val()); });

/* tabla de parámetros de corte */
function addMaterial(){
  const tb=document.querySelector("#materialTable tbody");
  const id=Date.now();
  const mats=<?= json_encode($materials) ?>;
  const opts=mats.map(m=>`<option value='${m.material_id}'>${m.name}</option>`).join('');
  tb.insertAdjacentHTML('beforeend',`
    <tr>
      <td><select name="materials[new_${id}][material_id]" class="form-select">${opts}</select></td>
      <td><input name="materials[new_${id}][vc_m_min]"   type="number" step="0.01" class="form-control"></td>
      <td><input name="materials[new_${id}][fz_min_mm]"  type="number" step="0.01" class="form-control"></td>
      <td><input name="materials[new_${id}][fz_max_mm]"  type="number" step="0.01" class="form-control"></td>
      <td><input name="materials[new_${id}][ap_slot_mm]" type="number" step="0.01" class="form-control"></td>
      <td><input name="materials[new_${id}][ae_slot_mm]" type="number" step="0.01" class="form-control"></td>
      <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">✖</button></td>
    </tr>`);
}
</script>

<?php include '../footer.php'; ?>

