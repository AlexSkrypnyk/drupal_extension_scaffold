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

echo "==> Lint code."
build/vendor/bin/phpcs \
  -s \
  --standard=Drupal,DrupalPractice \
  --extensions=module,php,install,inc,test,info.yml,js \
  "build/web/modules/${MODULE}"
