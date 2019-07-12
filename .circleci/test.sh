#!/usr/bin/env bash
##
# Run tests.
#
set -e

MODULE=$(basename -s .info.yml -- ./*.info.yml)

echo "==> Lint code"
build/vendor/bin/phpcs -s --standard=Drupal,DrupalPractice "build/web/modules/${MODULE}"

echo "==> Run tests"
mkdir -p /tmp/test_results/simpletest
php ./build/web/core/scripts/run-tests.sh \
  --sqlite /tmp/test.sqlite \
  --dburl sqlite://localhost//tmp/test.sqlite \
  --url http://localhost:8000 \
  --non-html \
  --xml /tmp/test_results/simpletest \
  --color \
  --verbose \
  --module "${MODULE}"
