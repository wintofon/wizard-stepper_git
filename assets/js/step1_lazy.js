export let page = 1;
export let loading = false;
export let hasMore = true;

const toolList = document.getElementById('tool-list');
const sentinel = document.getElementById('sentinel');
let controller;

export async function loadPage() {
  if (loading || !hasMore || !toolList) return;
  if (controller) controller.abort();
  controller = new AbortController();
  loading = true;

  const spinnerWrap = document.createElement('div');
  spinnerWrap.className = 'd-flex justify-content-center my-3';
  spinnerWrap.innerHTML =
    '<div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>';
  sentinel.before(spinnerWrap);
  try {
    const res = await fetch(`/wizard-stepper_git/ajax/tools_scroll.php?page=${page}`, {
      cache: 'no-store',
      signal: controller.signal,
      headers: window.csrfToken ? { 'X-CSRF-Token': window.csrfToken } : {}
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (Array.isArray(data.tools)) {
      data.tools.forEach(t => {
        const card = document.createElement('div');
        card.className = 'card mb-2';
        card.innerHTML = `<div class="card-body"><strong>${t.tool_code ?? ''}</strong> ${t.name ?? ''}</div>`;
        toolList.appendChild(card);
      });
    }
    page = data.nextPage;
    hasMore = data.hasMore;
    if (!hasMore) {
      observer.unobserve(sentinel);
      const endMsg = document.createElement('div');
      endMsg.className = 'alert alert-info text-center my-3';
      endMsg.textContent = 'Fin de lista';
      toolList.appendChild(endMsg);
    }
  } catch (err) {
    if (err.name !== 'AbortError') {
      console.error('loadPage error:', err);
      const alert = document.createElement('div');
      alert.className = 'alert alert-danger';
      alert.textContent = 'Error al cargar herramientas';
      toolList.appendChild(alert);
    }
  } finally {
    spinnerWrap.remove();
    loading = false;
  }
}

const observer = new IntersectionObserver(
  entries => {
    entries.forEach(e => {
      if (e.isIntersecting) loadPage();
    });
  },
  { rootMargin: '300px' }
);

export function initLazy() {
  if (toolList && sentinel) {
    page = 1;
    loading = false;
    hasMore = true;
    toolList.innerHTML = '';
    observer.observe(sentinel);
  }
}

document.addEventListener('DOMContentLoaded', initLazy);
if (document.readyState !== 'loading') initLazy();
window.initLazy = initLazy;
