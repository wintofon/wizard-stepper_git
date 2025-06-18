# Refactor de estilos Wizard CNC

Esta actualización agrega un theme global y reemplaza los íconos Feather por Lucide.

## Cómo probar
1. Instalar dependencias opcionales:
   ```bash
   npm install
   ```
2. Ejecutar lint y verificación de PHP:
   ```bash
   npm run lint:css
   find . -name '*.php' -exec php -l {} \;
   ```
3. Abrí `index.php` en tu servidor y recorré los pasos. Probá en móvil, tablet y escritorio.

El layout se adaptará a tres tamaños y los íconos se renderizan al cargar cada paso.
