#!/usr/bin/env bash
##
# Assemble a codebase using project code and all required dependencies.
#
# Allows to use the latest Drupal core as well as specified versions (for
# testing backward compatibility).
#
# - Retrieves the scaffold from drupal-composer/drupal-project or custom scaffold.
# - Builds Drupal site codebase with current extension and it's dependencies.
# - Adds development dependencies.
# - Installs composer dependencies.
#
# This script will re-build the codebase from scratch every time it runs.

# shellcheck disable=SC2015,SC2094,SC2002

set -eu
[ -n "${DEBUG:-}" ] && set -x

#-------------------------------------------------------------------------------
# Variables (passed from environment; provided for reference only).
#-------------------------------------------------------------------------------

# Drupal core version to use.
DRUPAL_VERSION="${DRUPAL_VERSION:-11}"

# Commit SHA of the drupal-project to install custom core version. If not
# provided - will be calculated from `$DRUPAL_VERSION` above.
DRUPAL_PROJECT_SHA="${DRUPAL_PROJECT_SHA:-}"

# Repository for "drupal-composer/drupal-project" project.
# May be overwritten to use forked repos that may have not been accepted
# yet (i.e., when major Drupal version is about to be released).
DRUPAL_PROJECT_REPO="${DRUPAL_PROJECT_REPO:-https://github.com/drupal-composer/drupal-project.git}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

#-------------------------------------------------------------------------------

echo "==============================="
echo "         🏗️ ASSEMBLE           "
echo "==============================="
echo

# Validate required variables.
if [ -z "${DRUPAL_VERSION}" ]; then
  fail "ERROR: DRUPAL_VERSION is not set."
  exit 1
fi

if [ -z "${DRUPAL_PROJECT_REPO}" ]; then
  fail "ERROR: DRUPAL_PROJECT_REPO is not set."
  exit 1
fi

# Make sure Composer doesn't run out of memory.
export COMPOSER_MEMORY_LIMIT=-1

task "Validating tools."
! command -v git >/dev/null && fail "ERROR: Git is required for this script to run." && exit 1
! command -v php >/dev/null && fail "ERROR: PHP is required for this script to run." && exit 1
! command -v composer >/dev/null && fail "ERROR: Composer (https://getcomposer.org/) is required for this script to run." && exit 1
! command -v jq >/dev/null && fail "ERROR: jq (https://stedolan.github.io/jq/) is required for this script to run." && exit 1
pass "Tools are valid."

# Set the sed options based on the OS.
sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')

# Extension name, taken from the .info file.
extension="$(basename -s .info.yml -- ./*.info.yml)"
[ "${extension}" == "*" ] && fail "ERROR: No .info.yml file found." && exit 1

# Extension type.
type=$(grep -q "type: theme" "${extension}.info.yml" && echo "themes" || echo "modules")

task "Validating Composer configuration."
composer validate --ansi --strict

# Reset the environment.
if [ -d "build" ]; then
  task "Removing existing build directory."
  chmod -Rf 777 "build" >/dev/null || true
  rm -rf "build" >/dev/null || true
  pass "Existing build directory removed."
fi

task "Creating Drupal codebase."

drupal_version_major="$(echo "${DRUPAL_VERSION}" | cut -d '.' -f 1 | cut -d '@' -f 1)"
drupal_version_stability="$(echo "${DRUPAL_VERSION}" | sed -n 's/.*@\(.*\)/\1/p')"
drupal_version_stability="${drupal_version_stability:-stable}"

DRUPAL_PROJECT_SHA="${DRUPAL_PROJECT_SHA:-"${drupal_version_major}.x"}"

note "Initialising Drupal ${DRUPAL_VERSION} site from the scaffold repo ${DRUPAL_PROJECT_REPO} ref ${DRUPAL_PROJECT_SHA}."

# Clone Drupal project at the specific commit SHA.
git clone -n "${DRUPAL_PROJECT_REPO}" "build"
git --git-dir="build/.git" --work-tree="build" checkout "${DRUPAL_PROJECT_SHA}"
rm -rf "build/.git" >/dev/null

note "Pinning Drupal to a specific version ${DRUPAL_VERSION}."
sed "${sed_opts[@]}" 's|\(.*"drupal\/core.*"\): "\(.*\)",.*|\1: '"\"~$DRUPAL_VERSION\",|" "build/composer.json"
grep '"drupal/core-.*": "' "build/composer.json"

note "Updating stability to ${drupal_version_stability}."
# Do not rely on the values coming from the scaffold and always set them.
sed "${sed_opts[@]}" 's|\(.*"minimum-stability"\): "\(.*\)",.*|\1: '"\"${drupal_version_stability}\",|" "build/composer.json"
grep 'minimum-stability' "build/composer.json"

