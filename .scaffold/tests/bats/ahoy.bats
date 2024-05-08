#!/usr/bin/env bats

load _helper

export BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1

@test "ahoy build" {
  run ahoy build
  assert_success
  assert_output_contains "PROVISION COMPLETE"
}

@test "ahoy assemble" {
  run ahoy assemble
  assert_success

  assert_output_contains "ASSEMBLE COMPLETE"
  assert_dir_exists "${BUILD_DIR}/build/vendor"
  assert_file_exists "${BUILD_DIR}/build/composer.json"
  assert_file_exists "${BUILD_DIR}/build/composer.lock"
}

@test "ahoy start" {
  run ahoy start
  assert_failure
  assert_output_not_contains "ENVIRONMENT READY"

  ahoy assemble
  run ahoy start
  assert_success

  assert_output_contains "ENVIRONMENT READY"
}

@test "ahoy stop" {
  run ahoy stop
  assert_success
  assert_output_contains "ENVIRONMENT STOPPED"

  ahoy assemble
  ahoy start

  run ahoy stop
  assert_success
  assert_output_contains "ENVIRONMENT STOPPED"
}

@test "ahoy build - basic workflow" {
  run ahoy build
  assert_success
  assert_output_contains "PROVISION COMPLETE"

  run ahoy drush status
  assert_success
  assert_output_contains "Database         : Connected"
  assert_output_contains "Drupal bootstrap : Successful"

  run ahoy login
  assert_success
  assert_output_contains "user/reset/1/"

  ahoy lint
  assert_success

  ahoy test
  assert_success

  ahoy test-unit
  assert_success

  ahoy test-kernel
  assert_success

  ahoy test-functional
  assert_success
}

@test "ahoy lint, lint-fix" {
  ahoy assemble
  assert_success

  ahoy lint
  assert_success

  # shellcheck disable=SC2016
  echo '$a=123;echo $a;' >>your_extension.module
  run ahoy lint
  assert_failure

  run ahoy lint-fix
  run ahoy lint
  assert_success
}

@test "ahoy test unit failure" {
  run ahoy assemble
  assert_success

  run ahoy test-unit
  assert_success

  sed -i -e "s/assertEquals/assertNotEquals/g" "${BUILD_DIR}/tests/src/Unit/YourExtensionServiceUnitTest.php"
  run ahoy test-unit
  assert_failure
}

@test "ahoy test functional failure" {
  run ahoy build
  assert_success

  run ahoy test-functional
  assert_success

  sed -i -e "s/responseContains/responseNotContains/g" "${BUILD_DIR}/tests/src/Functional/YourExtensionFunctionalTest.php"
  run ahoy test-functional
  assert_failure
}

@test "ahoy test kernel failure" {
  run ahoy build
  assert_success

  run ahoy test-kernel
  assert_success

  sed -i -e "s/assertEquals/assertNotEquals/g" "${BUILD_DIR}/tests/src/Kernel/YourExtensionServiceKernelTest.php"
  run ahoy test-kernel
  assert_failure
}
