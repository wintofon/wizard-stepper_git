# CSS architecture

This project adopts **ITCSS (Inverted Triangle CSS)** to keep styles scalable and maintainable.

## Folder overview

- **abstracts/** – global variables and mixins.
- **base/** – reset rules and basic typography.
- **layout/** – structural styles such as the wizard container and stepper.
- **components/** – reusable UI pieces (buttons, cards, sliders, footer).
- **pages/** – step specific styles used by the wizard steps.
- **themes/** – optional color themes.
- **vendors/** – third‑party overrides (e.g. Bootstrap).
- **main.css** – imports every partial in the proper ITCSS order.

## Adding styles

1. Place new variables in `abstracts/_variables.css`.
2. Add structural or component styles in the appropriate folder.
3. Import the new file from `main.css` following the ITCSS layers.
4. Run `npm run lint:css` to ensure Stylelint rules pass.

## Stylelint

Stylelint is configured via `.stylelintrc.json`. Run the linter with:

```bash
npm run lint:css
```

It checks property order, duplicates and general consistency.