drupal_version_prefer_stable="true"
if [ "${drupal_version_stability}" != "stable" ]; then
  drupal_version_prefer_stable="false"
fi
sed "${sed_opts[@]}" 's|\(.*"prefer-stable"\): \(.*\),.*|\1: '${drupal_version_prefer_stable}',|' "build/composer.json"
grep 'prefer-stable' "build/composer.json"

task "Merging configuration from composer.dev.json."
php -r "echo json_encode(array_replace_recursive(json_decode(file_get_contents('composer.dev.json'), true),json_decode(file_get_contents('build/composer.json'), true)),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);" >"build/composer2.json" && mv -f "build/composer2.json" "build/composer.json"

task "Merging configuration from extension's composer.json."
php -r "echo json_encode(array_replace_recursive(json_decode(file_get_contents('composer.json'), true),json_decode(file_get_contents('build/composer.json'), true)),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);" >"build/composer2.json" && mv -f "build/composer2.json" "build/composer.json"

# Asset Packagist sometimes fails, so we remove it by default. If it's needed,
# the lines below can be commented out.
task "Removing asset-packagist"
composer --working-dir="build" config --unset repositories.1 || true

if [ -d "patches" ]; then
  task "Copying patches."
  mkdir -p "build/patches"
  cp -r patches/* "build/patches/"
fi

if [ -n "${GITHUB_TOKEN:-}" ]; then
  task "Adding GitHub authentication token if provided."
  composer config --global github-oauth.github.com "${GITHUB_TOKEN}"
  composer config --global github-oauth.github.com | grep -q "gh" || fail "GitHub token not added."
  pass "GitHub token added."
fi

task "Creating custom directories."
mkdir -p build/web/modules/custom build/web/themes/custom

task "Installing dependencies."
composer --working-dir="build" install
pass "Dependencies installed."

# Suggested dependencies allow to install them for testing without requiring
# them in extension's composer.json.
task "Installing suggested dependencies from extension's composer.json."
composer_suggests=$(cat composer.json | jq -r 'select(.suggest != null) | .suggest | keys[]')
for composer_suggest in $composer_suggests; do
  composer --working-dir="build" require "${composer_suggest}"
done
pass "Suggested dependencies installed."

# Some versions of Drupal enforce maximum verbosity for errors by default. This
# leads to functional tests failing due to deprecation notices showing up in
# the page output, which is then interpreted as a test failure by PHPUnit.
# To address this, we inject the error_reporting() without deprecations into
# the index.php and default.settings.php file used by the system under test.
# But we do this only if the deprecations helper is set to `disable` which means
# to ignore deprecation notices.
# @see https://www.drupal.org/project/drupal/issues/1267246
if [ "${SYMFONY_DEPRECATIONS_HELPER-}" == "disabled" ]; then
  task "Disabling deprecation notices in functional tests."
  echo "error_reporting(E_ALL & ~E_DEPRECATED);" >>build/web/sites/default/default.settings.php
  sed "${sed_opts[@]}" 's/^<?php/<?php error_reporting(E_ALL \& ~E_DEPRECATED);/' build/web/index.php
  # shellcheck disable=SC2016
  sed "${sed_opts[@]}" 's/string $request_data = null, array $controls = null/string $request_data, array $controls/' build/vendor/symfony/polyfill-php83/bootstrap.php || true
  pass "Deprecation notices disabled."
fi

# If front-end dependencies are used in the project, package-lock.json is
# expected to be committed to the repository.
if [ -f "package-lock.json" ]; then
  task "Installing front-end dependencies."
  if [ -f ".nvmrc" ]; then nvm use || true; fi
  if [ ! -d "node_modules" ]; then npm ci; fi

  task "Building front-end dependencies."
  if [ ! -f ".skip_npm_build" ]; then npm run build; fi
  pass "Front-end dependencies installed."
fi

task "Copying tools configuration files."
# Not every tool correctly resolves the path to the configuration file if it is
# symlinked, so we copy them instead.
cp phpcs.xml phpstan.neon phpmd.xml rector.php .twig-cs-fixer.php phpunit.xml "build/"
[ -f "phpunit.d${drupal_version_major}.xml" ] && cp "phpunit.d${drupal_version_major}.xml" "build/phpunit.xml"
pass "Tools configuration files copied."

task "Symlinking extension's code."
rm -rf "build/web/${type}/custom" >/dev/null && mkdir -p "build/web/${type}/custom/${extension}"
ln -s "$(pwd)"/* "build/web/${type}/custom/${extension}" && rm "build/web/${type}/custom/${extension}/build"
pass "Extension's code symlinked."

echo
echo "==============================="
echo "    🏗 ASSEMBLE COMPLETE ✅   "
echo "==============================="
echo
echo "> Next steps:"
echo "  .devtools/start.sh        # Start the webserver"
echo "  .devtools/provision.sh    # Provision the website"
echo
