const fs = require('fs');
const postcss = require('postcss');
const safeParser = require('postcss-safe-parser');
const glob = require('glob');

const files = glob.sync('assets/css/**/*.css', {
  ignore: ['**/bootstrap*.css']
});
const selectorMap = new Map();

files.forEach(file => {
  let css = fs.readFileSync(file, 'utf8');
  css = css.replace(/<\?[^>]*\?>/g, '');
  postcss.parse(css, { parser: safeParser }).walkRules(rule => {
    const key = rule.selector;
    const decls = rule.nodes
      .filter(n => n.type === 'decl')
      .map(d => `${d.prop}:${d.value}`)
      .sort()
      .join(';');
    const combo = key + '|' + decls;
    if (!selectorMap.has(combo)) {
      selectorMap.set(combo, []);
    }
    selectorMap.get(combo).push(file);
  });
});

const duplicates = Array.from(selectorMap.entries()).filter(([,files]) => files.length > 1);

duplicates.forEach(([rule, files]) => {
  const [selector, props] = rule.split('|');
  console.log(selector, '=>', files.join(', '));
});
