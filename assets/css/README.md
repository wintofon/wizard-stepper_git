# ITCSS Structure

This project organizes stylesheets following the **Inverted Triangle CSS** methodology (ITCSS). The structure helps keep global rules separated from component and page level styles to ease maintenance.

## Folders

- **abstracts**: global variables and mixins.
- **base**: resets and base typography.
- **layout**: overall layout pieces such as stepper, footer and grid utilities.
- **components**: reusable UI components (buttons, cards, sliders…).
- **pages**: step specific styles for the wizard and onboarding screens.
- **themes**: optional theme overrides.
- **vendors**: overrides for third‑party libraries.

## Adding new styles

1. Place new variables in `abstracts/_variables.css`.
2. Add global element rules under `base/`.
3. Layout or structural rules go in `layout/`.
4. Component styles belong in `components/`.
5. Create a new file in `pages/` for page specific rules.
6. Import the file inside `main.css` respecting the ITCSS order.

## Using Stylelint

Run `npm run lint:css` to lint all CSS files. The configuration uses `stylelint-config-standard` with property ordering via `stylelint-order`.
