# Guía de Estilo

Este repositorio usa un tema global definido en `assets/css/base/theme.css`.
Las variables principales son:

```css
:root {
  --clr-bg:     #0d1117;
  --clr-card:   #132330;
  --clr-text:   #e0e0e0;
  --clr-primary:#0ea5e9;
  --clr-accent: #ffd54f;
  --radius-lg:  1rem;
  --spc-1:      .25rem;
  --spc-2:      .5rem;
  --spc-4:      1rem;
}
```

### Tipografía
- Base: `Segoe UI`, `Roboto`, `sans-serif`.
- Usar `<h1>` una sola vez en `layout_wizard.php`, `<h2>` para cada paso y `<h3>` para subtítulos.

### Íconos
- Lucide se carga vía CDN y se inicializa con `createLucideIcons()`.
- Agregalos con `<i data-lucide="icon-name"></i>` únicamente en títulos, botones y tips.

### Responsive
- Desktop ≥1280px mantiene sidebar y tabla.
- 768–1279px: filtros pasan a *offcanvas*.
- <768px: la tabla se muestra en tarjetas.

### Naming
- Prefijos `step-` para archivos y clases específicas de cada paso.
- Utilizá BEM simplificado (`block__elem--mod`).
