@@ -111,12 +111,6 @@
 
 The `make lint` or `ahoy lint` command checks the codebase using multiple
 tools:
-- PHP code standards checking against `Drupal` and `DrupalPractice` standards.
-- PHP code static analysis with PHPStan.
-- PHP deprecated code analysis and auto-fixing with Drupal Rector.
-- Twig code analysis with Twig CS Fixer.
-- JavaScript code analysis with ESLint.
-- CSS code analysis with Stylelint.
 
 The configuration files for these tools are located in the root of the codebase.
 
@@ -129,67 +123,3 @@
 ## Testing
 
 The `make test` or `ahoy test` command runs the tests for this extension.
-
-The tests are located in the `tests/src` directory. The `phpunit.xml` file
-configures PHPUnit to run the tests. It uses Drupal core's bootstrap file
-`core/tests/bootstrap.php` to bootstrap the Drupal environment before running
-the tests.
-
-The `test` command is a wrapper for multiple test commands:
-```bash
-make test-unit                    # Run Unit tests
-make test-kernel                  # Run Kernel tests
-make test-functional              # Run Functional tests
-make test-functional-javascript   # Run FunctionalJavascript tests
-
-ahoy test-unit                    # Run Unit tests
-ahoy test-kernel                  # Run Kernel tests
-ahoy test-functional              # Run Functional tests
-ahoy test-functional-javascript   # Run FunctionalJavascript tests
-```
-
-### Running FunctionalJavascript tests
-
-FunctionalJavascript tests need a real browser driven via WebDriver. By
-default they use the Google Chrome already installed on your machine - a
-matching `chromedriver` is downloaded automatically on first run, so no
-Docker is required:
-
-```bash
-ahoy start
-ahoy provision
-ahoy test-functional-javascript
-ahoy browser-stop
-```
-
-To run the browser in a Docker Selenium container instead, set
-`WEBDRIVER_BACKEND=selenium`. The container cannot reach the host's
-`localhost`, so start the webserver on all interfaces:
-
-```bash
-WEBSERVER_HOST=__VERSION__.0 ahoy start
-ahoy provision
-WEBDRIVER_BACKEND=selenium ahoy test-functional-javascript
-ahoy browser-stop
-```
-
-### Running specific tests
-
-You can run specific tests by passing a path to the test file or PHPUnit CLI
-option (`--filter`, `--group`, etc.) to the `make test` or `ahoy test` command:
-
-```bash
-make test-unit tests/src/Unit/MyUnitTest.php
-make test-unit -- --group=wip
-
-ahoy test-unit tests/src/Unit/MyUnitTest.php
-ahoy test-unit -- --group=wip
-```
-
-You may also run tests using the `phpunit` command directly:
-
-```bash
-cd build
-php -d pcov.directory=.. vendor/bin/phpunit tests/src/Unit/MyUnitTest.php
-php -d pcov.directory=.. vendor/bin/phpunit --group=wip
-```
