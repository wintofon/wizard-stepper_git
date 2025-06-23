/*
 * File: step1_manual_lazy_loader.js
 * Epic CNC Wizard Lazy Loader – versión legendaria 🌌
 *
 * Main responsibility:
 *   Cargar filas de tabla bajo demanda usando IntersectionObserver,
 *   narrar cada evento en la consola con dramatismo sin errores.
 * Related files: ajax/tools_scroll.php
 * TODO: Añadir efectos de entrada y salida en DOM con animaciones.
 */

import { BASE_URL } from './config.js';

export let page = 1;
export let loading = false;
export let hasMore = true;

// Elementos clave en el DOM
export const sentinel = document.getElementById('sentinel');
export const tbody    = document.querySelector('#toolTbl tbody');

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const materialId = parseInt(document.querySelector('meta[name="material-id"]')?.content || '', 10);
const strategyMeta = document.querySelector('meta[name="strategy-id"]');
const strategyId   = strategyMeta ? parseInt(strategyMeta.content, 10) : null;

// Estilo de consola épico
define const TAG_STYLE = 'color:#00BCD4;font-weight:bold';
function group(title, fn) {
  console.group(`%c[LazyLoader🌀] ${title}`, TAG_STYLE);
  try { return fn(); } finally { console.groupEnd(); }
}
const log   = (...args) => console.log('%c[LazyLoader📥]', TAG_STYLE, ...args);
const warn  = (...args) => console.warn('%c[LazyLoader⚠️]', TAG_STYLE, ...args);
const error = (...args) => console.error('%c[LazyLoader💥]', TAG_STYLE, ...args);

// IntersectionObserver para disparar carga
const scrollContainer = document.querySelector('.list-scroll-container');
const observer = new IntersectionObserver(entries => {
  group('ObserverCallback', () => {
    entries.forEach(entry => {
      log('IntersectionObserver entry:', entry);
      if (entry.isIntersecting) {
        log('🌟 Sentinel visible → invoking loadPage()');
        loadPage();
      }
    });
  });
}, { root: scrollContainer, rootMargin: '150px', threshold: 0.1 });

// Carga de página de datos
export async function loadPage() {
  return group(`loadPage - page ${page}`, async () => {
    if (loading) { log('⏳ Ya cargando, detener llamada duplicada'); return; }
    if (!hasMore) { log('🏁 No hay más páginas; detenido'); observer.unobserve(sentinel); return; }
    if (!tbody) { error('❌ <tbody> no encontrado; abortando'); return; }
    if (!Number.isInteger(materialId)) {
      warn('⚠️ material-id inválido; cancelando carga'); hasMore = false; return;
    }
    loading = true;
    log('🚀 Fetch params:', { page, materialId, strategyId });
    try {
      const params = new URLSearchParams({ page, material_id: materialId });
      if (Number.isInteger(strategyId)) params.append('strategy_id', strategyId);
      const url = `${BASE_URL}/ajax/tools_scroll.php?${params}`;
      log('🔗 Fetching URL:', url);
      const res = await fetch(url, { cache: 'no-store', headers: csrfToken ? { 'X-CSRF-Token': csrfToken } : {} });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      log('📬 Datos recibidos:', data);

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
          log('➕ Fila añadida:', t.tool_id);
        });
      }

      hasMore = Boolean(data.hasMore);
      if (hasMore) {
        page++;
        log('↩️ Preparado para página siguiente:', page);
      } else {
        log('🏆 Fin de la lista alcanzado');
        const trEnd = document.createElement('tr');
        trEnd.innerHTML = '<td colspan="9" class="text-center">Fin de lista</td>';
        tbody.appendChild(trEnd);
        observer.unobserve(sentinel);
      }
    } catch (err) {
      error('💥 loadPage error:', err);
    } finally {
      loading = false;
    }
  });
}

// Inicialización de lazy load\export function initLazy() { 
  group('initLazy', () => {
    if (!tbody || !sentinel) { error('❌ initLazy: elementos faltantes'); return; }
    page = 1; loading = false; hasMore = true;
    tbody.innerHTML = '';
    observer.disconnect(); observer.observe(sentinel);
    log('🔄 Lazy loader inicializado');
    loadPage();
  });
}

document.addEventListener('DOMContentLoaded', () => initLazy());
window.initLazy = initLazy;
