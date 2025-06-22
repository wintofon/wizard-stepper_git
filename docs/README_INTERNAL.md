# Internal Project Notes

This document describes how the wizard application is organized and provides a high-level overview for developers.

## Directory Roles

- `src/` – Autoloaded PHP classes containing the business logic. Controllers, models, utilities and the `StepperFlow` class live here.
- `views/` – PHP templates that render the interface for each wizard step and the overall layout.
- `public/` – Endpoints called via AJAX, such as `load-step.php` and `handle-step.php`.
- `ajax/` and `api/` – Legacy or auxiliary endpoints consumed by front‑end scripts.
- `assets/` – Static assets like CSS and JavaScript modules.
- `includes/` – Procedural helpers, database connection and bootstrap code.
- `tests/` – PHPUnit test suite.
- `docs/` – Additional documentation (e.g. `file_overview.md`).
- `vendor/` and `node_modules/` – Installed Composer and npm packages.

## Wizard Flow

1. **Entry point** – `wizard.php` boots the application, sets security headers and starts the session.
2. **Mode selection** – The user chooses between *manual* and *auto*. The choice is stored in the session.
3. **Step order** – `StepperFlow` defines the valid sequence of steps for each mode.
4. **Loading steps** – `load-step.php` fetches the template for the requested step after verifying session progress.
5. **Submitting data** – `handle-step.php` stores form data in the session and returns the next step number as JSON.
6. **Templates** – The corresponding view under `views/steps/` is rendered for each step.

Dependencies include Composer packages (`monolog/monolog`, `vlucas/phpdotenv`, etc.), npm packages for building the front‑end assets and linting, and a MySQL database imported from `cnc_calculador.sql`.

## Adding or Extending Steps

1. Create the new template under `views/steps/` (either inside `auto/` or `manual/` when mode‑specific).
2. Update `StepperFlow::FLOWS` in `src/StepperFlow.php` to include the new step number.
3. Handle the form data in `public/handle-step.php` and adjust any validation or models.
4. Add the view loading logic if necessary and update client‑side JavaScript or CSS.
5. Review tests under `tests/` and create new ones as needed.

When extending the wizard remember that both Composer dependencies and npm scripts (`npm run build`, `npm run lint:css`) may need to be reinstalled with `composer install` and `npm install`.
