# Refactor de estilos del Wizard

Se agregó un tema global con variables CSS en `assets/css/base/theme.css` y se
actualizaron los pasos para usarlo. Los íconos ahora se cargan con Lucide vía CDN
(inicializados en `layout_wizard.php`).

Para probar el proyecto:

```bash
composer install
npm run build
php -S localhost:8000
```

Abrí `http://localhost:8000/index.php` y recorré el asistente en desktop,
tablet y móvil.
