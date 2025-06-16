// JS para Paso 5 Auto: Estrategia (AJAX, selects y validación UI)
document.addEventListener('DOMContentLoaded', () => {
  const typeSel  = document.getElementById('machining_type_id');
  const stratSel = document.getElementById('strategy_id');
  const btn      = document.getElementById('submitBtn');

  typeSel.addEventListener('change', () => {
    const id = typeSel.value;
    stratSel.innerHTML = '<option>Cargando...</option>';
    stratSel.disabled = true;
    btn.disabled = true;
    if (!id) {
      stratSel.innerHTML = '<option>-- Primero elegí un tipo --</option>';
      return;
    }
    fetch(`?ajax=1&machining_type_id=${id}`)
      .then(r => r.json())
      .then(data => {
        stratSel.innerHTML = '<option value="">-- Elegí estrategia --</option>';
        data.forEach(item => {
          const o = document.createElement('option');
          o.value = item.strategy_id;
          o.textContent = item.name;
          stratSel.appendChild(o);
        });
        stratSel.disabled = false;
      })
      .catch(() => {
        stratSel.innerHTML = '<option>Error al cargar</option>';
      });
  });
  stratSel.addEventListener('change', () => {
    btn.disabled = !stratSel.value;
  });
  stratSel.disabled = !typeSel.value;
  btn.disabled     = true;
});
