#!/usr/bin/env bash
##
# Run tests.
#

set -e

MODULE=$(basename -s .info.yml -- ./*.info.yml)

echo "==> Run tests"
[ ! -d tests ] && echo "==> No tests found. Skipping." && exit 0

mkdir -p /tmp/test_results/simpletest
rm -f /tmp/test.sqlite

php ./build/web/core/scripts/run-tests.sh \
  --sqlite /tmp/test.sqlite \
  --dburl sqlite://localhost//tmp/test.sqlite \
  --url http://localhost:8000 \
  --non-html \
  --xml /tmp/test_results/simpletest \
  --color \
  --verbose \
  --suppress-deprecations \
  --module "${MODULE}"
