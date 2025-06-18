# Guía de Estilo Wizard CNC

## Paleta y variables
Las variables se definen en `assets/css/base/theme.css`.
- `--clr-bg`: color de fondo global.
- `--clr-card`: color de tarjetas y contenedores.
- `--clr-text`: color de texto principal.
- `--clr-primary`: tono destacado del stepper.
- `--clr-accent`: acento secundario.
- `--radius-lg`: radio general de bordes.
- `--spc-1/2/4`: escala de espaciados.

## Tipografía
Se usa `Segoe UI`, con fallback `Roboto` y `sans-serif`.

## Estructura HTML
- Un solo `<h1>` global en `layout_wizard.php`.
- Cada vista de paso comienza con `<h2 class="step-title">`.
- Subtítulos internos utilizan `<h3>` si es necesario.

## Iconografía
Se utilizan iconos **Lucide** mediante el atributo `data-lucide`.
Cargá el script ES Module:
```html
<script type="module" crossorigin src="https://cdn.jsdelivr.net/npm/lucide@latest/+esm"></script>
<script>window.lucide && window.lucide.createIcons();</script>
```
Ejemplo:
```html
<i data-lucide="search"></i>
```

## Naming conventions
Clases en inglés, guion-medio (`step-title`, `wizard-body`).
Evitar abreviaturas confusas.
