#!/usr/bin/env bash
##
# Run lint checks.
#

set -e

MODULE=$(basename -s .info.yml -- ./*.info.yml)

echo "==> Lint code"
build/vendor/bin/phpcs -s --standard=Drupal,DrupalPractice "build/web/modules/${MODULE}"
