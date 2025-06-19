# CSS Architecture

This directory follows the **ITCSS** (Inverted Triangle CSS) approach. Styles are split into layers from generic to specific so that cascade and overrides remain predictable.

## Folder overview

- **abstracts/** – global variables and mixins.
- **base/** – resets and typography.
- **layout/** – structural layout such as the stepper bar and general grid.
- **components/** – reusable widgets like buttons, cards and sliders.
- **pages/** – step–specific styles.
- **themes/** – optional color themes.
- **vendors/** – third‑party overrides (Bootstrap, etc.).
- **main.css** – imports all layers in ITCSS order.

## Adding styles

1. Place generic variables inside `abstracts/_variables.css`.
2. Base elements or typography go under `base/`.
3. Layout scaffolding lives in `layout/`.
4. Reusable UI pieces belong to `components/`.
5. Page or step specific rules should be stored in `pages/`.
6. Theme modifications extend variables in `themes/`.

Run `npm run lint:css` to validate with **stylelint**.
