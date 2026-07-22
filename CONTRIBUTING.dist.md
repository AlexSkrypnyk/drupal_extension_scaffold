# Contributing

This document explains how to set up a local development environment, build
the site, check coding standards, and run the tests for this extension.

## Local development

1. Install PHP with SQLite support and Composer
2. Clone this repository
3. Run `make build` or `ahoy build`

## Building website

`make build` or `ahoy build` assembles the codebase, starts the PHP server
and provisions the Drupal website with this extension enabled. These operations
are executed using scripts within [`.devtools`](.devtools) directory. CI uses
the same scripts to build and test this extension.

The resulting codebase is then placed in the `build` directory. The extension
files are symlinked into the Drupal site structure.

The `build` command is a wrapper for more granular commands:
```bash
make assemble     # Assemble the codebase
make start        # Start the PHP server
make provision    # Provision the Drupal website

ahoy assemble     # Assemble the codebase
ahoy start        # Start the PHP server
ahoy provision    # Provision the Drupal website
```

The `provision` command is useful for re-installing the Drupal website without
re-assembling the codebase.

### Drupal versions

The Drupal version used for the codebase assembly is determined by the
`DRUPAL_VERSION` variable and defaults to the latest stable version.

You can specify a different version by setting the `DRUPAL_VERSION` environment
variable before running the `make build` or `ahoy build` command:

```bash
DRUPAL_VERSION=11 make build        # Drupal 11
DRUPAL_VERSION=11@alpha make build  # Drupal 11 alpha
DRUPAL_VERSION=10@beta make build   # Drupal 10 beta
DRUPAL_VERSION=11.1 make build      # Drupal 11.1
```

The `minimum-stability` setting in the `composer.json` file is
automatically adjusted to match the specified Drupal version's stability.

### Patching dependencies

To apply patches to the dependencies, add a patch to the `patches` section of
`composer.json`. Local patches are sourced from the `patches` directory.

### Providing `GITHUB_TOKEN`

To overcome GitHub API rate limits, you may provide a `GITHUB_TOKEN` environment
variable with a personal access token.

### Provisioning the website

