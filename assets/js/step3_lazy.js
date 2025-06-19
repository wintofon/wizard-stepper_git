import { BASE_URL } from './config.js';

let observer;

export function initLazy() {
  const sentinel = document.getElementById('sentinel');
  const scrollContainer = document.getElementById('scrollContainer');
  if (!sentinel || !scrollContainer) return;

  observer?.disconnect();
  observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        if (window.fetchTools && typeof window.fetchTools === 'function') {
          window.fetchTools(window.currentPage + 1);
        }
      }
    });
  }, { root: scrollContainer, rootMargin: '200px', threshold: 0.1 });

  observer.observe(sentinel);
}

