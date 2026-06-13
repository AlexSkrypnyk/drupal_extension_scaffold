@@ -20,7 +20,6 @@
 - **ESLint / Stylelint**: `ahoy lint` / `ahoy lint-fix` - never `npx eslint` or `npx stylelint`.
 - **CSpell**: `ahoy lint` - never `npx cspell`.
 - **PHPUnit**: `ahoy test` / `ahoy test-unit` / `ahoy test-kernel` / `ahoy test-functional` - never `vendor/bin/phpunit`.
-- **Jest**: `ahoy test-js` - never `npx jest`.
 - **Drush**: `ahoy drush <command>` - never `build/vendor/bin/drush` directly.
 
 ### Build and Environment Management
@@ -51,7 +50,6 @@
 - `make test-kernel` / `ahoy test-kernel` - Run kernel tests only
 - `make test-functional` / `ahoy test-functional` - Run functional tests only
 - `make test-functional-javascript` / `ahoy test-functional-javascript` - Run FunctionalJavascript tests (requires Selenium)
-- `make test-js` / `ahoy test-js` - Run JavaScript unit tests (Jest)
 - `make selenium-start` / `ahoy selenium-start` - Start Selenium container
 - `make selenium-stop` / `ahoy selenium-stop` - Stop Selenium container
 
