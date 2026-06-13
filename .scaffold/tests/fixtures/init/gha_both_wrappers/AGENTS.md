@@ -10,6 +10,17 @@
 
 **HARD RULE - use the provided command wrappers, never the tool binaries directly.** When `make` or `ahoy` exposes a command for a task, use that command; do not call the underlying binary directly. Each wrapper `chdir`s into `build/` and runs the tool with the config, plugins, and environment that CI uses, so a raw invocation from the repository root silently diverges from CI - it can pass locally while CI fails (or vice versa), or crash outright when a relative path resolves against the wrong directory. If no wrapped command covers what you need, extend the `make` / `ahoy` target rather than making a one-off raw call; if that is not feasible, stop and ask.
 
+Run each tool through its `make` wrapper, never the binary directly:
+
+- **PHPCS / PHPCBF**: `make lint` / `make lint-fix` - never `vendor/bin/phpcs` or `vendor/bin/phpcbf`.
+- **PHPStan**: `make lint` - never `vendor/bin/phpstan`.
+- **Rector**: `make lint` (dry-run) / `make lint-fix` - never `vendor/bin/rector`.
+- **Twig CS Fixer**: `make lint` / `make lint-fix` - never `vendor/bin/twig-cs-fixer`.
+- **ESLint / Stylelint**: `make lint` / `make lint-fix` - never `npx eslint` or `npx stylelint`.
+- **CSpell**: `make lint` - never `npx cspell`.
+- **PHPUnit**: `make test` / `make test-unit` / `make test-kernel` / `make test-functional` - never `vendor/bin/phpunit`.
+- **Jest**: `make test-js` - never `npx jest`.
+- **Drush**: `make drush <command>` - never `build/vendor/bin/drush` directly.
 
 Run each tool through its `ahoy` wrapper, never the binary directly:
 
