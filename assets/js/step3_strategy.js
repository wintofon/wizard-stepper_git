/* global estrategiaMap */

document.addEventListener('DOMContentLoaded', () => {
  const selTipo = document.getElementById('machining_type_id');
  const selStrat = document.getElementById('strategy_id');

  selTipo.addEventListener('change', () => {
    const tipoId = selTipo.value;
    selStrat.innerHTML = '';

    if (!tipoId || !estrategiaMap[tipoId]) {
      selStrat.disabled = true;
      selStrat.innerHTML = '<option>-- Primero elegí un tipo --</option>';
      return;
    }

    const list = estrategiaMap[tipoId].estrategias;
    selStrat.innerHTML = '<option value="">-- Elegí una estrategia --</option>';
    list.forEach(e => {
      const opt = document.createElement('option');
      opt.value = e.id;
      opt.textContent = e.name;
      selStrat.appendChild(opt);
    });

    selStrat.disabled = false;
  });
});
