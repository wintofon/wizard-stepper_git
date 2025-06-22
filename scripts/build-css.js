#!/usr/bin/env node
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const { glob } = require('glob');

const MAX_SIZE_KB = 50; // size limit

function run(cmd) {
  execSync(cmd, { stdio: 'inherit' });
}

function build() {
  fs.rmSync('dist', { recursive: true, force: true });
  fs.mkdirSync('dist', { recursive: true });

  run("postcss 'assets/css/**/*.css' -d dist");
  run("npx purgecss --config purgecss.config.js --css dist/**/*.css --output dist");

  const files = glob.sync('dist/**/*.css');
  const concatenated = files.map(f => fs.readFileSync(f, 'utf8')).join('\n');
  fs.writeFileSync('dist/combined.css', concatenated);
  run('npx lightningcss dist/combined.css -o dist/style.min.css --minify');

  const size = fs.statSync(path.join('dist', 'style.min.css')).size / 1024;
  if (size > MAX_SIZE_KB) {
    console.error(`CSS size ${size.toFixed(2)}KB exceeds ${MAX_SIZE_KB}KB limit`);
    process.exit(1);
  }
}

build();
