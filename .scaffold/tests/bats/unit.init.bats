#!/usr/bin/env bats
#
# Unit tests for init.sh.
#
# shellcheck disable=SC2034

load _helper
load "../../../init.sh"

# bats file_tags=p0
@test "Test all conversions" {
  input="I am a_string-With spaces 13"

  TEST_CASES=(
    "I am a_string-With spaces 13" "file_name" "i_am_a_string-with_spaces_13"
    "I am a_string-With spaces 13" "route_path" "i_am_a_string-with_spaces_13"
    "I am a_string-With spaces 13" "deployment_id" "i_am_a_string-with_spaces_13"
    "I am a_string-With spaces 13" "domain_name" "i_am_a_stringwith_spaces_13"
    "I am a_string-With spaces 13" "namespace" "IAmAStringWithSpaces13"
    "I am a_string-With spaces 13" "package_name" "i-am-a_string-with-spaces-13"
    "I am a_string-With spaces 13" "function_name" "i_am_a_string-with_spaces_13"
    "I am a_string-With spaces 13" "ui_id" "i_am_a_string-with_spaces_13"
    "I am a_string-With spaces 13" "cli_command" "i_am_a_string-with_spaces_13"
    "I am a_string-With spaces 13" "log_entry" "I am a_string-With spaces 13"
    "I am a_string-With spaces 13" "code_comment_title" "I am a_string-With spaces 13"
    "I am a_string-With spaces 13" "dummy_type" "Invalid conversion type"
  )

  dataprovider_run "convert_string" 3
}
