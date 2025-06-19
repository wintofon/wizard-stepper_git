export function initLazy() {
  const shared = window.Step3LazyShared || {};
  const container = shared.container;
  const state = shared.state || { page: 2, hasMore: true, loading: false };
  const fetchTools = shared.fetchTools;
  const sentinel = document.getElementById('sentinel');
  const scrollContainer = document.getElementById('scrollContainer');
  if (!container || !sentinel || !fetchTools) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        loadMore();
      }
    });
  }, { root: scrollContainer, rootMargin: '200px', threshold: 0.1 });

  function loadMore() {
    if (state.loading || !state.hasMore) return;
    state.loading = true;
    fetchTools(state.page).then(() => {
      state.loading = false;
      state.page = shared.state.page;
      state.hasMore = shared.state.hasMore;
      if (!state.hasMore) {
        observer.unobserve(sentinel);
        shared.showEnd && shared.showEnd();
      }
    }).catch(err => {
      console.error('lazy load error:', err);
      state.loading = false;
    });
  }

  observer.observe(sentinel);
}
