<?php
// admin/tools.php
require_once __DIR__ . '/../includes/auth.php';
include __DIR__ . '/header.php';
?>
<!-- Bootstrap & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  .sidebar { min-width:260px; max-width:260px; }
  .facet-header { cursor:pointer; font-weight:600; font-size:.85rem; padding:.4rem .5rem; border-bottom:1px solid #dee2e6; background:#f8f9fa; }
  .facet-body { max-height:220px; overflow:auto; padding:.25rem .5rem; display:none; }
  .facet-body.show { display:block; }
  .facet-body label { display:block; font-size:.8rem; white-space:nowrap; }
  .facet-search { width:100%; margin-bottom:.25rem; font-size:.75rem; }
  .thumb { width:34px; height:34px; object-fit:contain; border:1px solid #ddd; }
  .thumb:hover { transform:scale(2); position:relative; z-index:10; box-shadow:0 0 4px #000; }
  .alert-no-brand { margin:.5rem; color:#c00; font-weight:600; text-align:center; }
  .stars { color: #0d6efd; }
</style>

<div class="container-fluid py-4">
  <h2 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> Explorador de fresas</h2>
  <div class="row">
    <aside class="col-md-3 sidebar">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white py-2">
          <i class="bi bi-funnel"></i> Filtros
        </div>
        <div id="facetBox"></div>
        <div id="brandWarning" class="alert-no-brand" style="display:none">
          ⚠ No se seleccionó ninguna marca.
        </div>
      </div>
    </aside>
    <main class="col-md-9">
      <div class="input-group mb-2">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input id="qBox" class="form-control" placeholder="Buscar nombre / código…">
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle" id="toolTbl">
          <thead class="table-light">
            <tr>
              <th></th><th>Marca</th><th>Código</th><th>Nombre</th><th>Ø</th><th>Filos</th><th>Tipo</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
const facetBox     = document.getElementById('facetBox');
const brandWarning = document.getElementById('brandWarning');
const qBox         = document.getElementById('qBox');

fetch('tools_facets.php')
  .then(r => r.json())
  .then(f => {
    const facets = {
      brand                : ['Marca',             {SGS:'SGS',MAYKESTAG:'Maykestag',SCHNEIDER:'Schneider',GENERICO:'Genérico'}],
      series_id            : ['Serie',             f.series_id],
      diameter_mm          : ['Ø (mm)',            f.diameter_mm],
      shank_diameter_mm    : ['Diámetro del cabo', f.shank_diameter_mm],
      flute_length_mm      : ['Largo útil',        f.flute_length_mm],
      full_length_mm       : ['Largo total',       f.full_length_mm],
      cut_length_mm        : ['Largo del filo',    f.cut_length_mm],
      flute_count          : ['Filos',             f.flute_count],
      tool_type            : ['Tipo',              f.tool_type],
      material             : ['Material herr.',    f.material],
      material_id          : ['Material mecanizable', f.material_id],
      radius               : ['Radio',             f.radius],
      conical_angle        : ['Ángulo de conicidad', f.conical_angle],
      coated               : ['Recubrimiento',     f.coated],
      strategy_id          : ['Estrategia',        f.strategy_id],
      made_in              : ['Origen',            f.made_in]
    };

    Object.entries(facets).forEach(([field, [label, raw]]) => {
      const data = typeof raw === 'object' && !Array.isArray(raw)
        ? raw
        : Object.fromEntries((raw ?? []).map(v => [v,v]));

      const id = 'facet_' + field;
      facetBox.insertAdjacentHTML('beforeend', `
        <div>
          <div class="facet-header" data-bs-target="#${id}">
            ${label} <i class="bi bi-caret-down"></i>
          </div>
          <div id="${id}" class="facet-body">
            <input class="form-control form-control-sm facet-search" placeholder="Buscar…">
            ${Object.entries(data).map(([v, txt]) => `
              <label><input type="checkbox" name="${field}" value="${v}"> ${txt}</label>
            `).join('')}
          </div>
        </div>`);

      const header = facetBox.querySelector(`[data-bs-target="#${id}"]`);
      header.addEventListener('click', () => {
        const body = document.getElementById(id);
        body.classList.toggle('show');
        header.querySelector('i').classList.toggle('bi-caret-up');
      });

      facetBox.querySelectorAll(`#${id} .facet-search`).forEach(si =>
        si.addEventListener('input', () => {
          const q = si.value.toLowerCase();
          facetBox.querySelectorAll(`#${id} label`).forEach(l =>
            l.style.display = l.textContent.toLowerCase().includes(q) ? '' : 'none'
          );
        })
      );

      facetBox.querySelectorAll(`#${id} input[type=checkbox]`).forEach(cb =>
        cb.addEventListener('change', applyFilter)
      );
    });

    document.querySelectorAll('input[name="brand"]').forEach(cb => cb.checked = true);
    loadTable();
  });

qBox.addEventListener('input', debounce(applyFilter, 300));

function applyFilter() {
  const sel = [...document.querySelectorAll('input[name="brand"]:checked')];
  if (!sel.length) {
    brandWarning.style.display = 'block';
    document.querySelector('#toolTbl tbody').innerHTML = '';
    return;
  }
  brandWarning.style.display = 'none';

  const fd = new FormData();
  facetBox.querySelectorAll('input[type=checkbox]:checked').forEach(cb =>
    fd.append(cb.name + '[]', cb.value)
  );
  fd.append('q', qBox.value.trim());
  loadTable('?' + new URLSearchParams(fd).toString());
}

async function loadTable(q = '') {
  const res = await fetch('tools_ajax.php' + q);
  const arr = await res.json();
  const tb = document.querySelector('#toolTbl tbody');
  tb.innerHTML = '';

  arr.forEach(t => {
    const rowId = 'r' + t.tbl + t.tool_id;
    tb.insertAdjacentHTML('beforeend', `
      <tr>
        <td class="d-flex">
          <button class="btn btn-sm btn-outline-secondary toggle-details" data-target="#${rowId}">
            <i class="bi bi-eye"></i>
          </button>
          <a href="tool_edit.php?tbl=${t.tbl}&id=${t.tool_id}"
             class="btn btn-sm btn-outline-primary ms-1">
            <i class="bi bi-pencil"></i>
          </a>
        </td>
        <td><span class="badge bg-info text-dark">${t.brand}</span></td>
        <td>${t.tool_code}</td>
        <td class="text-truncate" style="max-width:200px">
          ${t.details.image ? `<img src="../panel/${t.details.image}" class="thumb">` : ``}
          ${t.name}
        </td>
        <td>${t.diameter_mm}</td>
        <td>${t.flute_count}</td>
        <td>${t.tool_type}</td>
      </tr>
      <tr class="collapse" id="${rowId}">
        <td colspan="7" class="bg-light">
          <div class="p-3">
            <div class="row g-1 mb-2">
              ${Object.entries(t.details).map(([k, v]) => `
                <div class="col-6 col-md-3">
                  <small class="text-muted">${k}</small><br>${v ?? '-'}</div>`).join('')}
            </div>
            <h6>Estrategias compatibles</h6>
            <div class="d-flex flex-wrap gap-2 mb-3">
              ${t.strategies.map(s => `<span class="badge bg-secondary">${s}</span>`).join('')}
            </div>
            <h6>Parámetros de corte</h6>
            ${t.params.length ? `
              <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                  <tr><th>Material</th><th>Rating</th><th>Vc</th><th>Fz min</th><th>Fz max</th><th>ap</th><th>ae</th></tr>
                </thead>
                <tbody>
                  ${t.params.map(p => `
                    <tr>
                      <td>${p.material}</td>
                      <td><span class="stars">★★★</span></td>
                      <td>${p.vc}</td>
                      <td>${p.fzmin}</td>
                      <td>${p.fzmax}</td>
                      <td>${p.ap}</td>
                      <td>${p.ae}</td>
                    </tr>`).join('')}
                </tbody>
              </table>`
              : '<em>Sin parámetros</em>'}
          </div>
        </td>
      </tr>`);
  });

  document.querySelectorAll('.toggle-details').forEach(btn => {
    btn.addEventListener('click', () => {
      const tgt = document.querySelector(btn.dataset.target);
      const bs = bootstrap.Collapse.getInstance(tgt) || new bootstrap.Collapse(tgt, { toggle: false });
      bs.toggle();
    });
  });
}
</script>