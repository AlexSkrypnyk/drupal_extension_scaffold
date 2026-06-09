@@ -11,10 +11,8 @@
 [![GitHub Pull Requests](https://img.shields.io/github/issues-pr/force_crystal/force_crystal.svg)](https://github.com/force_crystal/force_crystal/pulls)
 [![Build, test and deploy](https://github.com/force_crystal/force_crystal/actions/workflows/test.yml/badge.svg)](https://github.com/force_crystal/force_crystal/actions/workflows/test.yml)
 [![CircleCI](https://circleci.com/gh/force_crystal/force_crystal.svg?style=shield)](https://circleci.com/gh/force_crystal/force_crystal)
-[![codecov](https://codecov.io/gh/force_crystal/force_crystal/graph/badge.svg)](https://codecov.io/gh/force_crystal/force_crystal)
 ![GitHub release (latest by date)](https://img.shields.io/github/v/release/force_crystal/force_crystal)
 ![LICENSE](https://img.shields.io/github/license/force_crystal/force_crystal)
-![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)
 
 ![PHP 8.2](https://img.shields.io/badge/PHP-8.2-777BB4.svg)
 ![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4.svg)
@@ -141,12 +139,6 @@
 
 The `make lint` or `ahoy lint` command checks the codebase using multiple
 tools:
-- PHP code standards checking against `Drupal` and `DrupalPractice` standards.
-- PHP code static analysis with PHPStan.
-- PHP deprecated code analysis and auto-fixing with Drupal Rector.
-- Twig code analysis with Twig CS Fixer.
-- JavaScript code analysis with ESLint.
-- CSS code analysis with Stylelint.
 
 The configuration files for these tools are located in the root of the codebase.
 
@@ -178,17 +170,6 @@
 ahoy test-functional-javascript   # Run FunctionalJavascript tests
 ```
 
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
 
 ### Running specific tests
 
