#!/usr/bin/env bash
##
# Build the codebase.
#
# Allows to use the latest Drupal core as well as specified versions (for
# testing backward compatibility).
#
# - Retrieves the scaffold from drupal-composer/drupal-project or custom scaffold.
# - Builds Drupal site codebase with current module and it's dependencies.
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

# Drupal core version to use. If not provided - the latest stable version will be used.
# Must be coupled with DRUPAL_PROJECT_SHA below.
DRUPAL_VERSION="${DRUPAL_VERSION:-}"

# Commit SHA of the drupal-project to install custom core version. If not
# provided - the latest version will be used.
# Must be coupled with DRUPAL_VERSION above.
DRUPAL_PROJECT_SHA="${DRUPAL_PROJECT_SHA:-}"

# Repository for "drupal-composer/drupal-project" project.
# May be overwritten to use forked repos that may have not been accepted
# yet (i.e., when major Drupal version is about to be released).
DRUPAL_PROJECT_REPO="${DRUPAL_PROJECT_REPO:-https://github.com/drupal-composer/drupal-project.git}"

#-------------------------------------------------------------------------------

echo "-------------------------------"
echo "       Building codebase       "
echo "-------------------------------"

# Make sure Composer doesn't run out of memory.
export COMPOSER_MEMORY_LIMIT=-1

echo "> Validating tools."
! command -v git > /dev/null && echo "ERROR: Git is required for this script to run." && exit 1
! command -v php > /dev/null && echo "ERROR: PHP is required for this script to run." && exit 1
! command -v composer > /dev/null && echo "ERROR: Composer (https://getcomposer.org/) is required for this script to run." && exit 1
! command -v jq > /dev/null && echo "ERROR: jq (https://stedolan.github.io/jq/) is required for this script to run." && exit 1

# Module name, taken from the .info file.
module="$(basename -s .info.yml -- ./*.info.yml)"
[ "${module}" == "*" ] && echo "ERROR: No .info.yml file found." && exit 1

echo "> Validating Composer configuration."
composer validate --ansi --strict

# Reset the environment.
[ -d "build" ] && echo "> Removing existing build directory." && chmod -Rf 777 "build" && rm -rf "build"

# Allow installing custom version of Drupal core from drupal-composer/drupal-project,
# but only coupled with drupal-project SHA (required to get correct dependencies).
if [ -n "${DRUPAL_VERSION:-}" ] && [ -n "${DRUPAL_PROJECT_SHA:-}" ]; then
  echo "> Initialising Drupal site from the scaffold repo ${DRUPAL_PROJECT_REPO} commit ${DRUPAL_PROJECT_SHA}."

  # Clone Drupal core at the specific commit SHA.
  git clone -n "${DRUPAL_PROJECT_REPO}" "build"
  git --git-dir="build/.git" --work-tree="build" checkout "${DRUPAL_PROJECT_SHA}"
  rm -rf "build/.git" > /dev/null

  echo "> Pinning Drupal to a specific version ${DRUPAL_VERSION}."
  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  sed "${sed_opts[@]}" 's|\(.*"drupal\/core"\): "\(.*\)",.*|\1: '"\"$DRUPAL_VERSION\",|" "build/composer.json"
  cat "build/composer.json"
else
  echo "> Initialising Drupal site from the latest scaffold."
  # There are no releases in "drupal-composer/drupal-project", so have to use "@dev".
  composer create-project drupal-composer/drupal-project:@dev "build" --no-interaction --no-install
fi

echo "> Merging configuration from module's composer.json."
php -r "echo json_encode(array_replace_recursive(json_decode(file_get_contents('composer.json'), true),json_decode(file_get_contents('build/composer.json'), true)),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);" > "build/composer2.json" && mv -f "build/composer2.json" "build/composer.json"

echo "> Creating GitHub authentication token if provided."
[ -n "${GITHUB_TOKEN:-}" ] && composer config --global github-oauth.github.com "${GITHUB_TOKEN}" && echo "Token: " && composer config --global github-oauth.github.com

echo "> Installing dependencies."
composer --working-dir="build" install

# Suggested dependencies allow to install them for testing without requiring
# them in module's composer.json.
echo "> Installing suggested dependencies from module's composer.json."
composer_suggests=$(cat composer.json | jq -r 'select(.suggest != null) | .suggest | keys[]')
for composer_suggest in $composer_suggests; do
  composer --working-dir="build" require "${composer_suggest}"
done

echo "> Installing other dev dependencies."
composer --working-dir="build" config allow-plugins.phpstan/extension-installer true
composer --working-dir="build" require --dev \
  dealerdirect/phpcodesniffer-composer-installer \
  friendsoftwig/twigcs:^6.2 \
  mglaman/phpstan-drupal:^1.2 \
  palantirnet/drupal-rector:^0.18 \
  phpcompatibility/php-compatibility \
  phpmd/phpmd \
  phpspec/prophecy-phpunit:^2 \
  phpstan/extension-installer
cat <<< "$(jq --indent 4 '.extra["phpcodesniffer-search-depth"] = 10' "build/composer.json")" > "build/composer.json"

echo "> Copying tools configuration files."
cp phpcs.xml phpstan.neon phpmd.xml rector.php .twig_cs.php "build/"

echo "> Symlinking module code."
rm -rf "build/web/modules/custom" > /dev/null && mkdir -p "build/web/modules/custom/${module}"
ln -s "$(pwd)"/* "build/web/modules/custom/${module}" && rm "build/web/modules/custom/${module}/build"

echo
echo "-------------------------------"
echo "        Codebase built 🚀      "
echo "-------------------------------"
echo
echo "> Next steps:"
echo "  .devtools/start-server.sh # Start the webserver"
echo "  .devtools/provision.sh    # Provision the website"
echo
