@@ -19,7 +19,6 @@
 - **ESLint / Stylelint**: `make lint` / `make lint-fix` - never `npx eslint` or `npx stylelint`.
 - **CSpell**: `make lint` - never `npx cspell`.
 - **PHPUnit**: `make test` / `make test-unit` / `make test-kernel` / `make test-functional` - never `vendor/bin/phpunit`.
-- **Jest**: `make test-js` - never `npx jest`.
 - **Drush**: `make drush <command>` - never `build/vendor/bin/drush` directly.
 
 ### Build and Environment Management
@@ -50,7 +49,6 @@
 - `make test-kernel` / `ahoy test-kernel` - Run kernel tests only
 - `make test-functional` / `ahoy test-functional` - Run functional tests only
 - `make test-functional-javascript` / `ahoy test-functional-javascript` - Run FunctionalJavascript tests (requires Selenium)
-- `make test-js` / `ahoy test-js` - Run JavaScript unit tests (Jest)
 - `make selenium-start` / `ahoy selenium-start` - Start Selenium container
 - `make selenium-stop` / `ahoy selenium-stop` - Stop Selenium container
 
