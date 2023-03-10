# Drupal CircleCI
Template CI configuration for Drupal contrib modules testing on CircleCI
with mirroring to Drupal.org.

[![CircleCI](https://circleci.com/gh/drevops/drupal_circleci.svg?style=shield)](https://circleci.com/gh/drevops/drupal_circleci)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/drevops/drupal_circleci)
![Drupal 9](https://img.shields.io/badge/Drupal-9-blue.svg) ![Drupal 10](https://img.shields.io/badge/Drupal-10-blue.svg)
![LICENSE](https://img.shields.io/github/license/drevops/drupal_circleci)

For Drupal 7 support, see [`7.x` branch](https://github.com/drevops/drupal_circleci/tree/7.x).

## Use case
Perform module development in GitHub with testing in CircleCI, and push code
committed only to main branches (`1.x`, `2.x` etc.) to [drupal.org](https://drupal.org).

## Features

- Turnkey CI configuration with artifacts and test results support.
- PHP version matrix for [8.1](https://www.php.net/supported-versions.php) and [8.0](https://www.php.net/supported-versions.php).
- Drupal version matrix: stable, next and last EOL version.
- PHP code standards checking against `Drupal` and `DrupalPractice` standards.
- PHP code static analysis with [drupal-check](https://github.com/mglaman/drupal-check).
- PHP deprecated code analysis with [Drupal Rector](https://github.com/palantirnet/drupal-rector).
- Drupal's Simpletest testing support - runs tests in the same way as
  [drupal.org](https://drupal.org)'s Drupal CI bot (`core/scripts/run-tests.sh`).
- Support for testing suggested dependencies (for integration testing between modules).
- Uses [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project)
  to provision Drupal site or custom fork.
- Mirroring of the repo to [drupal.org](https://drupal.org) (or any other git repo) on release.
- CI builder container is based on official PHP docker image.
- This template is tested in the same way as a project using it.

<img src="https://user-images.githubusercontent.com/378794/194235441-6da4914e-3114-4f54-8b43-d3f728e6ec60.png" alt="Screenshot of CI jobs" width="30%">

## Usage

1. Create your module's repository on GitHub.
2. Download this module's code by pressing 'Clone or download' button in GitHub UI.
3. Copy the contents of the downloaded archive into your module's repository.
4. Replace `drupal_circleci_example` with the machine name of your module.
5. Adjust several lines in `.gitignore`.
6. Commit and push to your new GitHub repo.
7. Login to CircleCI and add your new GitHub repository. Your project build will
   start momentarily.
8. Configure deployment to [drupal.org](https://drupal.org) (see below).

## Deployment

The CI supports mirroring of main branches (`9.x-1.x` etc.) to
[drupal.org](https://drupal.org) mirror of the project (to keep 2 repos in
sync).

The deployment job runs when commits are pushed to main branches
(`1.x`, `2.x`, `9.x-1.x` etc.) or when release tags are created.

Example of deployment repository: https://github.com/drevops/drupal_circleci_destination

### Configure deployment:
1. Generate a new SSH key without pass phrase:

       ssh-keygen -m PEM -t rsa -b 4096 -C "your_email@example.com"

2. Add public key to your [drupal.org](https://drupal.org) account:
   https://git.drupalcode.org/-/profile/keys

3. In CircleCI UI, go to your project -> **Settings** -> **SSH Permissions**
2. Put your private SSH key into the box. Leave **Hostname** empty.
3. Copy fingerprint string in CircleCI UI and replace `deploy_ssh_fingerprint`
   value in `.circleci/config.yml`.
4. In CircleCI UI go to your project -> **Settings** -> **Environment Variables**
   and add the following variables through CircleCI UI:
   - `DEPLOY_USER_NAME` - the name of the user who will be committing to a
     remote repository (i.e., your name on drupal.org).
   - `DEPLOY_USER_EMAIL` - the email address of the user who will be committing
     to a remote repository (i.e., your email on drupal.org).
   - `DEPLOY_REMOTE` - your modules remote drupal.org repository (i.e. `git@git.drupal.org:project/mymodule.git`).
   - `DEPLOY_PROCEED` - set to `1` once CI is working, and you are ready to
     deploy.

## Local module development

Provided that you have PHP installed locally, you can develop a module using
the provided scripts.

### Build
Run `.circleci/build.sh` (or `ahoy build` if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to start inbuilt PHP server locally and run the same
commands as in CI, plus installing a site and your module automatically.

### Code linting
Run `.circleci/lint.sh` (or `ahoy lint` if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to lint your code according to the
[Drupal coding standards](https://www.drupal.org/docs/develop/standards).

### Tests
Run `.circleci/test.sh` (or `ahoy test` if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to run all test for your module.

### Browsing SQLite database
To browse the contents of created SQLite database
(located at `/tmp/site_[MODULE_NAME].sqlite`), use [DB Browser for SQLite](https://sqlitebrowser.org/).

---

For an end-to-end website DevOps setup, check out [DrevOps](https://drevops.com) - Build, Test, Deploy scripts for Drupal using Docker and CI/CD
