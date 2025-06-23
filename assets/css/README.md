# CSS structure

This folder follows the **ITCSS (Inverted Triangle CSS)** methodology. Styles are organised from the most generic layers to the most specific so that later rules can safely override earlier ones.

## ITCSS folder hierarchy

- **settings** – global variables such as colours and fonts.
- **generic** – resets and generic stylesheets (e.g. normalisation).
- **elements** – base styles for HTML elements.
- **objects** – layout patterns and non-cosmetic wrappers.
- **components** – discrete UI modules.
- **utilities** – helper and override classes.

The directory order under `assets/css` mirrors the cascade:

```text
assets/css/
├── settings
├── generic
├── elements
├── objects
├── components
└── utilities
```

## Adding new styles

1. Place your CSS partial in the directory that matches its layer. Prefix file names with an underscore, for example `_table.css`.
2. Import the partial from `main.css` after the preceding layers so that the cascade flows from `settings` down to `utilities`:

```css
@import url('settings/_variables.css');
@import url('tools/_mixins.css');
@import url('generic/reset.css');
@import url('elements/_typography.css');
@import url('objects/_layout.css');
@import url('components/_button.css');
@import url('utilities/_helpers.css');
```

## Linting

Check code style using Stylelint:

```bash
npm run lint:css
```

Stylelint reads the configuration from `.stylelintrc.json`.

## Wizard step files

Each wizard step’s view loads its specific styles from `assets/css/components`.
