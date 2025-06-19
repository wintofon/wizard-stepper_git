# CSS structure

This folder follows the **ITCSS (Inverted Triangle CSS)** methodology. Styles are organised from generic to specific so cascade conflicts are minimised.

## Directory mapping

- **abstracts** – global variables and mixins (top of the triangle, similar to *Settings*).
- **base** – resets and basic element styles (*Elements* layer).
- **components** – reusable UI pieces such as buttons or sliders (*Components* layer).
- **pages** – styles scoped to individual steps or pages.
- **steps** – legacy step files kept for reference.
- **utilities** – helper classes (bottom of the triangle, comparable to *Trumps*).

## Adding new styles

1. Choose the folder that matches the scope of your styles.
   - Global variables go into `abstracts/`.
   - Element-level rules belong in `base/`.
   - Reusable widgets go into `components/`.
   - Page‑specific rules live under `pages/`.
2. Name partials with a leading underscore (`_example.css`) and import them from a parent file such as `main.css` or the relevant page CSS.
3. Keep import order from generic to specific, as in `main.css`:

```css
@import url('abstracts/_variables.css');
@import url('base/_reset.css');
@import url('base/_typography.css');
@import url('components/_common.css');
```

## Linting

Run `npm run lint:css` to check the code style. The rules come from `.stylelintrc.json` and enforce the standard configuration plus alphabetical property order.

## Wizard step files

Each wizard step loads a dedicated stylesheet:

- Step 1 (manual) – `pages/_step1.css` and `pages/_manual.css`.
- Step 1 (auto) – `material.css`.
- Step 2 – `pages/_step2.css` (`strategy.css` and `step-common.css` are also included in auto mode).
- Step 3 (manual) – `strategy.css` and `step-common.css`.
- Step 3 (auto) – `pages/_step3_auto.css` (`pages/_step3.css` when using the lazy/scroll view).
- Step 4 (manual) – `material.css`.
- Step 4 (auto) – `pages/_step2.css`.
- Step 5 – `step-common.css` and `pages/_step5.css`.
- Step 6 – `pages/_step6.css` (plus `pages/_step6-dark.css` for the dark theme).
