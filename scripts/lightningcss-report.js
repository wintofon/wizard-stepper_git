#!/usr/bin/env node
const { execSync } = require('child_process');
const fs = require('fs');

function run() {
  const output = execSync('npx lightningcss assets/css --analyze', { encoding: 'utf8' });
  fs.mkdirSync('reports', { recursive: true });
  fs.writeFileSync('reports/lightningcss-report.json', JSON.stringify({ analysis: output }, null, 2));
}

run();
