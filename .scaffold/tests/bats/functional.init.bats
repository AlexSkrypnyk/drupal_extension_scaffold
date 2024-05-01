#!/usr/bin/env bats
#
# Functional tests for init.sh.
#
# Example usage:
# ./tests/scaffold/node_modules/.bin/bats --no-tempdir-cleanup --formatter tap --filter-tags smoke tests/scaffold
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _assert_init

export BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1
export SCRIPT_FILE="init.sh"

# bats test_tags=smoke
@test "Init, defaults" {
  answers=(
    "YodasHut"      # organisation
    "force_crystal" # project
    "Force Crystal" # name
    "module"        # type
    "Jane Doe"      # author
    "nothing"       # use NodeJS
    "nothing"       # use GitHub release drafter
    "nothing"       # use GitHub pr auto-assign
    "nothing"       # use GitHub funding
    "nothing"       # use GitHub PR template
    "nothing"       # use Renovate
    "nothing"       # remove docs
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
}

# bats test_tags=smoke
@test "Init, theme" {
  answers=(
    "YodasHut"      # organisation
    "force_crystal" # project
    "Force Crystal" # name
    "module"        # theme
    "Jane Doe"      # author
    "nothing"       # use NodeJS
    "nothing"       # use GitHub release drafter
    "nothing"       # use GitHub pr auto-assign
    "nothing"       # use GitHub funding
    "nothing"       # use GitHub PR template
    "nothing"       # use Renovate
    "nothing"       # remove docs
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_theme "${BUILD_DIR}"
}
