// assets/js/wizard.js
document.addEventListener('DOMContentLoaded', () => {
  // STEP4: cargar lista de fresas
  const loadBtn = document.getElementById('loadTools');
  if (loadBtn) {
    loadBtn.addEventListener('click', async () => {
      const modo  = document.querySelector('input[name=modo_sel]:checked').value;
      const tipo  = document.getElementById('tipo').value;
      const estr  = document.getElementById('estrategia').value;
      // asumimos MATERIAL_ID definido globalmente en PHP si lo necesitás
      let url = `fetch_tools.php?material_id=${encodeURIComponent(window.MATERIAL_ID)}`;
      if (modo === 'recomendar') {
        url += `&tipo=${encodeURIComponent(tipo)}&estr=${encodeURIComponent(estr)}`;
      }
      const resp = await fetch(url);
      const arr  = await resp.json();
      document.getElementById('toolsList').innerHTML = arr.map(t => `
        <div class="form-check mb-2">
          <input class="form-check-input" type="radio" 
                 name="tool_id" id="tool${t.tool_id}"
                 value="${t.tool_id}">
          <label class="form-check-label" for="tool${t.tool_id}">
            <img src="../panel/${t.image||''}" class="thumb me-2">
            ${t.name} ★${t.rating}
          </label>
        </div>
      `).join('');
    });
  }

  // STEP5: calcular feed & speed
  const calcBtn = document.getElementById('calcBtn');
  if (calcBtn) {
    calcBtn.addEventListener('click', async () => {
      const form = document.getElementById('fsForm');
      const data = {
        tool_id:     form.tool_id.value,
        material_id: form.material_id.value,
        thickness:   form.thickness.value,
        fz_slider:   form.fz_slider?.value || 0,
        vf_slider:   form.vf_slider?.value || 0,
        passes:      form.passes?.value || 1
      };
      const res = await fetch('../wizard/fetch.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(data)
      });
      const j = await res.json();
      document.getElementById('results').innerHTML = `
        <table class="table">
          ${Object.entries(j).map(([k,v]) => `
            <tr><th>${k}</th><td>${v}</td></tr>
          `).join('')}
        </table>
      `;
    });
  }
});
