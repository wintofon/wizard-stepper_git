// JS para el filtro por diámetro en el paso de selección de herramientas recomendadas
document.addEventListener('DOMContentLoaded', function () {
  const filter = document.getElementById('diameterFilter');
  if (!filter) return;
  filter.addEventListener('change', function() {
    const val = this.value;
    document.querySelectorAll('.fresa-card').forEach(card => {
      card.style.display = (!val || card.dataset.diameter === val) ? '' : 'none';
    });
  });
});
