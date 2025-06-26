/*
 * File: step1_manual_lazy_loader.js
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * TODO: Extend documentation.
 */
// Lazy loading table rows using IntersectionObserver
import { BASE_URL } from './config.js';
export let page = 1;
export let loading = false;
export let hasMore = true;

export const sentinel = document.getElementById('sentinel');
export const tbody = document.querySelector('#toolTbl tbody');
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const materialId = parseInt(
  document.querySelector('meta[name="material-id"]')?.content || '',
  10,
);
const strategyMeta = document.querySelector('meta[name="strategy-id"]');
const strategyId = strategyMeta ? parseInt(strategyMeta.content, 10) : null;

console.log('Sentinel:', sentinel);

const scrollContainer = document.querySelector('.list-scroll-container');
const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      console.log('Observer entry:', entry);
      if (entry.isIntersecting) {
        console.log('Sentinel visible \u2192 loadPage()');
        loadPage();
      }
    });
  },
  { root: scrollContainer, rootMargin: '200px', threshold: 0.1 },
);

export async function loadPage() {
  if (loading || !hasMore || !tbody) return;
  if (!Number.isInteger(materialId)) {
    console.warn('Missing material_id; aborting lazy load');
    hasMore = false;
    return;
  }
  loading = true;
  try {
    const params = new URLSearchParams({
      page,
      material_id: materialId,
    });
    if (Number.isInteger(strategyId)) {
      params.append('strategy_id', strategyId);
    }
    const res = await fetch(`${BASE_URL}/ajax/tools_scroll.php?${params}`, {
      cache: 'no-store',
      headers: csrf ? { 'X-CSRF-Token': csrf } : {},
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (Array.isArray(data.tools)) {
      data.tools.forEach((t) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td><button class="btn-select" data-tool_id="${t.tool_id}" data-tbl="${t.tbl}"><span>Seleccionar</span><i data-feather="arrow-right"></i></button></td>
          <td><span class="badge bg-info text-dark">${t.brand ?? ""}</span></td>
          <td>${t.series ?? t.series_code ?? ""}</td>
          <td>${t.img_url ? `<img src="${t.img_url}" class="thumb">` : ""}</td>
          <td>${t.tool_code ?? ""}</td>
          <td class="text-truncate" style="max-width:200px">${t.name ?? ""}</td>
          <td>${t.diameter_mm ?? ""}</td>
          <td>${t.flute_count ?? ""}</td>
          <td>${t.tool_type ?? ""}</td>`;
        tbody.appendChild(tr);
      });
      if (window.feather) feather.replace();
    }
    hasMore = data.hasMore;
    if (hasMore) {
      page++;
    } else {
      observer.unobserve(sentinel);
      const end = document.createElement("tr");
      const endTd = document.createElement("td");
      endTd.colSpan = 9;
      endTd.className = "text-center";
      endTd.textContent = "Fin de lista";
      end.appendChild(endTd);
      tbody.appendChild(end);
    }
  } catch (err) {
    console.error("loadPage error:", err);
  } finally {
    loading = false;
  }
}

export function initLazy() {
  if (tbody && sentinel) {
    page = 1;
    loading = false;
    hasMore = true;
    tbody.innerHTML = "";
    observer.disconnect();
    observer.observe(sentinel);
    loadPage();
  }
}

document.addEventListener("DOMContentLoaded", initLazy);
window.initLazy = initLazy;
