@@ -12,10 +12,6 @@
 
 Run each tool through its wrapper (the `make` targets below; `ahoy` mirrors each one) - never the binary directly:
 
-- **PHPCS / PHPCBF**: `make lint` / `make lint-fix` - never `vendor/bin/phpcs` or `vendor/bin/phpcbf`.
-- **PHPStan**: `make lint` - never `vendor/bin/phpstan`.
-- **Rector**: `make lint` (dry-run) / `make lint-fix` - never `vendor/bin/rector`.
-- **Twig CS Fixer**: `make lint` / `make lint-fix` - never `vendor/bin/twig-cs-fixer`.
 - **ESLint / Stylelint**: `make lint` / `make lint-fix` - never `npx eslint` or `npx stylelint`.
 - **CSpell**: `make lint` - never `npx cspell`.
 - **PHPUnit**: `make test` / `make test-unit` / `make test-kernel` / `make test-functional` - never `vendor/bin/phpunit`.
@@ -103,10 +99,6 @@
 ## Code Quality Tools
 
 - **CSpell**: Spell checking across the codebase (config at `.cspell.json`)
-- **PHPCS**: Drupal and DrupalPractice standards
-- **PHPStan**: Static analysis with Drupal extensions
-- **Rector**: Automated refactoring and deprecation fixes
-- **Twig CS Fixer**: Twig template formatting
 
 ## CI/CD Support
 
