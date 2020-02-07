#!/usr/bin/env bash
##
# Run lint checks.
#

set -e

MODULE=$(basename -s .info -- ./*.info)

echo "==> Lint code"
build/vendor/bin/phpcs -s --standard=Drupal,DrupalPractice --extensions=module,php,install,inc,test,info,js "build/web/sites/all/modules/${MODULE}"
