# Guía de Estilo Wizard CNC

Esta refactorización unifica la apariencia del asistente.

## Paleta
- **Fondo** `#0d1117`
- **Cards** `#161b22`
- **Texto** `#e0e0e0`
- **Primario** `#ffd54f`
- **Acento** `#4fc3f7`

Todas las hojas deben importar `assets/css/base/theme.css` y usar las variables CSS.

## Tipografía
Utilizamos `Segoe UI`, `Roboto`, `sans-serif`. No declarar fuentes extras.

## Espaciado
- `var(--spc-1)` = 4 px
- `var(--spc-2)` = 8 px
- `var(--spc-4)` = 16 px

## Iconografía
Se emplea **Lucide** mediante CDN ESModule:
```html
<script type="module" crossorigin src="https://cdn.jsdelivr.net/npm/lucide@latest/+esm"></script>
```
Los íconos se insertan con `<i data-lucide="nombre"></i>`.

## Jerarquía de títulos
- `<h1>` único oculto en `layout_wizard.php`.
- `<h2>` para título de cada paso.
- `<h3>` para subtítulos internos.

## Naming
Clases en inglés y en minúsculas con guiones (`btn-next`, `tool-image`).
