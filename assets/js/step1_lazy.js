// step1_lazy.js - Infinite scroll loader for manual step 1
// ES6 module: fetches paginated tools and appends Bootstrap cards

const csrf = window.csrfToken || '';
let page = 1;
let loading = false;
let hasMore = true;
let controller = null;

const list = document.getElementById('tool-list');
const sentinel = document.getElementById('sentinel');
const spinner = document.createElement('div');
spinner.className = 'spinner-border text-info';

function renderToolCard(t) {
  const col = document.createElement('div');
  col.className = 'col-6 col-md-4 col-lg-3';

  const card = document.createElement('div');
  card.className = 'card h-100 bg-dark text-white';

  const img = document.createElement('img');
  img.loading = 'lazy';
  img.src = `/wizard-stepper_git/${t.image}`;
  img.className = 'card-img-top';
  img.alt = `Fresa ${t.tool_code}`;
  img.onerror = () => { img.style.display = 'none'; };

  const body = document.createElement('div');
  body.className = 'card-body p-2';

  const title = document.createElement('h6');
  title.className = 'card-title small mb-1';
  title.textContent = t.tool_code;

  const txt = document.createElement('p');
  txt.className = 'card-text mb-0';
  txt.textContent = `${t.diameter_mm} mm`;

  body.appendChild(title);
  body.appendChild(txt);
  card.appendChild(img);
  card.appendChild(body);
  col.appendChild(card);
  list.appendChild(col);
}

const debounce = (fn, ms = 150) => {
  let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
};

async function loadPage() {
  if (!hasMore) return;
  if (controller) controller.abort();
  loading = true;
  list.appendChild(spinner);
  controller = new AbortController();

  try {
    const res = await fetch(`../ajax/tools_scroll.php?page=${page}`, {
      headers: csrf ? { 'X-CSRF-Token': csrf } : {},
      signal: controller.signal
    });
    if (!res.ok) throw new Error(res.statusText);
    const json = await res.json();
    json.tools.forEach(renderToolCard);
    hasMore = json.hasMore;
    page = json.nextPage;
    if (!hasMore) {
      sentinel.remove();
      list.insertAdjacentHTML('beforeend', '<div class="alert alert-success mt-3">✅ Fin de lista</div>');
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

const io = new IntersectionObserver(debounce(([e]) => {
  if (e.isIntersecting && !loading) loadPage();
}, 150), { rootMargin: '300px' });

io.observe(sentinel);
loadPage();

