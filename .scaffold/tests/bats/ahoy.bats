#!/usr/bin/env bats

load _helper

export BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1

@test "ahoy build" {
  ahoy build
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

  ahoy reset
}
