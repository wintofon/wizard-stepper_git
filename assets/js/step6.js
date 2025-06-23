/*
 * File: step1_manual_lazy_loader.js
 * Epic CNC Wizard Lazy Loader – versión corregida 🔧🌟
 *
 * Main responsibility:
 *   Cargar filas de tabla de forma perezosa con IntersectionObserver,
 *   narrar cada evento en la consola con dramatismo y evitar "material_id" faltante.
 * Related files: ajax/tools_scroll.php
 * TODO: Añadir animaciones CSS en futuras versiones.
 */

import { BASE_URL } from './config.js';

export let page = 1;
export let loading = false;
export let hasMore = true;

// Elementos clave en el DOM
export const sentinel = document.getElementById('sentinel');
export const tbody    = document.querySelector('#toolTbl tbody');

// CSRF y metadatos
const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content || '';
const materialMeta = document.querySelector('meta[name="material-id"]');
const materialId = materialMeta && !isNaN(parseInt(materialMeta.content,10))
  ? parseInt(materialMeta.content,10)
  : null;
const strategyMeta = document.querySelector('meta[name="strategy-id"]');
const strategyId   = strategyMeta && !isNaN(parseInt(strategyMeta.content,10))
  ? parseInt(strategyMeta.content,10)
  : null;

// Estilos de consola épicos
const TAG_STYLE = 'color:#00BCD4;font-weight:bold';
const log   = (...args) => console.log('%c[LazyLoader📥]', TAG_STYLE, ...args);
const warn  = (...args) => console.warn('%c[LazyLoader⚠️]', TAG_STYLE, ...args);
const error = (...args) => console.error('%c[LazyLoader💥]', TAG_STYLE, ...args);
const table = d => console.table(d);
function group(title, fn) {
  console.group(`%c[LazyLoader🌀] ${title}`, TAG_STYLE);
  try { return fn(); }
  finally { console.groupEnd(); }
}

// IntersectionObserver para cargar más filas
const scrollContainer = document.querySelector('.list-scroll-container');
const observer = new IntersectionObserver(entries => {
  group('ObserverCallback', () => {
    entries.forEach(entry => {
      log('Observer entry:', entry.isIntersecting);
      if (entry.isIntersecting) loadPage();
    });
  });
}, { root: scrollContainer, rootMargin: '150px', threshold: 0.1 });

// Carga de datos perezosa
export async function loadPage() {
  return group(`loadPage - page ${page}`, async () => {
    if (loading) { log('⏳ Carga en curso, ignorar llamada.'); return; }
    if (!hasMore) { log('🏁 Sin más páginas.'); observer.unobserve(sentinel); return; }
    if (!tbody) { error('❌ <tbody> no encontrado.'); return; }
    if (materialId === null) {
      warn('⚠️ material_id ausente o inválido; procediendo sin filtrar por material');
      // procedemos con carga general sin filtrar por material
    }
    }

    loading = true;
    log('🚀 Parámetros:', { page, materialId, strategyId });

    try {
      const params = new URLSearchParams({ page, material_id: materialId });
      if (strategyId !== null) params.append('strategy_id', strategyId);
      const url = `${BASE_URL}/ajax/tools_scroll.php?${params}`;
      log('🔗 Fetching:', url);
      const res = await fetch(url, { cache: 'no-store', headers: csrfToken ? { 'X-CSRF-Token':csrfToken } : {} });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      log('📬 Datos recibidos'); table(data);

      if (Array.isArray(data.tools)) {
        data.tools.forEach(t => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td><input type="radio" class="form-check-input select-btn" data-tool_id="${t.tool_id}" data-tbl="${t.tbl}"></td>
            <td><span class="badge bg-info text-dark">${t.brand||''}</span></td>
            <td>${t.series_code||t.series||''}</td>
            <td>${t.img_url?`<img src="${t.img_url}" class="thumb">`:''}</td>
            <td>${t.tool_code||''}</td>
            <td class="text-truncate" style="max-width:200px">${t.name||''}</td>
            <td>${t.diameter_mm||''}</td>
            <td>${t.flute_count||''}</td>
            <td>${t.tool_type||''}</td>`;
          tbody.appendChild(tr);
          log('➕ Fila añadida ID:', t.tool_id);
        });
      }

      hasMore = Boolean(data.hasMore);
      if (hasMore) {
        page++;
        log('↩️ Preparado para página siguiente:', page);
      } else {
        log('🏆 Fin de lista.');
        const endTr = document.createElement('tr');
        endTr.innerHTML = '<td colspan="9" class="text-center">Fin de lista</td>';
        tbody.appendChild(endTr);
        observer.unobserve(sentinel);
      }
    } catch (err) {
      error('💥 loadPage error:', err);
    } finally {
      loading = false;
    }
  });
}

// Inicializar lazy load
export function initLazy() {
  return group('initLazy', () => {
    if (!tbody || !sentinel) { error('❌ initLazy: elementos faltan'); return; }
    page = 1; loading = false; hasMore = true;
    tbody.innerHTML = '';
    observer.disconnect(); observer.observe(sentinel);
    log('🔄 Lazy loader reiniciado.');
    loadPage();
  });
}

document.addEventListener('DOMContentLoaded', () => initLazy());
window.initLazy = initLazy;
