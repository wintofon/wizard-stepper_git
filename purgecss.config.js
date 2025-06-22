module.exports = {
  content: [
    './views/**/*.php',
    './public/**/*.php',
    './src/**/*.php',
    './assets/js/**/*.js',
    './**/*.html'
  ],
  css: ['assets/css/**/*.css'],
  safelist: {
    standard: [/^is-/, /^has-/],
    deep: [/^vue-/, /^react-/],
    greedy: ['active', 'open']
  }
};
