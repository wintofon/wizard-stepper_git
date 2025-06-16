document.addEventListener('DOMContentLoaded', function() {
  const radios  = document.querySelectorAll('input[name="transmission_id"]');
  const fields  = ['rpm_min','rpm_max','feed_max','hp'].map(id => document.getElementById(id));
  const nextBtn = document.getElementById('nextBtn');

  function toggleFields() {
    const sel = document.querySelector('input[name="transmission_id"]:checked');
    if (!sel) {
      fields.forEach(f => { f.disabled = true; f.value = ''; });
      nextBtn.disabled = true;
      return;
    }
    const lbl = document.querySelector('label[for="' + sel.id + '"]');
    fields.forEach(f => f.disabled = false);
    document.getElementById('rpm_min').value  = lbl.dataset.rpmmin;
    document.getElementById('rpm_max').value  = lbl.dataset.rpmmax;
    document.getElementById('feed_max').value = lbl.dataset.feed;
    document.getElementById('hp').value       = lbl.dataset.hp;
    nextBtn.disabled = false;
  }

  radios.forEach(r => r.addEventListener('change', toggleFields));
  toggleFields();

  document.getElementById('machineConfigForm').addEventListener('submit', function(e) {
    const msgs = [];
    const sel = document.querySelector('input[name="transmission_id"]:checked');
    if (!sel) msgs.push('Debés elegir una transmisión.');
    const min  = parseInt(document.getElementById('rpm_min').value, 10);
    const max  = parseInt(document.getElementById('rpm_max').value, 10);
    const feed = parseFloat(document.getElementById('feed_max').value);
    const hp   = parseFloat(document.getElementById('hp').value);
    if (!min || min <= 0 || !max || max <= 0)
      msgs.push('Las RPM deben ser enteros mayores que cero.');
    if (min > max)
      msgs.push('La RPM mínima no puede ser mayor que la máxima.');
    if (!feed || feed <= 0)
      msgs.push('El avance máximo debe ser mayor que cero.');
    if (!hp || hp <= 0)
      msgs.push('La potencia (HP) debe ser mayor que cero.');
    if (msgs.length) {
      e.preventDefault();
      alert(msgs.join('\n'));
    }
  });
});
