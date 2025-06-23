/*
 * File: dashboard.js
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * TODO: Extend documentation.
 */
/*  wizard/assets/js/dashboard.js  —  v2
    Refuerzo contra respuestas “casi-JSON” (cabecera JSON
    pero texto contaminado con warnings, BOM, etc.).        */

const BASE_URL = window.BASE_URL;
const L = window.Logger;
document.addEventListener('DOMContentLoaded', () => {
  const dash = document.getElementById('wizard-dashboard');

  let lastOk     = null;          // último snapshot correcto
  let errorCount = 0;             // errores consecutivos
  const MAX_ERR  = 5;             // corta peticiones tras este nº

  /* ---------- helpers ---------- */
  const paint = json => L.table(json);

  const warn  = msg => L.warn(msg);

  /* ---------- fetch c/2 s ---------- */
  const fetchSession = async () => {
    if (errorCount >= MAX_ERR) return;
    const endG = L.group('Dashboard fetchSession');
    L.log('start', { errorCount });

    try {
      const headers = window.csrfToken ? { 'X-CSRF-Token': window.csrfToken } : {};
      const res  = await fetch(`${BASE_URL}/public/session-api.php?debug=1`, {
        cache: 'no-store',
        headers
      });
      const cTyp = res.headers.get('Content-Type') || '';

      if (!res.ok || !cTyp.includes('application/json')) {
        throw new Error(`HTTP ${res.status} – Content-Type “${cTyp}”`);
      }

      /* — NEW — lee texto y limpia basura previa --------------- */
      let text = await res.text();
      text     = text.trimStart();                   // quita espacios/BOM

      /* Si empieza con ‘{’ o ‘[’ extrae desde ahí (ignora “v{…”) */
      const firstBrace = text.search(/[{[]/);
      if (firstBrace > 0) text = text.slice(firstBrace);

      if (!text.startsWith('{') && !text.startsWith('[')) {
        throw new Error('payload no parece JSON');
      }

      const data = JSON.parse(text);                // ahora seguro
      paint(data);                                  // ✔️ snapshot ok
      lastOk     = data;
      errorCount = 0;
      L.log('snapshot ok');
    } catch (err) {
      L.error('Respuesta inválida', err);

      errorCount++;
      const hint = `Fallo ${errorCount}/${MAX_ERR}: ${err.message}`;

      /* mantiene último JSON correcto si existe */
      lastOk ? (paint(lastOk), warn(hint)) : L.warn(hint);
    } finally {
      endG();
    }
  };

  fetchSession();                  // primera llamada inmediata
  setInterval(fetchSession, 2000); // bucle
});
