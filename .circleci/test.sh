#!/usr/bin/env bash
##
# Run tests.
#

set -e

MODULE=$(basename -s .info -- ./*.info)

echo "==> Run tests"
mkdir -p /tmp/test_results/simpletest
rm -f /tmp/test.sqlite

php ./build/web/scripts/run-tests.sh \
  --url http://localhost:8000 \
  --xml /tmp/test_results/simpletest \
  --color \
  --verbose \
  --directory "sites/all/modules/${MODULE}"
