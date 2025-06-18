// Lazy loading of tool rows via IntersectionObserver
export let page = 1;
export let loading = false;
export let hasMore = true;

const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
export const sentinel = document.getElementById("sentinel");
export const tbody = document.querySelector("#toolTbl tbody");

const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) loadPage();
    });
  },
  { rootMargin: "200px", threshold: 0.1 },
);

export async function loadPage() {
  if (loading || !hasMore || !tbody) return;
  loading = true;
  try {
    const res = await fetch(
      `/wizard-stepper_git/ajax/tools_scroll.php?page=${page}`,
      {
        cache: "no-store",
        headers: csrf ? { "X-CSRF-Token": csrf } : {},
      },
    );
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (Array.isArray(data.tools)) {
      data.tools.forEach((t) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td><input type="radio" class="form-check-input select-btn" data-tool_id="${t.tool_id}" data-tbl="${t.tbl}"></td>
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
    }
    page = data.nextPage;
    hasMore = data.hasMore;
    if (!hasMore) {
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
    observer.observe(sentinel);
    loadPage();
  }
}

document.addEventListener("DOMContentLoaded", initLazy);
if (document.readyState !== "loading") initLazy();
window.initLazy = initLazy;
