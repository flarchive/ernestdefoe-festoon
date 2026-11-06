const config = require('flarum-webpack-config');

/*
 * forum.js and admin.js as usual, plus fireworks.js: a standalone script the
 * server adds to the page only on days that have fireworks. Loading it that
 * way, rather than as a lazy chunk, keeps webpack's chunk loader (about 3 KB)
 * out of forum.js, which every visitor downloads on every page.
 */
module.exports = (...args) => {
  let base = config();
  if (typeof base === 'function') base = base(...args);

  base.entry = { ...base.entry, fireworks: './fireworks.js' };

  return base;
};
