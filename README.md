<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=Drupal+extension+scaffold&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="Drupal extension scaffold"></a>
</p>

<h1 align="center">Drupal extension scaffold</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/AlexSkrypnyk/drupal_extension_scaffold.svg)](https://github.com/AlexSkrypnyk/drupal_extension_scaffold/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/AlexSkrypnyk/drupal_extension_scaffold.svg)](https://github.com/AlexSkrypnyk/drupal_extension_scaffold/pulls)
[![Build, test and deploy](https://github.com/AlexSkrypnyk/drupal_extension_scaffold/actions/workflows/test.yml/badge.svg)](https://github.com/AlexSkrypnyk/drupal_extension_scaffold/actions/workflows/test.yml)
[![CircleCI](https://circleci.com/gh/AlexSkrypnyk/drupal_extension_scaffold.svg?style=shield)](https://circleci.com/gh/AlexSkrypnyk/drupal_extension_scaffold)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/drupal_extension_scaffold)
![LICENSE](https://img.shields.io/github/license/AlexSkrypnyk/drupal_extension_scaffold)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

![Drupal 10](https://img.shields.io/badge/Drupal-10-blue.svg)

</div>

---

<p align="center">Drupal extension template for development and testing in CI of your choice with mirroring to Drupal.org</p>

## Use case

Perform extension development in GitHub with testing in CI, and push code
committed only to the main branches (`1.x`, `2.x` etc.)
to [drupal.org](https://drupal.org).

## Features

- Turnkey CI configuration with artifacts and test results support.
  - PHP version matrix for [8.2](https://www.php.net/supported-versions.php)
    and [8.1](https://www.php.net/supported-versions.php).
  - Drupal version matrix: `stable`, `next` and `last EOL` version.
  - CI providers:
      - [GitHub Actions](.github/workflows/test.yml)
      - [CircleCI](.circleci/config.yml)
- Tools:
  - Develop locally using PHP running on your host using
    identical [`.devtools`](.devtools) scripts as in CI.
    - Uses [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project)
  to create drupal site structure. Providing a custom fork of `drupal-project` is also supported.
    - Additional development dependenices provided in [`composer.dev.json`](composer.dev.json). These are merged during the codebase build.
    - The extension can installed as a module or a theme: modify `type` property set in the `info.yml` file.
    - Additional dependencies can be added for integration testing
    between extensions: add dependency into [`suggest`](composer.json#L25) section
    of `composer.json`.
    ![Build process](https://user-images.githubusercontent.com/378794/253732550-20bd3877-cabb-4a5a-b9a6-ffb5fe6c8e3e.gif)
  - Codings standards checking:
    - PHP code standards checking against `Drupal` and `DrupalPractice` standards.
    - PHP code static analysis
      with PHPStan (including [PHPStan Drupal](https://github.com/mglaman/phpstan-drupal)).
    - PHP deprecated code analysis
      with [Drupal Rector](https://github.com/palantirnet/drupal-rector).
    - Twig code analysis with [TwigCS](https://github.com/friendsoftwig/twigcs).
      ![Lint process](https://user-images.githubusercontent.com/378794/253732548-9403e4cc-db03-4696-b114-32517ab0a571.gif)
  - Drupal's Simpletest testing support - runs tests in the same way as
    [drupal.org](https://drupal.org)'s Drupal CI
    bot (`core/scripts/run-tests.sh`).
    ![Test process](https://user-images.githubusercontent.com/378794/253732542-ea1b2f29-ce89-41bd-b92a-169b119731d3.gif)
  - Renovate configuration to keep your repository dependencies up-to-date.
- Deployment:
  - Mirroring of the repo to [drupal.org](https://drupal.org) (or any other git
    repo) on release.
  - Deploy to a destination branch different from the source branch.
  - Tags mirroring.
- This template is tested in the same way as a project using it.

<img src="https://user-images.githubusercontent.com/378794/253860380-7a702bf6-71f5-4c8c-a271-8dd3b25eaabf.png" alt="Screenshot of CI jobs" width="30%">

## Setup

1. Create your extension's repository on GitHub.
2. Download this extension's code by pressing 'Clone or download' button in GitHub
   UI.
3. Copy the contents of the downloaded archive into your extension's repository.
4. Adjust the codebase:
     1. Replace `your_extension` with the machine name of your extension:
     2. Update `type: theme` in the `info.yml` file if your extension is a theme.
     3. Adjust several lines in `.gitattributes`.
     4. Remove `.github/test-scaffold.yml` file.
     5. Remove this content from `README.md` file.
7. Commit and push to your new GitHub repo.
8. Login to your CI and add your new GitHub repository. Your project build will
   start momentarily.
9. Configure deployment to [drupal.org](https://drupal.org) (see below).
<details>
  <summary>Configure deployment (click to expand)</summary>

The CI supports mirroring of main branches (`1.x`, `10.x-1.x` etc.) to
[drupal.org](https://drupal.org) mirror of the project (to keep both repos in
sync).

The deployment job runs when commits are pushed to main branches
(`1.x`, `2.x`, `10.x-1.x` etc.) or when release tags are created.

Example of deployment
repository:
- from GitHub Actions: https://github.com/AlexSkrypnyk/drupal_extension_scaffold_destination_github
- from CircleCI: https://github.com/AlexSkrypnyk/drupal_extension_scaffold_destination_circleci


1. Generate a new SSH key without pass phrase:

```bash
ssh-keygen -m PEM -t rsa -b 4096 -C "your_email@example.com"
```

2. Add public key to your [drupal.org](https://drupal.org) account:
   https://git.drupalcode.org/-/profile/keys
3. Add private key to your CI:

- CircleCI:
  - Go to your project -> **Settings** -> **SSH Permissions**
  - Put your private SSH key into the box. Leave **Hostname** empty.
  - Copy the fingerprint string from the CircleCI User Interface. Then,
    replace the `deploy_ssh_fingerprint` value in the `.circleci/config.yml`
    file with this copied fingerprint string.
  - Push the code to your repository.

4. In CI, use UI to add the following variables:

- `DEPLOY_USER_NAME` - the name of the user who will be committing to a
  remote repository (i.e., your name on drupal.org).
- `DEPLOY_USER_EMAIL` - the email address of the user who will be committing
  to a remote repository (i.e., your email on drupal.org).
- `DEPLOY_REMOTE` - your extensions remote drupal.org repository (
  i.e. `git@git.drupal.org:project/myextension.git`).
- `DEPLOY_PROCEED` - set to `1` once CI is working, and you are ready to
  deploy.

To debug SSH connection used by Git, add `GIT_SSH_COMMAND` variable with value
`ssh -vvv`. This will output verbose information about the SSH connection and
key used.

</details>

---

**Below is a content of the <code>README.md</code> file that will be added to your project.**

**The content above this line should be removed.**

---

<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=Your+Extension&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="Yourproject logo"></a>
</p>

<h1 align="center">Your extension</h1>

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

Run `.devtools/build-codebase.sh` (or `ahoy build-codebase`
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
vendor/bin/twigcs
```

- PHPCS config: [`phpcs.xml`](phpcs.xml)
- PHPStan config: [`phpstan.neon`](phpstan.neon)
- PHPMD config: [`phpmd.xml`](phpmd.xml)
- Rector config: [`rector.php`](rector.php)
- TwigCS config: [`.twig_cs.php`](.twig_cs.php)

### Tests

Run `.devtools/test.sh` (or `ahoy test`
if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to run all test for
your extension.

### Browsing SQLite database

To browse the contents of created SQLite database
(located at `/tmp/site_[EXTENSION_NAME].sqlite`),
use [DB Browser for SQLite](https://sqlitebrowser.org/).
