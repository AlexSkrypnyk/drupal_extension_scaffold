#!/usr/bin/env bats
#
# Smoke tests.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper

# ./tests/scaffold/node_modules/.bin/bats --no-tempdir-cleanup --formatter tap --filter-tags smoke tests/scaffold
# bats test_tags=smoke
@test "Smoke" {
  assert_contains "scaffold" "${BUILD_DIR}"
}
