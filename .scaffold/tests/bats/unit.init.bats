#!/usr/bin/env bats
#
# Unit tests for init.sh.
#
# shellcheck disable=SC2034

load _helper
load "../../../init.sh"

@test "Test all conversions" {
  input="I am a_string-With spaces 13"

  TEST_CASES=(
    "$input" "file_name" "i_am_a_string-with_spaces_13"
    "$input" "route_path" "i_am_a_string-with_spaces_13"
  )

  dataprovider_run "convert_string" 3
}
