<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=Your+Extension&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="Yourproject logo"></a>
</p>

<h1 align="center">Your Extension</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/YourNamespace/your_extension.svg)](https://github.com/YourNamespace/your_extension/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/YourNamespace/your_extension.svg)](https://github.com/YourNamespace/your_extension/pulls)
[![Build, test and deploy](https://github.com/YourNamespace/your_extension/actions/workflows/test.yml/badge.svg)](https://github.com/YourNamespace/your_extension/actions/workflows/test.yml)
[![CircleCI](https://circleci.com/gh/YourNamespace/your_extension.svg?style=shield)](https://circleci.com/gh/YourNamespace/your_extension)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/YourNamespace/your_extension)
![LICENSE](https://img.shields.io/github/license/YourNamespace/your_extension)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

![Drupal 10](https://img.shields.io/badge/Drupal-10-blue.svg)

</div>

---

<p align="center"> Few lines describing your project.
    <br>
</p>

## Features

- Your first feature as a list item
- Your second feature as a list item
- Your third feature as a list item


## Local development

Provided that you have PHP installed locally, you can develop an extension using
the provided scripts.

### Build

Run `.devtools/assemble.sh` (or `ahoy assemble`
if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to start inbuilt PHP
server locally and run the same commands as in CI, plus installing a site and
your extension automatically.

### Code linting

Run tools individually (or `ahoy lint` to run all tools
if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to lint your code
according to
the [Drupal coding standards](https://www.drupal.org/docs/develop/standards).

```
cd build

vendor/bin/phpcs
vendor/bin/phpstan
vendor/bin/rector --clear-cache --dry-run
vendor/bin/phpmd . text phpmd.xml
vendor/bin/twig-cs-fixer
```

- PHPCS config: [`phpcs.xml`](phpcs.xml)
- PHPStan config: [`phpstan.neon`](phpstan.neon)
- PHPMD config: [`phpmd.xml`](phpmd.xml)
- Rector config: [`rector.php`](rector.php)
- Twig CS Fixer config: [`.twig-cs-fixer.php`](.twig-cs-fixer.php)

### Tests

Run tests individually with `cd build && ./vendor/bin/phpunit` (or `ahoy test`
if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to run all test for
your extension.

### Browsing SQLite database

To browse the contents of created SQLite database
(located at `/tmp/site_[EXTENSION_NAME].sqlite`),
use [DB Browser for SQLite](https://sqlitebrowser.org/).
