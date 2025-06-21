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
document.addEventListener('DOMContentLoaded', () => {
  const dash = document.getElementById('wizard-dashboard');
  if (!dash) return;

  let lastOk     = null;          // último snapshot correcto
  let errorCount = 0;             // errores consecutivos
  const MAX_ERR  = 5;             // corta peticiones tras este nº

  /* ---------- helpers ---------- */
  const paint = json =>
    dash.innerHTML = `<pre style="margin:0;">${JSON.stringify(json, null, 2)}</pre>`;

  const warn  = msg =>
    dash.innerHTML = `<pre style="margin:0;color:#ffb86c;">⚠️  ${msg}</pre>`;

  /* ---------- fetch c/2 s ---------- */
  const fetchSession = async () => {
    if (errorCount >= MAX_ERR) return;

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
    } catch (err) {
      console.warn('[Dashboard] Respuesta inválida:', err.message);

      errorCount++;
      const hint = `Fallo ${errorCount}/${MAX_ERR}: ${err.message}`;

      /* mantiene último JSON correcto si existe */
      lastOk ? (paint(lastOk), warn(hint)) : (dash.textContent = hint);
    }
  };

  fetchSession();                  // primera llamada inmediata
  setInterval(fetchSession, 2000); // bucle
});
