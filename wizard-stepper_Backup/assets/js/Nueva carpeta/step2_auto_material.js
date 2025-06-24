// ====================================
// JS de Paso 4 – Selección de material y espesor (Modo Automático)
// Este archivo hace que los selects y el input sean dinámicos y dependientes.
// ====================================

document.addEventListener('DOMContentLoaded', () => {
  // --- Referencias a los elementos de la UI ---
  const catSelect  = document.getElementById('category_id');   // Combo de categoría
  const matSelect  = document.getElementById('material_id');   // Combo de material
  const thickInput = document.getElementById('thickness');     // Input de espesor
  const submitBtn  = document.getElementById('submitBtn');     // Botón de siguiente
  const form       = document.getElementById('autoMaterialForm');

  // --- Utilidades internas ---

  // Resetea el select de materiales y el espesor cuando cambia la categoría
  function resetMaterial(msg) {
    matSelect.innerHTML = `<option>${msg}</option>`;
    matSelect.disabled = true;
    resetThickness();
    updateSubmitButton();
  }
  // Resetea y deshabilita el campo de espesor
  function resetThickness() {
    thickInput.value = '';
    thickInput.disabled = true;
    updateSubmitButton();
  }
  // Controla si todo está lleno y válido para habilitar el botón
  function updateSubmitButton() {
    const allFilled = catSelect.value && matSelect.value && parseFloat(thickInput.value) > 0;
    submitBtn.disabled = !allFilled;
  }

  // --- Listeners y lógicas principales ---

  // Cuando cambia la categoría: carga materiales por AJAX
  catSelect.addEventListener('change', () => {
    const catId = parseInt(catSelect.value, 10);
    if (!catId) return resetMaterial('-- Primero elegí categoría --');
    matSelect.innerHTML = '<option>Cargando...</option>';
    // AJAX puro: busca los materiales de esa categoría al mismo PHP, que responde JSON
    fetch(`?ajax=1&category_id=${catId}`)
      .then(res => res.json())
      .then(data => {
        matSelect.innerHTML = '<option value="">-- Elegí material --</option>';
        data.forEach(item => {
          const opt = document.createElement('option');
          opt.value = item.material_id;
          opt.textContent = item.name;
          matSelect.appendChild(opt);
        });
        matSelect.disabled = false;
        updateSubmitButton();
      })
      .catch(() => resetMaterial('Error al cargar materiales'));
  });

  // Cuando cambia el material: habilita/deshabilita el input de espesor
  matSelect.addEventListener('change', () => {
    thickInput.disabled = !matSelect.value;
    if (!matSelect.value) thickInput.value = '';
    updateSubmitButton();
  });

  // Cuando escriben espesor, valida y habilita el botón
  thickInput.addEventListener('input', () => {
    const val = parseFloat(thickInput.value);
    if (thickInput.value !== '' && val === 0) {
      alert('El espesor no puede ser cero.');
    }
    updateSubmitButton();
  });

  // Si le dan submit sin completar todo, cancela y alerta
  form.addEventListener('submit', e => {
    if (submitBtn.disabled) {
      e.preventDefault();
      alert('Completá todos los campos para continuar.');
    }
  });
});
