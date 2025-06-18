// assets/js/step1_lazy.js
// Carga perezosa de fresas (Paso 1 - manual)

// 1) Estado inicial
let page = 1;
let loading = false;
let hasMore = true;
let controller = null; // permite abortar fetch si el usuario scrollea rápido

// 2) Elementos del DOM
const list = document.getElementById('tool-list');
const sentinel = document.getElementById('sentinel');
const spinner = document.createElement('div');
spinner.className = 'spinner-border text-info';

// 3) Renderiza una tarjeta Bootstrap con los datos de una fresa
function renderToolCard(tool) {
  const col = document.createElement('div');
  col.className = 'col-6 col-md-4 col-lg-3';

  const card = document.createElement('div');
  card.className = 'card h-100 bg-dark text-white';

  const img = document.createElement('img');
  img.loading = 'lazy';
  img.src = `/wizard-stepper_git/${tool.image}`;
  img.className = 'card-img-top';
  img.alt = `Fresa ${tool.tool_code}`;
  img.onerror = () => { img.style.display = 'none'; };

  const body = document.createElement('div');
  body.className = 'card-body p-2';

  const title = document.createElement('h6');
  title.className = 'card-title small mb-1';
  title.innerText = tool.tool_code;

  const txt = document.createElement('p');
  txt.className = 'card-text mb-0';
  txt.innerText = `${tool.diameter_mm} mm`;

  body.appendChild(title);
  body.appendChild(txt);
  card.appendChild(img);
  card.appendChild(body);
  col.appendChild(card);
  list.appendChild(col);
}

// 4) Carga una página de resultados desde PHP
async function loadPage() {
  if (loading || !hasMore) return; // evita duplicar peticiones
  loading = true;
  list.appendChild(spinner);

  if (controller) controller.abort(); // cancela fetch anterior
  controller = new AbortController();

  try {
    const res = await fetch(`../ajax/tools_scroll.php?page=${page}`, {
      headers: { 'X-CSRF-Token': window.csrfToken || '' },
      signal: controller.signal
    });
    const json = await res.json();
    json.tools.forEach(renderToolCard);
    hasMore = json.hasMore;
    page = json.nextPage;
    if (!hasMore) {
      sentinel.remove();
      const done = document.createElement('div');
      done.className = 'alert alert-success w-100 text-center';
      done.textContent = '✅ Fin de lista';
      list.appendChild(done);
    }
  } catch (err) {
    if (err.name !== 'AbortError') {
      const warn = document.createElement('div');
      warn.className = 'alert alert-danger w-100';
      warn.textContent = 'Sin red, reintentá';
      list.appendChild(warn);
    }
  } finally {
    spinner.remove();
    loading = false;
  }
}

// 5) Debounce para scrolls muy rápidos
const debouncedLoad = (() => {
  let t; return () => { clearTimeout(t); t = setTimeout(loadPage, 150); };
})();

// 6) Observer que dispara la carga cuando el sentinel es visible
const io = new IntersectionObserver(e => {
  if (e[0].isIntersecting) debouncedLoad();
}, { rootMargin: '300px' });

io.observe(sentinel);
loadPage(); // primera tanda
