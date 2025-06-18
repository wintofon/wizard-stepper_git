// assets/js/step1_lazy.js
// Navegador de fresas con carga perezosa via IntersectionObserver
// y AJAX (tools_scroll.php)
// Comentarios paso a paso para comprender cada parte

let page = 1;                 // página actual a solicitar
let loading = false;          // evita solicitudes duplicadas
let hasMore = true;           // indica si quedan registros
const list = document.getElementById('tool-list');
const sentinel = document.getElementById('sentinel');
const spinner = document.createElement('div');
spinner.className = 'spinner-border text-info';

let controller = null;        // AbortController para abortar fetch previos

/** Renderiza una tarjeta Bootstrap para una herramienta */
function renderToolCard(t) {
  const col = document.createElement('div');
  col.className = 'col-6 col-md-4 col-lg-3';
  col.innerHTML = `
    <div class="card h-100 bg-dark text-white">
      <img loading="lazy" src="/wizard-stepper_git/${t.image}" class="card-img-top"
           alt="Fresa ${t.tool_code}" onerror="this.style.display='none'">
      <div class="card-body p-2">
        <h6 class="card-title small mb-1">${t.tool_code}</h6>
        <p class="card-text mb-0">${t.diameter_mm} mm</p>
      </div>
    </div>`;
  list.appendChild(col);
}

/** Carga una página de herramientas desde el servidor */
async function loadPage() {
  if (loading || !hasMore) return;
  loading = true;
  list.appendChild(spinner);

  // aborta la solicitud previa si existe
  if (controller) controller.abort();
  controller = new AbortController();

  try {
    const res = await fetch(`../ajax/tools_scroll.php?page=${page}`, {
      signal: controller.signal,
    });
    if (!res.ok) throw new Error(res.statusText);
    const json = await res.json();
    json.tools.forEach(renderToolCard);
    hasMore = json.hasMore;
    page = json.nextPage;
    if (!hasMore) {
      sentinel.remove();
      list.insertAdjacentHTML(
        'beforeend',
        '<div class="alert alert-success w-100">✅ Fin de lista</div>'
      );
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

// IntersectionObserver: dispara loadPage cuando el sentinel entra en vista
const io = new IntersectionObserver(
  entries => entries[0].isIntersecting && loadPage(),
  { rootMargin: '300px' }
);
io.observe(sentinel);

// Debounce manual para scroll intenso en navegadores antiguos
let debounceT;
document.addEventListener('scroll', () => {
  clearTimeout(debounceT);
  debounceT = setTimeout(() => {}, 150);
});

// Primera carga
loadPage();
