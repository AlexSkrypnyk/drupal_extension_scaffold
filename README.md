<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://github.com/AlexSkrypnyk/drupal_extension_scaffold/assets/378794/31658686-7a8a-4203-9c8b-a8bc0b99f002" alt="Drupal extension scaffold"></a>
</p>

<h1 align="center">Template for a contributed Drupal module or theme with CI and mirroring to Drupal.org</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/AlexSkrypnyk/drupal_extension_scaffold.svg)](https://github.com/AlexSkrypnyk/drupal_extension_scaffold/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/AlexSkrypnyk/drupal_extension_scaffold.svg)](https://github.com/AlexSkrypnyk/drupal_extension_scaffold/pulls)
[![Build, test and deploy](https://github.com/AlexSkrypnyk/drupal_extension_scaffold/actions/workflows/test.yml/badge.svg)](https://github.com/AlexSkrypnyk/drupal_extension_scaffold/actions/workflows/test.yml)
[![CircleCI](https://circleci.com/gh/AlexSkrypnyk/drupal_extension_scaffold.svg?style=shield)](https://circleci.com/gh/AlexSkrypnyk/drupal_extension_scaffold)
[![codecov](https://codecov.io/gh/AlexSkrypnyk/drupal_extension_scaffold/graph/badge.svg?token=GSXTND4VOC&cachebust=123)](https://codecov.io/gh/AlexSkrypnyk/drupal_extension_scaffold)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/drupal_extension_scaffold)
![LICENSE](https://img.shields.io/github/license/AlexSkrypnyk/drupal_extension_scaffold)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4.svg)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4.svg)
![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777BB4.svg)
![Drupal 10](https://img.shields.io/badge/Drupal-10-009CDE.svg)
![Drupal 11](https://img.shields.io/badge/Drupal-11-006AA9.svg)
</div>

---

## Use case

Develop a module or theme on GitHub, test it using GitHub Actions or CircleCI,
and push the code to [Drupal.org](https://drupal.org).

## Index

- [Features](#features)
- [Setup overview](#setup-overview)
- [Codebase setup](#codebase-setup)
- [Building website](#building-website)
- [Coding standards](#coding-standards)
- [Testing](#testing)
- [Branch protection](#branch-protection)
- [Deployment](#deployment)
- [Updating your extension](#updating-your-extension)
- [Renovate](#renovate)
- [Projects using this scaffold](#projects-using-this-scaffold)
- [Contributing](#contributing)

## Features

- Turnkey CI configuration:
  - PHP version matrix: `8.3`, `8.4`, `8.5`.
  - Drupal version matrix: `stable` on Drupal 10 and 11, plus `legacy` and `canary` tiers on Drupal 11.
  - CI providers: [GitHub Actions](.github/workflows/test.yml)
    and [CircleCI](.circleci/config.yml)
  - Code coverage with https://github.com/krakjoe/pcov pushed to [codecov.io](https://codecov.io).
  - Compatible with Drupal.org GitLab CI ([DrupalCI](#drupalorg-ci-drupalci)).
- Develop locally using PHP running on your host using
  identical [`.devtools`](.devtools) scripts as in CI:
  - Uses [drupal/recommended-project](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
    to create Drupal site structure.
  - Additional development dependencies provided in [`composer.dev.json`](composer.dev.json).
    These are merged during the codebase assembly.
  - The extension can be installed as a module or a theme: modify `type`
    property set in the `info.yml` file.
  - Additional dependencies can be added for integration testing
    between extensions: add dependencies into `suggest` section
    of `composer.json` and they will be included into the assembled codebase.
  - Patches can be applied to the dependencies: add a patch to the
    `patches` section of `composer.json`. Local patches are sourced from
    the `patches` directory.
  - Command wrappers using `make` and [Ahoy](https://github.com/ahoy-cli/ahoy)
    for common tasks.
- Codings standards checking:
  - PHP code standards checking against `Drupal` and `DrupalPractice` standards.
  - PHP code static analysis
    with PHPStan (
    including [PHPStan Drupal](https://github.com/mglaman/phpstan-drupal)).
  - PHP deprecated code analysis and auto-fixing
    with [Drupal Rector](https://github.com/palantirnet/drupal-rector).
  - Twig code analysis
    with [Twig CS Fixer](https://github.com/VincentLanglet/Twig-CS-Fixer).
  - JavaScript code analysis with [ESLint](https://eslint.org/).
  - CSS code analysis with [Stylelint](https://stylelint.io/).
  - Spell checking with [CSpell](https://cspell.org/).
  - Code formatting with [Prettier](https://prettier.io/).
- PHPUnit testing support
- [Renovate](#renovate) configuration to keep dependencies up-to-date with a single grouped PR.
- [README.md](README.dist.md) template
- Deployment:
  - Mirroring of the repo to Drupal.org (or any other git
    repo) on release.
  - Deploy to a destination branch different from the source branch.
  - Tags mirroring.
- This template is tested in the same way as a project using it. See examples of the deployment destination repositories for [GitHub Actions](https://github.com/AlexSkrypnyk/drupal_extension_scaffold_destination_github) and [CircleCI](https://github.com/AlexSkrypnyk/drupal_extension_scaffold_destination_circleci)

## Setup overview

1. Download this extension's code by pressing 'Clone or download' button in
   GitHub UI.
2. Expand into a new directory.
3. Run the initial [codebase setup](#codebase-setup) script: `php init.php`.
4. If you already have an existing extension code, copy it into the directory
   created in step 2.
5. [Build website](#building-website) with `make build` or `ahoy build`
   to check that everything is set up correctly.
6. [Check coding standards](#coding-standards) with `make lint` or `ahoy lint`.
7. [Run tests](#testing) with `make test` or `ahoy test`.
8. Create your extension's repository on GitHub.
9. Commit and push to your new GitHub repo.
10. If using CircleCI, login and add your new GitHub repository. Your project
    build will start momentarily.
11. [Configure branch protection in GitHub](#branch-protection).
12. [Configure deployment](#deployment) to Drupal.org.

See the sections below for more details.

## Codebase setup

The initial codebase setup script `php init.php` will ask you for some information
and update the codebase to reflect your extension's name and other details.

![Init process](.scaffold/assets/init.svg)

## Building website

`make build` or `ahoy build` assembles the codebase, starts the PHP server
and provisions the Drupal website with your extension enabled. These operations
are executed using scripts within [`.devtools`](.devtools) directory. CI uses
the same scripts to build and test your extension.

The resulting codebase is then placed in the `build` directory. Your extension
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

See [README.md](README.dist.md) for more development commands.

![Build process](.scaffold/assets/build.svg)

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

### CI Drupal version matrix

The CI configuration ([GitHub Actions](.github/workflows/test.yml) and [CircleCI](.circleci/config.yml)) tests the extension against deliberate *role corners* rather than a full cross-product of every PHP and Drupal version. Six jobs cover the lowest and highest supported PHP on each stable Drupal major, the next minor pre-release, and one pinned older minor:

| Job | PHP | Drupal | Role |
|-----|-----|--------|------|
| `test-php-min-d10-stable` | `8.3` | `10` | Drupal 10 on the lowest supported PHP |
| `test-php-max-d10-stable` | `8.4` | `10` | Drupal 10 on the highest supported PHP |
| `test-php-min-d11-stable` | `8.3` | `11` | Drupal 11 on the lowest supported PHP |
| `test-php-max-d11-stable` | `8.5` | `11` | Drupal 11 on the highest supported PHP |
| `test-php-min-d11-legacy` | `8.3` | `11.1.0` | Oldest tested Drupal minor (pinned) |
| `test-php-max-d11-canary` | `8.5` | `11@beta` | Next Drupal minor pre-release |

Each job name encodes the PHP bound (`min`/`max`), the Drupal major (`d10`/`d11`) and the release tier (`stable`/`legacy`/`canary`). The exact PHP version for each job is shown in its "Setup PHP" step.

The two axes behave differently:

- **Drupal versions float, with one exception.** `stable` (`10`, `11`) resolves to the newest stable minor, and `canary` (`11@beta`) resolves to the latest alpha, beta or release candidate, falling back to the current stable when none exists. Both follow Drupal core on their own with no edits. The only Drupal value that does not float is the pinned `legacy` minor (`11.1.0`, which Composer resolves to the newest `11.1.x` patch), pinned on purpose so the job genuinely exercises an older minor.
- **PHP versions are pinned bounds.** Neither provider can float a PHP version - the GitHub Actions setup action and the CircleCI images both take an explicit version - so the `min` and `max` PHP values are fixed. They change rarely: `max` when a newer PHP is added, `min` only when support for an old PHP is dropped.

Because `stable` and `canary` float, the matrix follows Drupal core on its own - a new stable minor or pre-release is picked up on the next CI run with no manual changes. The pinned `legacy` minor and the PHP versions are set-and-forget: they keep exercising the same floor indefinitely, so there is nothing you have to maintain by hand. When you want to move that floor forward as core and PHP advance, re-pull from the scaffold (see [Updating your extension](#updating-your-extension)) - this template tracks the versions Drupal core provides, so updating from it refreshes the `legacy` pin and the PHP versions for you.

### Patching dependencies

To apply patches to the dependencies, add a patch to the `patches` section of
`composer.json`. Local patches are sourced from the `patches` directory.

### Providing `GITHUB_TOKEN`

To overcome GitHub API rate limits, you may provide a `GITHUB_TOKEN` environment
variable with a personal access token.

### Debugging command output

The output of the underlying commands (Composer, npm, Drush) is suppressed by
default and shown only when a command fails. Set `DEBUG=1` to stream the full
output of every command:

```bash
DEBUG=1 make build   # stream all command output
DEBUG=1 ahoy build   # same, with ahoy
```

### Optional dependencies

If your extension requires additional dependencies for integration testing
between extensions, add the dependency into the `suggest` section of
`composer.json`. The dependency is included in the assembled codebase and
enabled in the Drupal website.

### Frontend dependencies

If your extension requires frontend dependencies for testing, add them to the
`package.json` file. The `package-lock.json` file is expected to be committed to
the repository.

The `assemble` command installs (`npm ci`) and builds (`npm run build`) the
frontend dependencies within the `build` directory. You can add and commit a
`.skip_npm_build` file to skip all Node.js processing, which will also
disable JS/CSS linting (ESLint, Stylelint, Prettier).

### Provisioning the website

The `provision` command installs the Drupal website from the `standard`
profile with your extension (and any `suggest`'ed extensions) enabled. The
profile can be changed by setting the `DRUPAL_PROFILE` environment variable.

The website will be available at http://localhost:8000 by default. The
hostname can be changed by setting the `WEBSERVER_HOST` environment variable.

#### Webserver port

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

The `.env` file is loaded automatically by `make` (via `-include .env` and
`export`) and `ahoy` (via the native `env:` field), so all wrapper commands
see the resolved port without further configuration. The file is gitignored.

To force re-discovery, delete `.env` (or just the `WEBSERVER_PORT` line in
it) and re-run `make start` / `ahoy start`.

An SQLite database is created in `/tmp/site_[EXTENSION_NAME].sqlite` file.
You can browse the contents of the created SQLite database using
[DB Browser for SQLite](https://sqlitebrowser.org/).

A one-time login link will be printed to the console.

### Custom lifecycle scripts

The `assemble`, `provision`, `start`, and `stop` scripts each look for project-local shell scripts in the `scripts/` directory and run them during their respective phase:

- `scripts/assemble-*.sh` runs at the tail of `make assemble` / `ahoy assemble`, after dependencies are installed and the extension is symlinked into `build/`.
- `scripts/provision-*.sh` runs at the tail of `make provision` / `ahoy provision`, after the site is installed, the extension is enabled, and caches are pre-warmed.
- `scripts/start-*.sh` runs at the tail of `make start` / `ahoy start`, after the PHP webserver is up and serving.
- `scripts/stop-*.sh` runs during `make stop` / `ahoy stop`, before the webserver is stopped, while it is still reachable.

Matching files are executed in lexicographic order. The current working directory is the project root, and each script inherits the parent process environment. A non-zero exit from any script aborts the parent run.

The directory is `export-ignore`d via `.gitattributes`, so anything under `scripts/` is excluded from distribution archives published to Drupal.org.

Example scripts ship with the scaffold (`scripts/assemble-example.sh`, `scripts/provision-example.sh`, `scripts/start-example.sh`, `scripts/stop-example.sh`). Delete them, replace them, or use them as a starting point.

#### Public HTTPS tunnel (Cloudflare)

Remote and cloud development environments (Codespaces, DevPod, a remote Docker host, an SSH dev box) cannot reach the `localhost`-bound PHP dev server directly. The scaffold ships opt-in hook scripts that expose it through a [Cloudflare quick tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/do-more-with-tunnels/trycloudflare/) - a public `*.trycloudflare.com` HTTPS URL with no account, DNS, or config:

```bash
export CLOUDFLARE_TUNNEL=1
make build
```

> [!WARNING]
> A quick tunnel publishes your local site to a public URL with no authentication in front of it - anyone with the URL can reach it while the tunnel is up. A local Drupal install typically ships with a known admin account and no firewall, so treat the exposed site as fully public: use disposable test data only, never real or sensitive content, and stop the tunnel with `make stop` when you are done.

With `CLOUDFLARE_TUNNEL` set and the [`cloudflared`](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/) binary on `PATH`, `scripts/start-cloudflared.sh` starts (or reuses a healthy) tunnel and writes its URL to `.env` as `TUNNEL_URL`. The `start`, `provision`, and `info` output, and `make`/`ahoy drush` and `login`, then use that URL. `scripts/provision-cloudflared.sh` configures Drupal's reverse-proxy and trusted-host settings so the tunnel serves correctly, and `scripts/stop-cloudflared.sh` tears the tunnel down on `make stop`. Without the env var, behaviour is unchanged; with it set but `cloudflared` absent, the hook skips with a note.

Any tool that writes a `TUNNEL_URL` to `.env` (ngrok, tailscale funnel, etc.) is picked up the same way - the core scripts are tunnel-agnostic.

#### Scannable QR codes

QR rendering is opt-in. Set `QRCODE=1` (in your shell environment or `.env`) with [`qrencode`](https://fukuchi.org/works/qrencode/) on `PATH`, and `make login` / `ahoy login` render the one-time login link as a terminal QR code below the link. Scan it to open the site - already logged in - on a phone or another device, which is most useful when the site is exposed through a public tunnel. Left unset (the default) or without `qrencode` installed, the output is unchanged.

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
- Spell checking with CSpell.
- PHP code standards checking against `Drupal` and `DrupalPractice` standards.
- PHP code static analysis with PHPStan.
- PHP deprecated code analysis and auto-fixing with Drupal Rector.
- Twig code analysis with Twig CS Fixer.
- JavaScript code analysis with ESLint.
- CSS code analysis with Stylelint.

The configuration files for these tools are located in the root of the codebase.

![Lint process](.scaffold/assets/lint.svg)

### Fixing coding standards issues

To fix coding standards issues automatically, run the `make lint-fix` or
`ahoy lint-fix`. This runs the same tools as `lint` command but with the
`--fix` option (for the tools that support it).

If automatic fixes are not accurate, you can adjust the configuration files
to either suppress the issue or adjust the fix.

## Testing

The `make test` or `ahoy test` command runs the PHPUnit tests for your extension.

The tests are located in the `tests/src` directory. The `phpunit.xml` file
configures PHPUnit to run the tests. It uses Drupal core's bootstrap file
`web/core/tests/bootstrap.php` to bootstrap the Drupal environment before running
the tests.

The `test` command is a wrapper for multiple test commands:
```bash
make test-unit                    # Run Unit tests
make test-kernel                  # Run Kernel tests
make test-functional              # Run Functional tests
make test-functional-javascript   # Run FunctionalJavascript tests
make test-javascript              # Run JavaScript unit tests (Jest)

ahoy test-unit                    # Run Unit tests
ahoy test-kernel                  # Run Kernel tests
ahoy test-functional              # Run Functional tests
ahoy test-functional-javascript   # Run FunctionalJavascript tests
ahoy test-javascript              # Run JavaScript unit tests (Jest)
```

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

![Test process](.scaffold/assets/test.svg)

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
./vendor/bin/phpunit tests/src/Unit/MyUnitTest.php
./vendor/bin/phpunit --group=wip
```

### Deprecated code testing

The tests are configured to check for deprecated code usage and fail if any
is found. You can fix the deprecated code or suppress the test by adding
`.deprecation-ignore.txt` file to the root of the codebase and updating
the `SYMFONY_DEPRECATIONS_HELPER` environment variable in the `phpunit.xml`.
See https://www.drupal.org/node/3285162 for more details.

Note that the CI configuration has jobs that run the unstable `canary` versions
of Drupal which may have different deprecations. These versions have the
`SYMFONY_DEPRECATIONS_HELPER` environment variable set to `disable` to ignore
deprecation errors. You may want to adjust this CI configuration for your
project depending on your deprecated code policy.

## Branch protection

Whether you are using GitHub Actions or CircleCI, you should configure [branch
protection rules](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/managing-a-branch-protection-rule)
in GitHub to ensure that the code tests pass before merging.

Make sure to add all jobs for your default branch:

![GitHub branch protection jobs](.scaffold/assets/github-branch-protection.png)

## Deployment

The CI supports deployment of the code via mirroring of main branches
(`1.x`, `10.x-1.x` etc.) to Drupal.org repository.

The `deploy` job runs when commits are pushed to main branches
(`1.x`, `2.x`, `10.x-1.x` etc.) or when release tags are created. This means
that out-of-the-box, the deployment job will not run for other branches or
pull requests, but you can adjust the CI configuration to suit your needs.

The code pushed to the destination repository is the commit that CI tested,
so a release tag deploys the tagged commit rather than the tip of the branch
it was cut from.

See these examples of the deployment destination repository:
[GitHub Actions](https://github.com/AlexSkrypnyk/drupal_extension_scaffold_destination_github) and
[CircleCI](https://github.com/AlexSkrypnyk/drupal_extension_scaffold_destination_circleci)

CI will use the SSH key to push the code to the destination repository. The
public part of the SSH key should be added to your [Drupal.org account](https://git.drupalcode.org/-/user_settings/ssh_keys).
The private part of the SSH key should be added to the CI provider.

It is a good practice to use a dedicated SSH key for every project.

### Setting up SSH key for deployment

1. Generate a new SSH key without the pass phrase:

```bash
ssh-keygen -m PEM -t rsa -b 4096 -C "your_email+project_name@example.com"
```

2. Add **public** key to your [Drupal.org account](https://git.drupalcode.org/-/user_settings/ssh_keys)
3. Add **private** key to your CI:
  - GitHub Actions:
    - Go to your project -> **Settings** -> **Secrets**
    - Add a new secret with the `DEPLOY_SSH_KEY` name and the private key as
      the value.

  - CircleCI:
    - Go to your project -> **Settings** -> **SSH Permissions**
    - Put your private SSH key into the box. Leave **Hostname** empty.
    - Copy the fingerprint string from the CircleCI User Interface. Then,
      replace the `deploy_ssh_key_fingerprint` value in the `.circleci/config.yml`
      file with this copied fingerprint string.
    - Push the code to your repository.

4. In CI, use UI to add the following variables as secrets:

- `DEPLOY_REMOTE` - your extension's Drupal.org repository (
  i.e. `git@git.drupal.org:project/myextension.git`).
- `DEPLOY_USER_NAME` - the name of the user who commits to the
  remote repository (i.e., your name on Drupal.org).
- `DEPLOY_USER_EMAIL` - the email address of the user who commits
  to the remote repository (i.e., your email on Drupal.org).
- `DEPLOY_PROCEED` - set to `1` once CI is working, and you are ready to
  deploy. Without this variable, the deployment job will run but will not
  push the code. This is useful for testing the deployment job.

5. Optionally, set `DEPLOY_BRANCH` to the branch to push to in the destination repository. It is not a secret: add it as a repository variable in GitHub Actions (**Settings** -> **Secrets and variables** -> **Actions** -> **Variables**) and as a project environment variable in CircleCI. Without it, the code is pushed to the branch that triggered the build, and a tagged release is pushed to the default branch - the repository default branch in GitHub Actions, and the `default_branch` alias in `.circleci/config.yml` in CircleCI.

### Drupal.org CI (DrupalCI)

Once your extension is mirrored to Drupal.org, its GitLab CI ("DrupalCI") runs
automatically. The scaffold's configuration is compatible with DrupalCI out of
the box - the PHPStan error suppressions resolve correctly even though DrupalCI
runs the analysis from within the module directory.

PHPUnit needs one override: disable code coverage on DrupalCI. DrupalCI symlinks
your project back into the built site's `web/modules/custom/<name>/` directory,
so PHPUnit's coverage scan follows that recursive symlink into
`web/core/node_modules` and exhausts the available file descriptors. Coverage is
already collected by GitHub Actions and CircleCI, so turning it off on DrupalCI
is safe.

Add the [standard DrupalCI includes](https://git.drupalcode.org/project/gitlab_templates)
to a `.gitlab-ci.yml` in your project root and set the override:

```yaml
variables:
  _PHPUNIT_EXTRA: '--no-coverage'
```

## Updating your extension

When this template is updated, you can merge the changes into your extension
codebase.

If you use Claude Code, the bundled
[`update-consumer-drupal-extension-scaffold`](.scaffold/skills/update-consumer-drupal-extension-scaffold/SKILL.md)
skill automates this process: in your initialised project, ask Claude to
"update scaffold" and it will fetch the skill, download the latest scaffold,
re-run `init.php` with your original answers, restore project-specific files
from git, and reconcile differences.

For a manual update, follow these steps:

1. Download the latest version of this codebase by pressing 'Clone or download'
   button in GitHub UI.
2. Expand into a new directory.
3. Run the initial [codebase setup](#codebase-setup) script: `php init.php` and
   repeat the answers you provided during the initial setup.
4. Create a new branch in your extension's repository.
5. Copy all files into your extension's directory and override the existing files.
6. Resolve any conflicts between the new files and your extension's files. Refer
   to the release notes for any breaking changes and accept/reject them as needed.
7. [Build website](#building-website) with `make build` or `ahoy build`
   to check that everything is set up correctly.
8. [Check coding standards](#coding-standards) with `make lint` or `ahoy lint`.
9. [Run tests](#testing) with `make test` or `ahoy test`.
10. Commit and push to your new GitHub repo.
11. Check that all the CI jobs are finishing successfully.
12. Merge the new branch into your main branch.
13. Check that the deployment job is working correctly.

## Renovate

This template includes a [Renovate](https://docs.renovatebot.com/)
configuration ([`renovate.json`](renovate.json)) to automatically keep
dependencies up-to-date.

### What is updated

| Source                                   | Dependencies                  | Examples                                                                          |
|------------------------------------------|-------------------------------|-----------------------------------------------------------------------------------|
| [`composer.dev.json`](composer.dev.json) | Development Composer packages | `drupal/coder`, `mglaman/phpstan-drupal`, `vincentlanglet/twig-cs-fixer`          |
| [`package.json`](package.json)           | npm packages                  | `eslint`, `stylelint`, `prettier`                                                 |
| `.github/workflows/*.yml`                | GitHub Actions                | `actions/checkout`, `actions/upload-artifact`, `codecov/codecov-action`           |

### What is NOT updated

- **`composer.json`** — contains the extension's production dependencies
  (`require` and `require-dev`) which should be updated manually to ensure
  compatibility with Drupal.org packaging.
- **Major versions** — major version bumps are disabled for all dependencies
  and should be updated manually to avoid breaking changes.

### How it works

- **Single PR**: all minor and patch dependency updates are grouped into a
  single pull request titled "Update all dependencies".
- **Automerge**: PRs are automatically merged when all CI checks pass.
- **Digest pinning**: GitHub Actions are pinned to SHA digests
  for reproducibility and security.
- **Range strategy**: version ranges in `package.json` and `composer.dev.json`
  are bumped to the latest version (e.g., `^1.2` becomes `^1.3`).
- **Dependency Dashboard**: an issue is created in the repository to track
  pending updates, approval requests, and detected problems.

See the [Renovate documentation](https://docs.renovatebot.com/configuration-options/)
for all available options.

## Projects using this scaffold

- [Testmode](https://github.com/AlexSkrypnyk/testmode) - Drupal module to alter existing site content and other configurations when running tests.
- [Generated Content](https://github.com/AlexSkrypnyk/generated_content) - Drupal module to programmatically generate content.
- [Integration Report](https://github.com/AlexSkrypnyk/integration_report) - Drupal module to report on availability status of 3rd party endpoints.
- [Drupal Helpers](https://github.com/alexSkrypnyk/drupal_helpers) - Helper utilities for Drupal.
- [Deploy_Steps](https://github.com/alexSkrypnyk/deploy_steps) - Runs repeatable run-on-every-deploy logic as discoverable plugins.

---

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for how to
build and test the scaffold, run its self-tests, and regenerate the snapshot
fixtures.
