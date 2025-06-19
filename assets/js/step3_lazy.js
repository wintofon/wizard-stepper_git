import { BASE_URL } from './config.js';
let page = 2;
let loading = false;
let hasMore = true;

export function initLazy() {
  const sentinel = document.getElementById('sentinel');
  const container = document.getElementById('toolContainer');
  const scrollContainer = document.getElementById('scrollContainer');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  if (!sentinel || !container) return;

  const observer = new IntersectionObserver(async (entries) => {
    if (entries.some(e => e.isIntersecting)) {
      await loadPage();
    }
  }, { root: scrollContainer, rootMargin: '200px' });

  async function loadPage() {
    if (loading || !hasMore) return;
    loading = true;
    try {
      const res = await fetch(`${BASE_URL}/ajax/tools_scroll.php?mode=auto&page=${page}`, {
        cache: 'no-store',
        headers: { 'X-CSRF-Token': csrf }
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      if (Array.isArray(data.tools)) {
        window.appendTools?.(data.tools);
      }
      hasMore = data.hasMore;
      if (hasMore) {
        page++;
      } else {
        observer.disconnect();
        const end = document.createElement('div');
        end.className = 'text-center text-muted py-2';
        end.textContent = 'Fin de lista';
        container.appendChild(end);
      }
    } catch (err) {
      console.error('lazy load error', err);
    } finally {
      loading = false;
    }
  }

  observer.observe(sentinel);
}
