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
2. Create a database called `cnc_calculador` (or adjust the name in `includes/db.php`).
3. Import `cnc_calculador.sql`:
   ```bash
   mysql -u <user> -p cnc_calculador < cnc_calculador.sql
   ```
4. Adjust database credentials in `includes/db.php` if needed.
5. Start the application using PHP's built‑in server:
   ```bash
   php -S localhost:8000
   ```
   Then visit [http://localhost:8000](http://localhost:8000).

## Asset URLs

All CSS, JS and image paths should be generated using the helper
`asset_url()` from `src/Utils/Path.php`. This function prepends the
current `BASE_URL` to the provided relative path. In the browser the same
value is exposed as `window.BASE_URL` so that JavaScript modules can build
their fetch URLs consistently.

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

## License

This project is licensed under the [MIT License](LICENSE).
