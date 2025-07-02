# Wizard CNC Stepper

This project contains a PHP wizard for configuring CNC operations.

## Requirements

- **PHP 7.4+** (Composer uses `php >=7.4`)
- **Composer** for autoloading
- **MySQL/MariaDB** to import the provided database
- **APCu PHP extension** – required for caching. The application still runs
  without APCu but loses caching benefits. See
  [README_scroll_fix.md#pruebas](README_scroll_fix.md#pruebas) for instructions
  on clearing the cache.

## Setup

1. Install Composer dependencies:
   ```bash
   composer install
   ```
2. Create a database called `cnc_calculador` (or adjust the name in your `.env` file).
3. Import `cnc_calculador.sql`:
   ```bash
   mysql -u <user> -p cnc_calculador < cnc_calculador.sql
   ```
4. Copy `.env.example` to `.env` and adjust the database credentials there.
5. Start the application using PHP's built‑in server:
 ```bash
  php -S localhost:8000
  ```
  Then visit [http://localhost:8000](http://localhost:8000).

6. When adding links to CSS, JavaScript or image files in your views, generate the
   path with the `asset()` helper so URLs work from any base path.

## Admin Console

A simple administration panel for managing database records lives under
`/admin/panel/`. Once the PHP server is running, open
`http://localhost:8000/admin/panel/` to log in. The welcome screen also links to
this panel.

## Debug Endpoints

When running with `?debug=1` in the URL you can access additional debug helpers:

- `public/session-api.php` – dumps the session as JSON.
- `public/export.php` – downloads session data as text.
- `public/export_json.php` – downloads session data as JSON.

All these endpoints require debug mode.

## Running Tests

Unit tests can be run with PHPUnit. From the project root execute:

```bash
vendor/bin/phpunit
```

## CSS Linting

Run Stylelint to check the coding style of all CSS files:

```bash
npm run lint:css
```

GitHub Actions executes the same command on every push to ensure consistent formatting.

## Auditing unused CSS

Run the PurgeCSS audit script to see which selectors are not used by the PHP views or JavaScript modules:

```bash
node scripts/purgecss-audit.js
```

The script uses PostCSS and PurgeCSS to scan the templates and JavaScript. It outputs `reports/report-unused-selectors.json` listing every selector removed from the CSS.

## License

This project is licensed under the [MIT License](LICENSE).
