# CSS structure using ITCSS

This project organizes styles following the Inverted Triangle CSS (ITCSS) methodology. CSS files are grouped into layers from generic to specific.

## Folder overview

- **abstracts/** – global variables and mixins.
- **base/** – resets and typographic rules.
- **layout/** – structural styles such as the stepper layout and grid helpers.
- **components/** – reusable UI components (buttons, cards, sliders, etc.).
- **pages/** – step specific rules for the wizard screens.
- **themes/** – colour themes (light/dark).
- **vendors/** – third‑party overrides (e.g. Bootstrap).
- `main.css` – imports every layer in the correct order.

## Adding new styles

1. Determine the correct layer for the new rules.
2. Create a file in that folder or extend an existing one.
3. Import it from `main.css` maintaining the ITCSS order.

## Stylelint

Run `npm run lint:css` to check style consistency. The configuration is located in `.stylelintrc.json` and enforces standard ordering and formatting rules.
