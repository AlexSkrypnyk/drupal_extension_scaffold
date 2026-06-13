@@ -17,7 +17,6 @@
 - **Rector**: `make lint` (dry-run) / `make lint-fix` - never `vendor/bin/rector`.
 - **Twig CS Fixer**: `make lint` / `make lint-fix` - never `vendor/bin/twig-cs-fixer`.
 - **ESLint / Stylelint**: `make lint` / `make lint-fix` - never `npx eslint` or `npx stylelint`.
-- **CSpell**: `make lint` - never `npx cspell`.
 - **PHPUnit**: `make test` / `make test-unit` / `make test-kernel` / `make test-functional` - never `vendor/bin/phpunit`.
 - **Jest**: `make test-js` - never `npx jest`.
 - **Drush**: `make drush <command>` - never `build/vendor/bin/drush` directly.
@@ -102,7 +101,6 @@
 
 ## Code Quality Tools
 
-- **CSpell**: Spell checking across the codebase (config at `.cspell.json`)
 - **PHPCS**: Drupal and DrupalPractice standards
 - **PHPStan**: Static analysis with Drupal extensions
 - **Rector**: Automated refactoring and deprecation fixes
