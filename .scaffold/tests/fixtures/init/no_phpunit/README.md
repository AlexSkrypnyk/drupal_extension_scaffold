@@ -11,7 +11,6 @@
 [![GitHub Pull Requests](https://img.shields.io/github/issues-pr/force_crystal/force_crystal.svg)](https://github.com/force_crystal/force_crystal/pulls)
 [![Build, test and deploy](https://github.com/force_crystal/force_crystal/actions/workflows/test.yml/badge.svg)](https://github.com/force_crystal/force_crystal/actions/workflows/test.yml)
 [![CircleCI](https://circleci.com/gh/force_crystal/force_crystal.svg?style=shield)](https://circleci.com/gh/force_crystal/force_crystal)
-[![codecov](https://codecov.io/gh/force_crystal/force_crystal/graph/badge.svg)](https://codecov.io/gh/force_crystal/force_crystal)
 ![GitHub release (latest by date)](https://img.shields.io/github/v/release/force_crystal/force_crystal)
 ![LICENSE](https://img.shields.io/github/license/force_crystal/force_crystal)
 ![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)
@@ -159,57 +158,6 @@
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
-FunctionalJavascript tests require a browser controlled via WebDriver.
-
-```bash
-ahoy selenium-start
-WEBSERVER_HOST=__VERSION__.0 ahoy start
-ahoy provision
-ahoy test-functional-javascript
-ahoy selenium-stop
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
 
 ---
 _This repository was created using the [Drupal Extension Scaffold](https://github.com/AlexSkrypnyk/drupal_extension_scaffold) project template_
