#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const { glob } = require('glob');
const postcss = require('postcss');
const purgecss = require('@fullhuman/postcss-purgecss').default;

async function audit() {
  // Skip minified files which might trigger parser errors
  const cssFiles = glob.sync('assets/css/**/*.css', {
    ignore: ['**/*.min.css']
  });
  const report = {};
  for (const file of cssFiles) {
    const css = fs.readFileSync(file, 'utf8');
    const result = await postcss([
      purgecss({
        content: ['views/**/*.php', 'assets/js/**/*.js'],
        rejected: true
      })
    ]).process(css, { from: file });
    const selectors = [];
    for (const msg of result.messages) {
      if (msg.type === 'purgecss') {
        const parts = msg.text.split('\n').slice(1);
        for (const sel of parts) {
          const trimmed = sel.trim();
          if (trimmed) selectors.push(trimmed);
        }
      }
    }
    if (selectors.length > 0) {
      report[file] = selectors;
    }
  }
  fs.mkdirSync('reports', { recursive: true });
  fs.writeFileSync(
    path.join('reports', 'report-unused-selectors.json'),
    JSON.stringify(report, null, 2) + '\n'
  );
}

audit().catch((err) => {
  console.error(err);
  process.exit(1);
});
