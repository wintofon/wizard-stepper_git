/*
 * File: step1_manual_tool_browser.js
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * TODO: Extend documentation.
 */
/* ------------------------------------------------------------
 * step1_manual_tool_browser.js
 * Navegador de herramientas con facetas, búsqueda y selección
 * ▸ 100 % compatible con el stepper (envía tool_id & tool_table)
 * ▸ Soporta acceso externo ?brand=&code=  (fallback GET → step 2)
 * ▸ Modo DEBUG: loguea TODO en consola + window.dbg()
 * ------------------------------------------------------------ */
(() => {
  const BASE_URL = window.BASE_URL;
  /* -------- utilidades comunes ------------------------------------ */
  const dbg = (...m) => {          // visible en consola + #debug
    console.log('[STEP-1]', ...m);
    window.dbg?.('[STEP-1]', ...m);   // si existe helper global
  };
  const debounce = (fn, ms = 300) => {
    let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
  };

  /* -------- elementos clave --------------------------------------- */
  const facetBox      = document.getElementById('facetBox');
  const brandWarning  = document.getElementById('brandWarning');
  const qBox          = document.getElementById('qBox');
  const form          = document.getElementById('step1ManualForm');
  const toolIdInput   = document.getElementById('tool_id');
  const toolTableInput= document.getElementById('tool_table');
  const tableBody     = document.querySelector('#toolTbl tbody');
  const nextBtn       = document.getElementById('nextBtn') || { disabled:false };

  let toolsData   = [];
  let currentSort = { col:null, dir:null };

  /* ========== CARGAR FACETAS ====================================== */
  fetch(`${BASE_URL}/public/tools_facets.php`,{cache:'no-store'})
    .then(r => r.ok ? r.json() : Promise.reject(r.statusText))
    .then(renderFacets)
    .then(() => {        // Marcas seleccionadas por default
      facetBox.querySelectorAll('input[name="brand"]').forEach(cb => cb.checked = true);
      applyFilter();
      dbg('facets cargadas');
    })
    .catch(err => {
      console.error('❌ tools_facets.php:', err);
      facetBox.innerHTML = '<div class="text-danger p-2">Error al cargar filtros</div>';
    });

  function renderFacets(data){
    const facets = {
      brand:['Marca',data.brand],series_id:['Serie',data.series_id],
      diameter_mm:['Ø (mm)',data.diameter_mm],shank_diameter_mm:['Ø cabo',data.shank_diameter_mm],
      flute_length_mm:['Largo útil',data.flute_length_mm],full_length_mm:['Total',data.full_length_mm],
      cut_length_mm:['Largo filo',data.cut_length_mm],flute_count:['Filos',data.flute_count],
      tool_type:['Tipo',data.tool_type],material:['Material herr.',data.material],
      material_id:['Material mecan.',data.material_id],radius:['Radio',data.radius],
      conical_angle:['Ángulo',data.conical_angle],coated:['Coating',data.coated],
      strategy_id:['Estrategia',data.strategy_id],made_in:['Origen',data.made_in]
    };
    Object.entries(facets).forEach(([field,[label,raw]])=>{
      const opts = typeof raw==='object' && !Array.isArray(raw)
                 ? raw
                 : Object.fromEntries((raw||[]).map(v=>[v,v]));
      const id = 'facet_'+field;
      facetBox.insertAdjacentHTML('beforeend',`
        <div>
          <div class="facet-header" data-bs-target="#${id}">
            ${label} <i class="bi bi-caret-down"></i>
          </div>
          <div id="${id}" class="facet-body">
            <input class="form-control form-control-sm facet-search" placeholder="Buscar…">
            ${Object.entries(opts).map(([v,txt])=>`
              <label><input type="checkbox" name="${field}" value="${v}"> ${txt}</label>`).join('')}
          </div>
        </div>`);

      /* toggle */
      facetBox.querySelector(`[data-bs-target="#${id}"]`).onclick = () => {
        document.getElementById(id).classList.toggle('show');
      };
      /* mini-buscador */
      facetBox.querySelector(`#${id} .facet-search`).oninput = e => {
        const q = e.target.value.toLowerCase();
        facetBox.querySelectorAll(`#${id} label`).forEach(l=>{
          l.style.display = l.textContent.toLowerCase().includes(q)?'':'none';
        });
      };
      /* cambio de checkbox = filtrar */
      facetBox.querySelectorAll(`#${id} input[type=checkbox]`)
              .forEach(cb=>cb.onchange=applyFilter);
    });
  }

  /* ========== FILTRO + BÚSQUEDA =================================== */
  qBox.addEventListener('input', debounce(applyFilter,300));

  function applyFilter(){
    tableBody.innerHTML='';
    if(!facetBox.querySelectorAll('input[name="brand"]:checked').length){
      brandWarning.hidden=false;
    } else {
      brandWarning.hidden=true;
      cargarTabla();
    }
    import(`${BASE_URL}/assets/js/step1_manual_lazy_loader.js`)
      .then(m => m.initLazy())
      .catch(console.error);
  }

  /* ========== AJAX → tools_ajax.php =============================== */
  async function cargarTabla(){
    const fd=new FormData();
    facetBox.querySelectorAll('input[type=checkbox]:checked')
            .forEach(cb=>fd.append(cb.name+'[]',cb.value));
    fd.append('q',qBox.value.trim());

    const url=`${BASE_URL}/ajax/tools_ajax.php?`+new URLSearchParams(fd);
    dbg('fetch',url);
    const r = await fetch(url,{cache:'no-store'});
    toolsData = await r.json();
    renderTable();
  }

  /* ========== RENDER TABLA ======================================== */
  function renderTable(){
    tableBody.innerHTML='';
    toolsData.forEach(t=>{
      tableBody.insertAdjacentHTML('beforeend',`
        <tr>
          <td><button class="btn btn-sm btn-primary select-btn"
                 data-tool_id="${t.tool_id}" data-tbl="${t.tbl}">✓</button></td>
          <td><span class="badge bg-info text-dark">${t.brand}</span></td>
          <td>${t.series_code}</td>
          <td>${t.details.image?`<img src="${BASE_URL}/${t.details.image}" class="thumb">`:''}</td>
          <td>${t.tool_code}</td>
          <td class="text-truncate" style="max-width:200px">${t.name}</td>
          <td>${t.diameter_mm}</td><td>${t.flute_count||'-'}</td><td>${t.tool_type}</td>
        </tr>`);
    });

    /* hook de selección */
    document.querySelectorAll('.select-btn').forEach(btn=>{
      btn.onclick=()=>{
        toolIdInput.value=btn.dataset.tool_id;
        toolTableInput.value=btn.dataset.tbl;
        nextBtn.disabled=false;
        dbg('seleccion',btn.dataset.tbl,btn.dataset.tool_id);
        form.requestSubmit();
      };
    });
    ordenarIconos();
  }

  /* ========== ORDENAMIENTO CLIENT-SIDE ============================ */
  document.querySelectorAll('#toolTbl thead th[data-col]').forEach(th=>{
    const col = th.dataset.col;
    th.insertAdjacentHTML('beforeend',`
      <i class="bi bi-caret-up-fill  sort-icon" data-col="${col}" data-dir="asc"></i>
      <i class="bi bi-caret-down-fill sort-icon" data-col="${col}" data-dir="desc"></i>`);
  });

  document.addEventListener('click',e=>{
    if(!e.target.classList.contains('sort-icon')) return;
    const {col,dir}=e.target.dataset;
    currentSort={col,dir};
    toolsData.sort((a,b)=>{
      const va=a[col]||'', vb=b[col]||'';
      return dir==='asc' ? va.localeCompare(vb,'es',{numeric:true})
                         : vb.localeCompare(va,'es',{numeric:true});
    });
    renderTable();
  });

  function ordenarIconos(){
    document.querySelectorAll('.sort-icon').forEach(i=>{
      i.classList.toggle('active',
        i.dataset.col===currentSort.col && i.dataset.dir===currentSort.dir);
    });
  }

  /* ========== LINK EXTERNO (GET) ================================== */
  document.addEventListener('DOMContentLoaded',()=>{
    const u=new URL(location.href), b=u.searchParams.get('brand'), c=u.searchParams.get('code');
    if(!b||!c) return;
    dbg('GET external',b,c);
    fetch(`${BASE_URL}/views/steps/manual/step2.php?brand=${encodeURIComponent(b)}&code=${encodeURIComponent(c)}`)
      .then(r=>r.ok?location.assign('wizard.php?step=2'):Promise.reject('404'))
      .catch(err=>alert('⚠️ No se pudo cargar '+c+': '+err));
  });
})();
