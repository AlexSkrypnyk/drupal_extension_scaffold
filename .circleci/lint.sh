#!/usr/bin/env bash
##
# Run lint checks.
#

set -e

MODULE=$(basename -s .info.yml -- ./*.info.yml)

echo "==> Lint code"
build/vendor/bin/phpcs -s --standard=Drupal,DrupalPractice --extensions=module,php,install,inc,test,info.yml,js "build/web/modules/${MODULE}"
