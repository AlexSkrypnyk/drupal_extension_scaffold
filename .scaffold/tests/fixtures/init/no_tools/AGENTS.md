@@ -12,14 +12,6 @@
 
 Run each tool through its wrapper (the `make` targets below; `ahoy` mirrors each one) - never the binary directly:
 
-- **PHPCS / PHPCBF**: `make lint` / `make lint-fix` - never `vendor/bin/phpcs` or `vendor/bin/phpcbf`.
-- **PHPStan**: `make lint` - never `vendor/bin/phpstan`.
-- **Rector**: `make lint` (dry-run) / `make lint-fix` - never `vendor/bin/rector`.
-- **Twig CS Fixer**: `make lint` / `make lint-fix` - never `vendor/bin/twig-cs-fixer`.
-- **ESLint / Stylelint**: `make lint` / `make lint-fix` - never `npx eslint` or `npx stylelint`.
-- **CSpell**: `make lint` - never `npx cspell`.
-- **PHPUnit**: `make test` / `make test-unit` / `make test-kernel` / `make test-functional` - never `vendor/bin/phpunit`.
-- **Jest**: `make test-js` - never `npx jest`.
 - **Drush**: `make drush <command>` - never `build/vendor/bin/drush` directly.
 
 ### Build and Environment Management
@@ -46,13 +38,6 @@
 
 **Testing:**
 - `make test` / `ahoy test` - Run all tests
-- `make test-unit` / `ahoy test-unit` - Run unit tests only
-- `make test-kernel` / `ahoy test-kernel` - Run kernel tests only
-- `make test-functional` / `ahoy test-functional` - Run functional tests only
-- `make test-functional-javascript` / `ahoy test-functional-javascript` - Run FunctionalJavascript tests (requires Selenium)
-- `make test-js` / `ahoy test-js` - Run JavaScript unit tests (Jest)
-- `make selenium-start` / `ahoy selenium-start` - Start Selenium container
-- `make selenium-stop` / `ahoy selenium-stop` - Stop Selenium container
 
 ### Drupal Commands
 
@@ -67,7 +52,6 @@
 
 **Key Directories:**
 - `src/` - Extension source code (services, forms, etc.)
-- `tests/src/` - PHPUnit tests (Unit/, Kernel/, Functional/)
 - `config/schema/` - Configuration schema definitions
 - `build/` - Assembled Drupal codebase (symlinked extension)
 - `.devtools/` - Build and deployment scripts used by CI
@@ -102,11 +86,6 @@
 
 ## Code Quality Tools
 
-- **CSpell**: Spell checking across the codebase (config at `.cspell.json`)
-- **PHPCS**: Drupal and DrupalPractice standards
-- **PHPStan**: Static analysis with Drupal extensions
-- **Rector**: Automated refactoring and deprecation fixes
-- **Twig CS Fixer**: Twig template formatting
 
 ## CI/CD Support
 
