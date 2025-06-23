/*
 * File: step1_manual_lazy_loader.js
 * Epic CNC Wizard Lazy Loader – versión robusta 🔧🌟
 *
 * Main responsibility:
 *   Cargar filas de tabla bajo demanda con IntersectionObserver,
 *   manejar errores, evitar fin de sintaxis y reset de parámetros,
 *   y narrar cada evento en la consola con dramatismo.
 * Related files: ajax/tools_scroll.php
 * TODO: Añadir animaciones CSS en futuras versiones.
 */

import { BASE_URL } from './config.js';

// Estado global
export let page = 1;
export let loading = false;
export let hasMore = true;

// Elementos clave del DOM
export const sentinel = document.getElementById('sentinel');
export const tbody    = document.querySelector('#toolTbl tbody');

// CSRF y metadatos
const csrfToken   = document.querySelector('meta[name="csrf-token"]')?.content || '';
const materialMeta = document.querySelector('meta[name="material-id"]');
const materialId   = materialMeta && !isNaN(+materialMeta.content)
  ? +materialMeta.content
  : null;
const strategyMeta = document.querySelector('meta[name="strategy-id"]');
const strategyId   = strategyMeta && !isNaN(+strategyMeta.content)
  ? +strategyMeta.content
  : null;

// Estilos de consola épicos
const TAG_STYLE = 'color:#00BCD4;font-weight:bold';
const log   = (...args) => console.log('%c[LazyLoader📥]', TAG_STYLE, ...args);
const warn  = (...args) => console.warn('%c[LazyLoader⚠️]', TAG_STYLE, ...args);
const error = (...args) => console.error('%c[LazyLoader💥]', TAG_STYLE, ...args);
const table = data => console.table(data);
function group(title, fn) {
  console.group(`%c[LazyLoader🌀] ${title}`, TAG_STYLE);
  try { return fn(); } finally { console.groupEnd(); }
}

// IntersectionObserver para carga perezosa
const scrollContainer = document.querySelector('.list-scroll-container');
const observer = new IntersectionObserver(entries => {
  group('ObserverCallback', () => {
    entries.forEach(entry => {
      log('Observer entry.isIntersecting:', entry.isIntersecting);
      if (entry.isIntersecting) loadPage().catch(err => error('loadPage failed', err));
    });
  });
}, { root: scrollContainer, rootMargin: '150px', threshold: 0.1 });

// Función principal: carga de página
export async function loadPage() {
  return group(`loadPage - page ${page}`, async () => {
    if (loading) {
      log('⏳ Carga en curso, ignorar llamada duplicada');
      return;
    }
    if (!hasMore) {
      log('🏁 Sin más páginas');
      observer.unobserve(sentinel);
      return;
    }
    if (!tbody) {
      error('❌ <tbody> no encontrado');
      return;
    }
    if (materialId === null) {
      warn('⚠️ material_id ausente; cargando sin filtrar');
    }

    loading = true;
    log('🚀 Parámetros:', { page, materialId, strategyId });

    try {
      // Construir parámetros
      const params = new URLSearchParams({ page: String(page) });
      if (materialId !== null) params.append('material_id', String(materialId));
      if (strategyId !== null) params.append('strategy_id', String(strategyId));
      const url = `${BASE_URL}/ajax/tools_scroll.php?${params.toString()}`;
      log('🔗 Fetching:', url);

      const res = await fetch(url, {
        cache: 'no-store',
        headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {}
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      log('📬 Datos recibidos'); table(data);

      // Renderizar filas
      if (Array.isArray(data.tools)) {
        data.tools.forEach(tool => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td><input type="radio" class="form-check-input select-btn" data-tool_id="${tool.tool_id}" data-tbl="${tool.tbl}"></td>
            <td><span class="badge bg-info text-dark">${tool.brand || ''}</span></td>
            <td>${tool.series_code || tool.series || ''}</td>
            <td>${tool.img_url ? `<img src="${tool.img_url}" class="thumb">` : ''}</td>
            <td>${tool.tool_code || ''}</td>
            <td class="text-truncate" style="max-width:200px">${tool.name || ''}</td>
            <td>${tool.diameter_mm || ''}</td>
            <td>${tool.flute_count || ''}</td>
            <td>${tool.tool_type || ''}</td>`;
          tbody.appendChild(tr);
          log('➕ Fila añadida ID:', tool.tool_id);
        });
      }

      // Control de paginación
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
    if (!tbody || !sentinel) {
      error('❌ initLazy: elementos faltan');
      return;
    }
    page = 1;
    loading = false;
    hasMore = true;
    tbody.innerHTML = '';
    observer.disconnect();
    observer.observe(sentinel);
    log('🔄 Lazy loader reiniciado.');
    loadPage().catch(err => error('initLazy loadPage', err));
  });
}

document.addEventListener('DOMContentLoaded', () => initLazy());
window.initLazy = initLazy;
