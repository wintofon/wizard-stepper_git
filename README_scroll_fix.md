# Fix de Scroll Infinito en Paso 1

## Causa raíz
El listado de herramientas en `step1_manual_tool_browser.php` se cargaba usando un script inline que inicializaba la lógica de scroll. La política CSP bloqueaba estos scripts inline por no estar firmados y el token CSRF se pasaba también mediante otro script inline. Como resultado el `IntersectionObserver` nunca disparaba porque el JS no se ejecutaba y la primera página quedaba vacía.

## Solución
Se movieron los scripts inline a un módulo externo `assets/js/step1_manual_selection_hook.js`. El token CSRF ahora se expone mediante una etiqueta `<meta>` y es leído por los módulos. La CSP se actualizó para permitir únicamente scripts propios y uno firmado con `nonce`. Dentro de `step1_manual_lazy_loader.js` se ajustó la inicialización para que siempre cargue la primera página y se añadió un alto mínimo al *sentinel*.

```css
#sentinel {
  min-height: 40px;
}
```

## Pasos para reproducir
1. Abrir `step1_manual_tool_browser.php` antes de aplicar el fix y observar en la consola del navegador errores CSP y que la lista no se carga.
2. Con la actualización, recargar la página y verificar que el primer lote de fresas aparece automáticamente.
3. Hacer scroll hasta que `hasMore` sea `false` para confirmar que el scroll infinito funciona.

## Cabeceras CSP finales
```
Content-Security-Policy: default-src 'self';
        script-src 'self' 'nonce-<valor>'; 
        style-src  'self' 'unsafe-inline';
```

## Pruebas
- `./vendor/bin/phpunit` – ejecuta las pruebas unitarias (si existen).
- `npm run lint` – ejecuta `eslint` sobre los archivos JS.
- `composer run-script lint` – ejecuta `php -l` sobre los archivos PHP.

Para limpiar la caché APCu manualmente:
```bash
php -r 'apcu_clear_cache();'
```
