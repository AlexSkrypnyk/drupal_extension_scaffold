#!/usr/bin/env bash
##
# Run lint checks.
#

set -e

#-------------------------------------------------------------------------------
# Variables (passed from environment; provided for reference only).
#-------------------------------------------------------------------------------

# Directory where Drupal site will be built.
BUILD_DIR="${BUILD_DIR:-build}"

# Module name, taken from .info file.
MODULE="$(basename -s .info.yml -- ./*.info.yml)"

#-------------------------------------------------------------------------------

echo "==> Lint code for module $MODULE."
echo "  > Running PHPCS."
build/vendor/bin/phpcs \
  -s \
  -p \
  --standard=Drupal,DrupalPractice \
  --extensions=module,php,install,inc,test,info.yml,js \
  "build/web/modules/${MODULE}"

echo "  > Running drupal-check."
build/vendor/bin/drupal-check \
  --drupal-root=build/web \
  "build/web/modules/${MODULE}"

echo "  > Running Drupal Rector."
pushd "build" >/dev/null || exit 1
vendor/bin/rector process \
  "web/modules/${MODULE}" \
  --dry-run
popd >/dev/null || exit 1
