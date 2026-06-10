@@ -32,13 +32,6 @@
 
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
 
@@ -53,7 +46,6 @@
 
 **Key Directories:**
 - `src/` - Extension source code (services, forms, etc.)
-- `tests/src/` - PHPUnit tests (Unit/, Kernel/, Functional/)
 - `config/schema/` - Configuration schema definitions
 - `build/` - Assembled Drupal codebase (symlinked extension)
 - `.devtools/` - Build and deployment scripts used by CI
@@ -88,11 +80,6 @@
 
 ## Code Quality Tools
 
-- **CSpell**: Spell checking across the codebase (config at `.cspell.json`)
-- **PHPCS**: Drupal and DrupalPractice standards
-- **PHPStan**: Static analysis with Drupal extensions
-- **Rector**: Automated refactoring and deprecation fixes
-- **Twig CS Fixer**: Twig template formatting
 
 ## CI/CD Support
 
