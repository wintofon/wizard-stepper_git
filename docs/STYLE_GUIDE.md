# Guía de Estilos

Este proyecto usa **Bootstrap 5.3** y CSS modular. Para mantener la coherencia:

- Importá `assets/css/base/theme.css` en cada hoja.
- Utilizá las variables `--clr-*` para colores y `--spc-*` para espaciados.
- Títulos: `h1` único en `layout_wizard.php`; cada paso usa `h2` y subtítulos `h3`.
- Los íconos Lucide se cargan desde `assets/js/lucide.js` y se inicializan con `lucide.createIcons()`.
- Clases utilitarias siguen la nomenclatura de Bootstrap.
