#!/usr/bin/env bash
##
# Run lint checks.
#

set -eu
[ -n "${DEBUG:-}" ] && set -x

#-------------------------------------------------------------------------------
# Variables (passed from environment; provided for reference only).
#-------------------------------------------------------------------------------

# Directory where Drupal site will be built.
BUILD_DIR="${BUILD_DIR:-build}"

# Module name, taken from .info file.
MODULE="$(basename -s .info.yml -- ./*.info.yml)"

#-------------------------------------------------------------------------------

pushd "${BUILD_DIR}" >/dev/null || exit 1

echo "==> Lint code for module $MODULE."
echo "  > Running PHPCS, PHPMD, TWIGCS"
vendor/bin/phpcs

echo "  > Running PHPMD"
vendor/bin/phpmd --exclude vendor,vendor-bin,node_modules . text phpmd.xml

echo "  > Running TWIGCS"
vendor/bin/twigcs

echo " > Running phpstan."
vendor/bin/phpstan

echo "  > Running Drupal Rector."
vendor/bin/rector --dry-run

popd >/dev/null || exit 1
