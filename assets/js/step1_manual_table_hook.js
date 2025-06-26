/*
 * File: step1_manual_table_hook.js
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * TODO: Extend documentation.
 */
export function dbg(...m) {
  console.log('[DBG]', ...m);
}

export function initToolTable() {
  window.dbg = dbg;
  dbg('hook externo activo');
  const tbl = document.getElementById('toolTbl');
  if (!tbl) {
    dbg('tabla no encontrada');
    return;
  }

  tbl.addEventListener('click', e => {
    const btn = e.target.closest('.btn-select');
    if (!btn) return;
    document.getElementById('tool_id').value = btn.dataset.tool_id;
    document.getElementById('tool_table').value = btn.dataset.tbl;
    document.getElementById('step1ManualForm').requestSubmit();
    dbg('► herramienta seleccionada:', btn.dataset.tbl, btn.dataset.tool_id);
  });
}
