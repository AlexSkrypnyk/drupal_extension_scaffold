<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=Drupal+Module+Scaffold&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="Yourproject logo"></a>
</p>

<h1 align="center">Drupal module scaffold</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/AlexSkrypnyk/drupal_module_scaffold.svg)](https://github.com/AlexSkrypnyk/drupal_module_scaffold/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/AlexSkrypnyk/drupal_module_scaffold.svg)](https://github.com/AlexSkrypnyk/drupal_module_scaffold/pulls)
[![CircleCI](https://circleci.com/gh/AlexSkrypnyk/drupal_module_scaffold.svg?style=shield)](https://circleci.com/gh/AlexSkrypnyk/drupal_module_scaffold)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/drupal_module_scaffold)
![Drupal 9](https://img.shields.io/badge/Drupal-9-blue.svg) ![Drupal 10](https://img.shields.io/badge/Drupal-10-blue.svg)
![LICENSE](https://img.shields.io/github/license/AlexSkrypnyk/drupal_module_scaffold)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

</div>

---

<p align="center">

Template CI configuration for Drupal contrib modules testing on your CI provider
with mirroring to Drupal.org.

For Drupal 7 support, see [`7.x` branch](https://github.com/AlexSkrypnyk/drupal_module_scaffold/tree/7.x).

</p>


## Use case

Perform module development in GitHub with testing in CI, and push code
committed only to main branches (`1.x`, `2.x` etc.) to [drupal.org](https://drupal.org).

## Features

- Turnkey CI configuration with artifacts and test results support.
  - PHP version matrix for [8.2](https://www.php.net/supported-versions.php) and [8.1](https://www.php.net/supported-versions.php).
  - Drupal version matrix: stable, next and last EOL version.
  - CI builder container is based on official PHP docker image.
- Tools:
  - [Develop locally](#local-development) using PHP running on your host using identical scripts as CI.
  - PHP code standards checking against `Drupal` and `DrupalPractice` standards.
  - PHP code static analysis with [drupal-check](https://github.com/mglaman/drupal-check).
  - PHP deprecated code analysis with [Drupal Rector](https://github.com/palantirnet/drupal-rector).
  - Drupal's Simpletest testing support - runs tests in the same way as
    [drupal.org](https://drupal.org)'s Drupal CI bot (`core/scripts/run-tests.sh`).
  - Support for including of additional dependencies for integration testing between modules (add dependency into [`suggest`](composer.json#L22) section of `composer.json`).
  - Uses [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project)
  to provision Drupal site or custom fork.
- Deployment:
  - Mirroring of the repo to [drupal.org](https://drupal.org) (or any other git repo) on release.
  - Deploy to a destination branch different from the source branch.
  - Tags mirroring.
- This template is tested in the same way as a project using it.

<img src="https://user-images.githubusercontent.com/378794/253860380-7a702bf6-71f5-4c8c-a271-8dd3b25eaabf.png" alt="Screenshot of CI jobs" width="30%">

## Usage

1. Create your module's repository on GitHub.
2. Download this module's code by pressing 'Clone or download' button in GitHub UI.
3. Copy the contents of the downloaded archive into your module's repository.
4. Replace `drupal_module_scaffold` with the machine name of your module.
5. Adjust several lines in `.gitattributes`.
6. Commit and push to your new GitHub repo.
7. Login to your CI and add your new GitHub repository. Your project build will
   start momentarily.
8. Configure deployment to [drupal.org](https://drupal.org) (see below).

## Deployment

The CI supports mirroring of main branches (`1.x`, `10.x-1.x` etc.) to
[drupal.org](https://drupal.org) mirror of the project (to keep both repos in
sync).

The deployment job runs when commits are pushed to main branches
(`1.x`, `2.x`, `10.x-1.x` etc.) or when release tags are created.

Example of deployment repository: https://github.com/AlexSkrypnyk/drupal_module_scaffold_destination

### Configure deployment

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
       file with  this copied fingerprint string.
     - Push the code to your repository.
4. In CI, UI add the following variables:
   - `DEPLOY_USER_NAME` - the name of the user who will be committing to a
     remote repository (i.e., your name on drupal.org).
   - `DEPLOY_USER_EMAIL` - the email address of the user who will be committing
     to a remote repository (i.e., your email on drupal.org).
   - `DEPLOY_REMOTE` - your modules remote drupal.org repository (i.e. `git@git.drupal.org:project/mymodule.git`).
   - `DEPLOY_PROCEED` - set to `1` once CI is working, and you are ready to
     deploy.

## Maintenance / Local development

Provided that you have PHP installed locally, you can develop a module using
the provided scripts.

### Build

Run `.devtools/build.sh` (or `ahoy build` if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to start inbuilt PHP server locally and run the same
commands as in CI, plus installing a site and your module automatically.

![Build process](https://user-images.githubusercontent.com/378794/253732550-20bd3877-cabb-4a5a-b9a6-ffb5fe6c8e3e.gif)

### Code linting

Run `.devtools/lint.sh` (or `ahoy lint` if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to lint your code according to the
[Drupal coding standards](https://www.drupal.org/docs/develop/standards).

PHPCS config: `phpcs.xml`

PHPStan config: `phpstan.neon`

PHPMD config: `phpmd.xml`

TWIGCS config: `.twig_cs.php`

![Lint process](https://user-images.githubusercontent.com/378794/253732548-9403e4cc-db03-4696-b114-32517ab0a571.gif)

### Tests

Run `.devtools/test.sh` (or `ahoy test` if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to run all test for your module.

![Test process](https://user-images.githubusercontent.com/378794/253732542-ea1b2f29-ce89-41bd-b92a-169b119731d3.gif)

### Browsing SQLite database

To browse the contents of created SQLite database
(located at `/tmp/site_[MODULE_NAME].sqlite`), use [DB Browser for SQLite](https://sqlitebrowser.org/).

---

For an end-to-end website DevOps setup, check out [DrevOps](https://drevops.com) - Drupal project template