The `provision` command installs the Drupal website from the `standard`
profile with the extension (and any `suggest`'ed extensions) enabled. The
profile can be changed by setting the `DRUPAL_PROFILE` environment variable.

The website will be available at http://localhost:8000 by default. The
hostname can be changed by setting the `WEBSERVER_HOST` environment variable.

The `WEBSERVER_PORT` is resolved with the following precedence:

1. **`WEBSERVER_PORT` exported in the shell** - used as-is. Useful for one-off
   runs: `WEBSERVER_PORT=9000 make build`.
2. **`WEBSERVER_PORT` line in the project-root `.env` file** - used as-is.
   The `start` script does not modify `.env` when this entry is already
   present, so the same port is reused across `start`, `stop`, `provision`,
   `drush` and `login` commands.
3. **Neither is set** - the `start` script discovers the first free port in
   the range `8000-8099` and writes it to `.env` as `WEBSERVER_PORT=NNNN`.
   Subsequent commands read this value from `.env`.

To force re-discovery, delete `.env` (or just the `WEBSERVER_PORT` line in
it) and re-run `make start` / `ahoy start`.

An SQLite database is created in `/tmp/site_[EXTENSION_NAME].sqlite` file.
You can browse the contents of the created SQLite database using
[DB Browser for SQLite](https://sqlitebrowser.org/).

A one-time login link will be printed to the console.

### Step-debugging with XDebug

PHP step-debugging is supported via [XDebug](https://xdebug.org/docs/install). Install the XDebug PHP extension on your host (`php -v` should mention `with Xdebug`), then toggle it on the development server:

```bash
make debug      # restart with XDebug enabled (aliases: debug-on, xdebug, xdebug-on)
ahoy debug      # same, with ahoy

make start      # restart without XDebug (aliases: debug-off, xdebug-off)
ahoy start      # same, with ahoy
```

The `debug` command probes the running PHP server's command line for `xdebug.mode=debug` and skips the restart if XDebug is already enabled. Code coverage stays on [pcov](https://github.com/krakjoe/pcov) because `xdebug.mode=debug` does not include `coverage`.

To start and stop debug sessions from the browser, install the Xdebug Helper extension: [Chrome](https://chromewebstore.google.com/detail/xdebug-helper-by-jetbrain/aoelhdemabeimdhedkidlnbkfhnhgnhm) / [Firefox](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-by-jetbrains/).

## Coding standards

The `make lint` or `ahoy lint` command checks the codebase using multiple
tools:
<!-- #;< DEV_PHPCS -->
- PHP code standards checking against `Drupal` and `DrupalPractice` standards.
<!-- #;> DEV_PHPCS -->
<!-- #;< DEV_PHPSTAN -->
- PHP code static analysis with PHPStan.
<!-- #;> DEV_PHPSTAN -->
<!-- #;< DEV_RECTOR -->
- PHP deprecated code analysis and auto-fixing with Drupal Rector.
<!-- #;> DEV_RECTOR -->
<!-- #;< DEV_TWIGCS -->
- Twig code analysis with Twig CS Fixer.
<!-- #;> DEV_TWIGCS -->
<!-- #;< DEV_ESLINT -->
- JavaScript code analysis with ESLint.
<!-- #;> DEV_ESLINT -->
<!-- #;< DEV_STYLELINT -->
- CSS code analysis with Stylelint.
<!-- #;> DEV_STYLELINT -->

The configuration files for these tools are located in the root of the codebase.

### Fixing coding standards issues

To fix coding standards issues automatically, run the `make lint-fix` or
`ahoy lint-fix`. This runs the same tools as `lint` command but with the
`--fix` option (for the tools that support it).

## Testing

The `make test` or `ahoy test` command runs the tests for this extension.
<!-- #;< DEV_PHPUNIT -->

The tests are located in the `tests/src` directory. The `phpunit.xml` file
configures PHPUnit to run the tests. It uses Drupal core's bootstrap file
`core/tests/bootstrap.php` to bootstrap the Drupal environment before running
the tests.

The `test` command is a wrapper for multiple test commands:
```bash
make test-unit                    # Run Unit tests
make test-kernel                  # Run Kernel tests
make test-functional              # Run Functional tests
#;< DEV_FUNCTIONAL_JAVASCRIPT
make test-functional-javascript   # Run FunctionalJavascript tests
#;> DEV_FUNCTIONAL_JAVASCRIPT

ahoy test-unit                    # Run Unit tests
ahoy test-kernel                  # Run Kernel tests
ahoy test-functional              # Run Functional tests
#;< DEV_FUNCTIONAL_JAVASCRIPT
ahoy test-functional-javascript   # Run FunctionalJavascript tests
#;> DEV_FUNCTIONAL_JAVASCRIPT
```

<!-- #;> DEV_PHPUNIT -->
<!-- #;< DEV_FUNCTIONAL_JAVASCRIPT -->
### Running FunctionalJavascript tests

FunctionalJavascript tests need a real browser driven via WebDriver. By
default they use the Google Chrome already installed on your machine - a
matching `chromedriver` is downloaded automatically on first run, so no
Docker is required:

```bash
ahoy start
ahoy provision
ahoy test-functional-javascript
ahoy browser-stop
```

To run the browser in a Docker Selenium container instead, set
`WEBDRIVER_BACKEND=selenium`. The container cannot reach the host's
`localhost`, so start the webserver on all interfaces:

```bash
WEBSERVER_HOST=0.0.0.0 ahoy start
ahoy provision
WEBDRIVER_BACKEND=selenium ahoy test-functional-javascript
ahoy browser-stop
```

<!-- #;> DEV_FUNCTIONAL_JAVASCRIPT -->
<!-- #;< DEV_PHPUNIT -->
### Running specific tests

You can run specific tests by passing a path to the test file or PHPUnit CLI
option (`--filter`, `--group`, etc.) to the `make test` or `ahoy test` command:

```bash
make test-unit tests/src/Unit/MyUnitTest.php
make test-unit -- --group=wip

ahoy test-unit tests/src/Unit/MyUnitTest.php
ahoy test-unit -- --group=wip
```

You may also run tests using the `phpunit` command directly:

```bash
cd build
php -d pcov.directory=.. vendor/bin/phpunit tests/src/Unit/MyUnitTest.php
php -d pcov.directory=.. vendor/bin/phpunit --group=wip
```
<!-- #;> DEV_PHPUNIT -->
