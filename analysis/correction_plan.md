# Plan de Corrección CSS Paso 6

- [ ] Eliminar duplicado de `main.css` en `wizard_layout.php` o ajustar `partials/styles.php`.
- [ ] Reordenar imports en `step6.php` siguiendo ITCSS: primero `step-common.css` (objects) luego `main.css` (components).
- [ ] Revisar función `runStepStyles` para evitar inyecciones repetidas.
- [ ] Extraer estilos inline del mensaje de error de `step6.php` a un CSS.
- [ ] Confirmar si archivos no usados (`components.css`, `elements.css`, etc.) deben eliminarse o importarse.
