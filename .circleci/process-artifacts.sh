#!/usr/bin/env bash
##
# Process test artifacts.
#
set -e

if [ -d "$(pwd)/build/web/sites/simpletest/browser_output" ]; then
  echo "==> Copying Simpletest test artifacts"
  mkdir -p /tmp/artifacts/simpletest
  cp -Rf "$(pwd)/build/web/sites/simpletest/browser_output/." /tmp/artifacts/simpletest
fi

if [ -d "$(pwd)/build/screenshots" ]; then
  echo "==> Copying Behat test artifacts"
  mkdir -p /tmp/artifacts/behat
  cp -Rf "$(pwd)/build/screenshots" /tmp/artifacts/simpletest
fi
