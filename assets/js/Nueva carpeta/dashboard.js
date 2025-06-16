// assets/js/dashboard.js
const dash = document.getElementById('wizard-dashboard');
function render(data) {
  if (!dash) return;
  dash.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
}
setInterval(() => {
  fetch('session-api.php')
    .then(res => res.json())
    .then(render)
    .catch(() => {/* ignore */});
}, 2000);
