const container = document.getElementById('toolContainer');
const sentinel = document.getElementById('scrollSentinel');
const diaFilter = document.getElementById('diaFilter');
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const materialId = parseInt(container.dataset.material, 10);
const strategyId = parseInt(container.dataset.strategy, 10);
const thickness = parseFloat(container.dataset.thickness || '0');

window.dbg = function (...m) {
  console.log('[STEP3]', ...m);
  const box = document.getElementById('debug');
  if (box) box.textContent += m.join(' ') + '\n';
};

let page = 1;
let loading = false;
let hasMore = true;
const diamSet = new Set();

function fmtMM(val) {
  const num = parseFloat(val);
  if (Number.isNaN(num)) return '-';
  if (Math.round(num) === num) return `${num.toFixed(0)} mm`;
  return `${num.toFixed(3).replace(/\.0+$|(?<=\.\d)0+$/, '')} mm`;
}

function renderStars(n) {
  const c = Math.max(0, parseInt(n, 10));
  return '★'.repeat(c);
}

function addFilterOptions() {
  diaFilter.innerHTML = '<option value="">— Todos —</option>';
  Array.from(diamSet).sort((a,b)=>parseFloat(a)-parseFloat(b)).forEach(d => {
    const o = document.createElement('option');
    o.value = d;
    o.textContent = fmtMM(d);
    diaFilter.appendChild(o);
  });
}

diaFilter.addEventListener('change', () => {
  const sel = diaFilter.value;
  document.querySelectorAll('#toolContainer .fresa-card').forEach(c => {
    if (!sel || c.dataset.dia === sel) c.style.display = '';
    else c.style.display = 'none';
  });
});

function createCard(t) {
  const dia = parseFloat(t.diameter_mm).toFixed(3);
  const card = document.createElement('div');
  card.className = 'fresa-card row align-items-center';
  card.dataset.dia = dia;

  const imgCol = document.createElement('div');
  imgCol.className = 'col-md-2 mb-2 mb-md-0';
  const img = document.createElement('img');
  img.className = 'img-fluid tool-thumb';
  img.src = t.image_url || `${window.BASE_URL}/assets/img/logos/logo_stepper.png`;
  img.onerror = () => {
    img.src = `${window.BASE_URL}/assets/img/logos/logo_stepper.png`;
  };
  imgCol.appendChild(img);
  card.appendChild(imgCol);

  const info = document.createElement('div');
  info.className = 'col-md-7';
  info.innerHTML = `<strong>${t.brand}</strong><br>${t.name} — Serie ${t.serie} — Código ${t.tool_code}<br>` +
    `<small>Ø${fmtMM(t.diameter_mm)} · Mango ${fmtMM(t.shank_diameter_mm)} · L. útil ${fmtMM(t.cut_length_mm)} · Z = ${t.flute_count || '-'}</small><br>` +
    `<span class="estrella">${renderStars(t.rating)}</span>`;
  if (thickness && thickness > parseFloat(t.cut_length_mm)) {
    const warn = document.createElement('div');
    warn.className = 'warning mt-1';
    warn.textContent = `⚠ El espesor (${fmtMM(thickness)}) supera el largo útil (${fmtMM(t.cut_length_mm)})`;
    info.appendChild(warn);
  }
  card.appendChild(info);

  const btnCol = document.createElement('div');
  btnCol.className = 'col-md-3 text-md-end mt-2 mt-md-0';
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'btn btn-select';
  btn.textContent = 'Seleccionar';
  btn.dataset.tool_id = t.tool_id;
  btn.dataset.tool_tbl = t.source_table;
  btnCol.appendChild(btn);
  card.appendChild(btnCol);

  return card;
}

async function fetchPage() {
  if (loading || !hasMore) return;
  loading = true;
  try {
    const url = `${window.BASE_URL}/ajax/load_tools_scroll.php?material_id=${materialId}&strategy_id=${strategyId}&page=${page}&per_page=12`;
    dbg('fetch', url);
    const res = await fetch(url, { headers: csrf ? { 'X-CSRF-Token': csrf } : {}, cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();
    data.tools.forEach(t => {
      container.appendChild(createCard(t));
      if (t.diameter_mm) diamSet.add(parseFloat(t.diameter_mm).toFixed(3));
    });
    addFilterOptions();
    hasMore = data.has_more;
    if (hasMore) page++; else observer.unobserve(sentinel);
  } catch (err) {
    dbg('error', err);
    const div = document.createElement('div');
    div.className = 'alert alert-danger';
    div.textContent = 'Error al cargar herramientas: ' + err.message;
    container.appendChild(div);
    observer.unobserve(sentinel);
  } finally {
    loading = false;
  }
}

const observer = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) fetchPage();
  });
}, { rootMargin: '200px' });

document.addEventListener('DOMContentLoaded', () => {
  observer.observe(sentinel);
  fetchPage();
});
