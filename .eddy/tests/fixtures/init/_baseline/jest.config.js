const fs = require('fs');
const path = require('path');

// Discover js/ directories in custom modules and themes, resolving symlinks
// to real paths. Jest resolves symlinks internally, so roots must use real
// paths for test files to be matched.
const dirs = ['web/modules/custom', 'web/themes/custom'];
const roots = [];

dirs.forEach((dir) => {
  if (fs.existsSync(dir)) {
    fs.readdirSync(dir).forEach((name) => {
      const jsDir = path.resolve(dir, name, 'js');
      if (fs.existsSync(jsDir)) {
        roots.push(jsDir);
      }
    });
  }
});

module.exports = {
  // V8 tracks files outside rootDir only when rootDir contains them, so
  // anchor at the project root rather than the build directory.
  rootDir: path.resolve(__dirname, '..'),
  testEnvironment: 'jsdom',
  roots,
  testMatch: ['**/*.test.js'],
  testPathIgnorePatterns: ['/node_modules/', '/vendor/'],
  collectCoverage: true,
  coverageProvider: 'v8',
  coveragePathIgnorePatterns: ['/node_modules/', '/vendor/', '\\.test\\.js$'],
  coverageDirectory: path.resolve(__dirname, '../.logs/coverage/jest'),
  coverageReporters: [
    'text',
    ['html', { subdir: '.coverage-html' }],
    ['cobertura', { file: 'cobertura.xml' }],
  ],
  modulePaths: [path.resolve(__dirname, 'node_modules')],
};
