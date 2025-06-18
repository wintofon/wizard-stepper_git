// --------------------------------------------------------------
// step1_lazy.js - Navegador de fresas con scroll infinito
// --------------------------------------------------------------

let page = 1;
let loading = false;
let hasMore = true;
let abortCtrl = null;

const list = document.getElementById('tool-list');
const sentinel = document.getElementById('sentinel');
const spinner = document.createElement('div');
spinner.className = 'spinner-border text-info';

// obtén token CSRF del formulario
const csrfToken = document.querySelector('input[name="csrf_token"]').value;

// -------------------------------------------------------------------
// Carga un lote de fresas desde el servidor
// -------------------------------------------------------------------
async function loadPage() {
  if (loading || !hasMore) return;
  loading = true;
  list.appendChild(spinner);

  // aborta cualquier fetch previo si sigue en curso
  if (abortCtrl) abortCtrl.abort();
  abortCtrl = new AbortController();

  try {
    const res = await fetch(`../ajax/tools_scroll.php?page=${page}`, {
      headers: { 'X-CSRF-Token': csrfToken },
      signal: abortCtrl.signal
    });
    if (!res.ok) throw new Error('network');
    const json = await res.json();
    json.tools.forEach(renderToolCard);
    hasMore = json.hasMore;
    page = json.nextPage;
    if (!hasMore) {
      sentinel.remove();
      alert('✅ Fin de lista');
    }
  } catch (err) {
    if (err.name !== 'AbortError') {
      alert('Sin red, reintentá');
    }
  } finally {
    spinner.remove();
    loading = false;
  }
}

// -------------------------------------------------------------------
// Renderiza una tarjeta Bootstrap para cada fresa
// -------------------------------------------------------------------
function renderToolCard(t) {
  const col = document.createElement('div');
  col.className = 'col-6 col-md-4 col-lg-3';
  col.innerHTML = `
    <div class="card h-100 bg-dark text-white">
      <img loading="lazy" src="/wizard-stepper_git/${encodeURI(t.image)}" class="card-img-top" alt="Fresa ${t.tool_code}" onerror="this.style.display='none'">
      <div class="card-body p-2">
        <h6 class="card-title small mb-1"></h6>
        <p class="card-text mb-0"></p>
      </div>
    </div>`;
  col.querySelector('.card-title').innerText = t.tool_code;
  col.querySelector('.card-text').innerText = `${t.diameter_mm} mm`;
  list.appendChild(col);
}

// utilitario debounce para navegadores viejos
function debounce(fn, ms = 150) {
  let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

const io = new IntersectionObserver(entries => {
  if (entries[0].isIntersecting) debouncedLoad();
}, { rootMargin: '300px' });

const debouncedLoad = debounce(loadPage, 150);
io.observe(sentinel);
loadPage();

