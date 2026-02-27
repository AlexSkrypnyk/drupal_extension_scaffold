#!/usr/bin/env bats

load _helper

export BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1

@test "Assemble: Drupal 11" {
  run .devtools/assemble
  assert_success

  assert_output_contains "Initialising Drupal 11 site"

  assert_output_contains "ASSEMBLE COMPLETE"
  assert_dir_exists "${BUILD_DIR}/build/vendor"
  assert_file_exists "${BUILD_DIR}/build/composer.json"
  assert_file_exists "${BUILD_DIR}/build/composer.lock"

  # @see https://github.com/composer/composer/issues/12215
  run composer --working-dir="${BUILD_DIR}/build" require --dev drupal/coder --with-all-dependencies --dry-run
  assert_output_not_contains "Upgrading"
}

@test "Assemble: Drupal 10" {
  export DRUPAL_VERSION="10"

  run .devtools/assemble
  assert_success

  assert_output_contains "Initialising Drupal 10 site"

  assert_output_contains "ASSEMBLE COMPLETE"
  assert_dir_exists "${BUILD_DIR}/build/vendor"
  assert_file_exists "${BUILD_DIR}/build/composer.json"
  assert_file_exists "${BUILD_DIR}/build/composer.lock"

  # @see https://github.com/composer/composer/issues/12215
  run composer --working-dir="${BUILD_DIR}/build" require --dev drupal/coder --with-all-dependencies --dry-run
  assert_output_not_contains "Upgrading"
}
