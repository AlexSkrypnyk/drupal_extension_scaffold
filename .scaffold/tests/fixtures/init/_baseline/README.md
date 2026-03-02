<div align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="logo.png" alt="Force Crystal logo"></a>
</div>

<h1 align="center">Few lines describing your project.</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/force_crystal/force_crystal.svg)](https://github.com/force_crystal/force_crystal/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/force_crystal/force_crystal.svg)](https://github.com/force_crystal/force_crystal/pulls)
[![Build, test and deploy](https://github.com/force_crystal/force_crystal/actions/workflows/test.yml/badge.svg)](https://github.com/force_crystal/force_crystal/actions/workflows/test.yml)
[![CircleCI](https://circleci.com/gh/force_crystal/force_crystal.svg?style=shield)](https://circleci.com/gh/force_crystal/force_crystal)
[![codecov](https://codecov.io/gh/force_crystal/force_crystal/graph/badge.svg)](https://codecov.io/gh/force_crystal/force_crystal)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/force_crystal/force_crystal)
![LICENSE](https://img.shields.io/github/license/force_crystal/force_crystal)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

![PHP 8.2](https://img.shields.io/badge/PHP-8.2-777BB4.svg)
![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4.svg)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4.svg)
![Drupal 10](https://img.shields.io/badge/Drupal-10-009CDE.svg)
![Drupal 11](https://img.shields.io/badge/Drupal-11-006AA9.svg)

</div>

---

## Features

- Your first feature as a list item
- Your second feature as a list item
- Your third feature as a list item

## Local development

1. Install PHP with SQLite support and Composer
3. Clone this repository
4. Run `make build` or `ahoy build`

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

### Using Drupal project fork

If you want to use a custom fork of `drupal-composer/drupal-project`, set the
`DRUPAL_PROJECT_REPO` environment variable before running the `make build` or
`ahoy build` command:

```bash
DRUPAL_PROJECT_REPO=https://github.com/me/drupal-project-fork.git make build
```

### Patching dependencies

To apply patches to the dependencies, add a patch to the `patches` section of
`composer.json`. Local patches are be sourced from the `patches` directory.

### Providing `GITHUB_TOKEN`

To overcome GitHub API rate limits, you may provide a `GITHUB_TOKEN` environment
variable with a personal access token.

### Provisioning the website

The `provision` command installs the Drupal website from the `standard`
profile with the extension (and any `suggest`'ed extensions) enabled. The
profile can be changed by setting the `DRUPAL_PROFILE` environment variable.

The website will be available at http://localhost:8000. The hostname and port
can be changed by setting the `WEBSERVER_HOST` and `WEBSERVER_PORT` environment
variables.

An SQLite database is created in `/tmp/site_force_crystal.sqlite` file.
You can browse the contents of the created SQLite database using
[DB Browser for SQLite](https://sqlitebrowser.org/).

A one-time login link will be printed to the console.

## Coding standards

The `make lint` or `ahoy lint` command checks the codebase using multiple
tools:
- PHP code standards checking against `Drupal` and `DrupalPractice` standards.
- PHP code static analysis with PHPStan.
- PHP deprecated code analysis and auto-fixing with Drupal Rector.
- Twig code analysis with Twig CS Fixer.

The configuration files for these tools are located in the root of the codebase.

### Fixing coding standards issues

To fix coding standards issues automatically, run the `make lint-fix` or
`ahoy lint-fix`. This runs the same tools as `lint` command but with the
`--fix` option (for the tools that support it).

## Testing

The `make test` or `ahoy test` command runs the PHPUnit tests for this extension.

The tests are located in the `tests/src` directory. The `phpunit.xml` file
configures PHPUnit to run the tests. It uses Drupal core's bootstrap file
`core/tests/bootstrap.php` to bootstrap the Drupal environment before running
the tests.

The `test` command is a wrapper for multiple test commands:
```bash
make test-unit        # Run Unit tests
make test-kernel      # Run Kernel tests
make test-functional  # Run Functional tests

ahoy test-unit        # Run Unit tests
ahoy test-kernel      # Run Kernel tests
ahoy test-functional  # Run Functional tests
```

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

---
_This repository was created using the [Drupal Extension Scaffold](https://github.com/force_crystal/force_crystal) project template_
