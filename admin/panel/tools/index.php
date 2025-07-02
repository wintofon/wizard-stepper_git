<?php
require_once '../../includes/auth.php';
include   '../header.php';
?>
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
  .sidebar{min-width:260px;max-width:260px}
  .facet-header{cursor:pointer;font-weight:600;font-size:.85rem;
                padding:.4rem .5rem;border-bottom:1px solid #dee2e6;background:#f8f9fa}
  .facet-body{max-height:220px;overflow:auto;padding:.25rem .5rem;display:none}
  .facet-body.show{display:block}
  .facet-body label{display:block;font-size:.8rem;white-space:nowrap}
  .facet-search{width:100%;margin-bottom:.25rem;font-size:.75rem}
  .thumb{width:40px;height:40px;object-fit:contain;border:1px solid #ddd}
  .thumb:hover{transform:scale(2);position:relative;z-index:10;box-shadow:0 0 4px #000}
  .alert-no-brand{margin:.5rem;color:#c00;font-weight:600;text-align:center}

  /* flechas orden */
  .sort-wrap{display:inline-flex;flex-direction:column;margin-left:2px}
  .sort-icon{cursor:pointer;font-size:.7rem;line-height:.75rem;opacity:.4}
  .sort-icon.active{opacity:1;color:#0d6efd}
  .stars{color:#0d6efd}
</style>

<div class="container-fluid py-4">
  <h2 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> Explorador de fresas</h2>

  <!-- Navegación -->
  <div class="mb-3 d-flex justify-content-between">
    <a href="dashboard.php"  class="btn btn-outline-secondary">← Volver al panel</a>
    <a href="tools_form.php" class="btn btn-success">➕ Nueva Fresa</a>
  </div>

<div class="container-fluid py-4">

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
              <th></th>
              <th data-col="brand">Marca</th>
              <th data-col="series_id">Serie</th>
              <th data-col="img">Img</th>
              <th data-col="tool_code">Código</th>
              <th data-col="name">Nombre</th>
              <th data-col="diameter_mm">Ø</th>
              <th data-col="flute_count">Filos</th>
              <th data-col="tool_type">Tipo</th>
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
/* utils */
const debounce=(fn,ms)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);}};
const facetBox=document.getElementById('facetBox');
const brandWarning=document.getElementById('brandWarning');
const qBox=document.getElementById('qBox');

let toolsData=[];          // cache de resultados
let currentSort={col:null,dir:null}; // estado de orden

/* ------ FACETS (idéntico a antes) ------ */
fetch('tools_facets.php').then(r=>r.json()).then(f=>{
  const facets={
    /* … mismo mapeo que en la respuesta anterior … */
    brand:['Marca',{SGS:'SGS',MAYKESTAG:'Maykestag',SCHNEIDER:'Schneider',GENERICO:'Genérico'}],
    series_id:['Serie',f.series_id],
    diameter_mm:['Ø (mm)',f.diameter_mm],
    shank_diameter_mm:['Diámetro del cabo',f.shank_diameter_mm],
    flute_length_mm:['Largo útil',f.flute_length_mm],
    full_length_mm:['Largo total',f.full_length_mm],
    cut_length_mm:['Largo del filo',f.cut_length_mm],
    flute_count:['Filos',f.flute_count],
    tool_type:['Tipo',f.tool_type],
    material:['Material herr.',f.material],
    material_id:['Material mecanizable',f.material_id],
    radius:['Radio',f.radius],
    conical_angle:['Ángulo de conicidad',f.conical_angle],
    coated:['Recubrimiento',f.coated],
    strategy_id:['Estrategia',f.strategy_id],
    made_in:['Origen',f.made_in]
  };

  Object.entries(facets).forEach(([field,[label,dataRaw]])=>{
      const data=(typeof dataRaw==='object'&&!Array.isArray(dataRaw))
                ? dataRaw
                : Object.fromEntries((dataRaw??[]).map(v=>[v,v]));
      const id='facet_'+field;
      facetBox.insertAdjacentHTML('beforeend',`
        <div>
          <div class="facet-header" data-bs-target="#${id}">${label}
            <i class="bi bi-caret-down"></i>
          </div>
          <div id="${id}" class="facet-body">
            <input class="form-control form-control-sm facet-search" placeholder="Buscar…">
            ${Object.entries(data).map(([v,txt])=>`
              <label><input type="checkbox" name="${field}" value="${v}"> ${txt}</label>`).join('')}
          </div>
        </div>`);
      const header=facetBox.querySelector(`[data-bs-target="#${id}"]`);
      header.addEventListener('click',()=>{
        const body=document.getElementById(id);
        body.classList.toggle('show');
        header.querySelector('i').classList.toggle('bi-caret-up');
      });
      facetBox.querySelector(`#${id} .facet-search`).addEventListener('input',e=>{
         const q=e.target.value.toLowerCase();
         facetBox.querySelectorAll(`#${id} label`).forEach(l=>{
           l.style.display=l.textContent.toLowerCase().includes(q)?'':'none';
         });
      });
      facetBox.querySelectorAll(`#${id} input[type=checkbox]`).forEach(cb=>cb.addEventListener('change',applyFilter));
  });

  document.querySelectorAll('input[name="brand"]').forEach(cb=>cb.checked=true);
  loadServerData();
});

qBox.addEventListener('input',debounce(applyFilter,300));

function applyFilter(){
  if(!document.querySelectorAll('input[name="brand"]:checked').length){
      brandWarning.style.display='block';
      document.querySelector('#toolTbl tbody').innerHTML='';
      return;
  }
  brandWarning.style.display='none';
  loadServerData();
}

/* ----------- Carga + render ----------- */
async function loadServerData(){
  const fd=new FormData();
  facetBox.querySelectorAll('input[type=checkbox]:checked').forEach(cb=>fd.append(cb.name+'[]',cb.value));
  fd.append('q',qBox.value.trim());
  const q='?'+new URLSearchParams(fd).toString();
  const res=await fetch('ajax.php'+q);
  toolsData=await res.json();
  renderTable();
}

function renderTable(){
  const tb=document.querySelector('#toolTbl tbody');
  tb.innerHTML='';
  toolsData.forEach(t=>{
      const rowId='r'+t.tbl+t.tool_id;
      tb.insertAdjacentHTML('beforeend',`
        <tr>
          <td class="d-flex">
            <button class="btn btn-sm btn-outline-secondary toggle-details" data-target="#${rowId}">
              <i class="bi bi-eye"></i>
            </button>
            <a href="edit.php?tbl=${t.tbl}&id=${t.tool_id}" class="btn btn-sm btn-outline-primary ms-1">
              <i class="bi bi-pencil"></i>
            </a>
          </td>
          <td><span class="badge bg-info text-dark">${t.brand}</span></td>
          <td>${t.series_code}</td>
          <td>${t.details.image?`<img src="../panel/${t.details.image}" class="thumb">`:''}</td>
          <td>${t.tool_code}</td>
          <td class="text-truncate" style="max-width:220px">${t.name}</td>
          <td>${t.diameter_mm}</td>
          <td>${t.flute_count??'-'}</td>
          <td>${t.tool_type}</td>
        </tr>
        <tr class="collapse" id="${rowId}">
          <td colspan="9" class="bg-light">
            <div class="p-3">
              <div class="row g-1 mb-2">
                ${Object.entries(t.details).map(([k,v])=>`
                   <div class="col-6 col-md-3">
                      <small class="text-muted">${k}</small><br>${v??'-'}
                   </div>`).join('')}
              </div>
              <h6>Estrategias compatibles</h6>
              <div class="d-flex flex-wrap gap-2 mb-3">
                 ${t.strategies.map(s=>`<span class="badge bg-secondary">${s}</span>`).join('')}
              </div>
              <h6>Parámetros de corte</h6>
              ${t.params.length?`
                 <table class="table table-sm table-bordered mb-0">
                   <thead class="table-light"><tr>
                     <th>Material</th><th>Rating</th><th>Vc</th><th>Fz&nbsp;min</th>
                     <th>Fz&nbsp;max</th><th>ap</th><th>ae</th></tr></thead>
                   <tbody>
                     ${t.params.map(p=>`
                       <tr>
                          <td>${p.material}</td>
                          <td><span class="stars">${'★'.repeat(p.rating)}${'☆'.repeat(3-p.rating)}</span></td>
                          <td>${p.vc}</td><td>${p.fzmin}</td><td>${p.fzmax}</td>
                          <td>${p.ap}</td><td>${p.ae}</td>
                       </tr>`).join('')}
                   </tbody>
                 </table>`:'<em>Sin parámetros</em>'}
            </div>
          </td>
        </tr>`);
  });

  document.querySelectorAll('.toggle-details').forEach(btn=>{
      btn.onclick=()=>{
        const tgt=document.querySelector(btn.dataset.target);
        (bootstrap.Collapse.getInstance(tgt)||new bootstrap.Collapse(tgt,{toggle:false})).toggle();
      };
  });
  updateSortIcons();
}

/* ----------- Ordenamiento cliente ----------- */
document.querySelectorAll('#toolTbl thead th[data-col]').forEach(th=>{
   const col=th.dataset.col;
   th.insertAdjacentHTML('beforeend',`
      <i class="bi bi-caret-up-fill sort-icon"  data-dir="asc"  data-col="${col}"></i>
      <i class="bi bi-caret-down-fill sort-icon" data-dir="desc" data-col="${col}"></i>`);
});

/* clic en flecha */
document.addEventListener('click',e=>{
   if(!e.target.classList.contains('sort-icon')) return;
   const col=e.target.dataset.col;
   const dir=e.target.dataset.dir;
   currentSort={col,dir};
   toolsData.sort((a,b)=>{
       const va=a[col]??'' , vb=b[col]??'';
       if(va==vb) return 0;
       return (dir==='asc'?1:-1)*(va>vb?1:-1);
   });
   renderTable();
});

function updateSortIcons(){
   document.querySelectorAll('.sort-icon').forEach(i=>{
      i.classList.toggle('active',
        i.dataset.col===currentSort.col && i.dataset.dir===currentSort.dir);
   });
}
</script>
