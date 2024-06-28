#!/usr/bin/env bats

load _helper

export BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1

# bats file_tags=p2
@test "make default" {
  run make
  assert_success

  assert_output_contains "ASSEMBLE COMPLETE"
  assert_output_contains "PROVISION COMPLETE"

  assert_dir_exists "${BUILD_DIR}/build/vendor"
  assert_file_exists "${BUILD_DIR}/build/composer.json"
  assert_file_exists "${BUILD_DIR}/build/composer.lock"
}

@test "make assemble" {
  run make assemble
  assert_success

  assert_output_contains "ASSEMBLE COMPLETE"
  assert_dir_exists "${BUILD_DIR}/build/vendor"
  assert_file_exists "${BUILD_DIR}/build/composer.json"
  assert_file_exists "${BUILD_DIR}/build/composer.lock"
  assert_dir_exists "${BUILD_DIR}/node_modules"
  assert_output_contains "Would run build"
}

@test "make assemble - skip NPM build" {
  touch ".skip_npm_build"

  run make assemble
  assert_success

  assert_output_contains "ASSEMBLE COMPLETE"
  assert_dir_exists "${BUILD_DIR}/build/vendor"
  assert_file_exists "${BUILD_DIR}/build/composer.json"
  assert_file_exists "${BUILD_DIR}/build/composer.lock"
  assert_dir_exists "${BUILD_DIR}/node_modules"
  assert_output_not_contains "Would run build"
}

@test "make start" {
  run make start
  assert_failure

  make assemble
  run make start
  assert_success

  assert_output_contains "ENVIRONMENT READY"
}

@test "make stop" {
  run make stop
  assert_success
  assert_output_contains "ENVIRONMENT STOPPED"

  make assemble
  make start

  run make stop
  assert_success

  assert_output_contains "ENVIRONMENT STOPPED"
}

@test "make build - basic workflow" {
  run make build
  assert_success
  assert_output_contains "PROVISION COMPLETE"

  run make drush status
  assert_success
  assert_output_contains "Database         : Connected"
  assert_output_contains "Drupal bootstrap : Successful"

  run make login
  assert_success
  assert_output_contains "user/reset/1/"

  make lint
  assert_success

  make test
  assert_success
  assert_dir_exists "${BUILD_DIR}/build/web/sites/simpletest/browser_output"
}

@test "make lint, lint-fix" {
  make assemble
  assert_success

  make lint
  assert_success

  # shellcheck disable=SC2016
  echo '$a=123;echo $a;' >>your_extension.module
  run make lint
  assert_failure

  run make lint-fix
  run make lint
  assert_success
}

@test "make test unit failure" {
  run make assemble
  assert_success

  run make test-unit
  assert_success

  sed -i -e "s/assertEquals/assertNotEquals/g" "${BUILD_DIR}/tests/src/Unit/YourExtensionServiceUnitTest.php"
  run make test-unit
  assert_failure
}

@test "make test functional failure" {
  run make build
  assert_success

  run make test-functional
  assert_success
  assert_dir_exists "${BUILD_DIR}/build/web/sites/simpletest/browser_output"

  sed -i -e "s/responseContains/responseNotContains/g" "${BUILD_DIR}/tests/src/Functional/YourExtensionFunctionalTest.php"
  run make test-functional
  assert_failure
}

@test "make test kernel failure" {
  run make build
  assert_success

  run make test-kernel
  assert_success

  sed -i -e "s/assertEquals/assertNotEquals/g" "${BUILD_DIR}/tests/src/Kernel/YourExtensionServiceKernelTest.php"
  run make test-kernel
  assert_failure
}
