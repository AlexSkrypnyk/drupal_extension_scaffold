#!/usr/bin/env bats
#
# Functional tests for init.sh.
#
# Example usage:
# ./.scaffold/tests/node_modules/.bin/bats --no-tempdir-cleanup --formatter tap --filter-tags smoke .scaffold/tests
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper
load _assert_init

export BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1
export SCRIPT_FILE="init.sh"

# bats test_tags=smoke
@test "Init, defaults, workflow" {
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
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

# bats test_tags=smoke
@test "Init, extension theme, workflow" {
  answers=(
    "YodasHut"      # organisation
    "force_crystal" # project
    "Force Crystal" # name
    "theme"         # type
    "Jane Doe"      # author
    "nothing"       # use NodeJS
    "nothing"       # use GitHub release drafter
    "nothing"       # use GitHub pr auto-assign
    "nothing"       # use GitHub funding
    "nothing"       # use GitHub PR template
    "nothing"       # use Renovate
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_theme "${BUILD_DIR}"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

# bats test_tags=smoke
@test "Init, no release drafter, workflow" {
  answers=(
    "YodasHut"      # organisation
    "force_crystal" # project
    "Force Crystal" # name
    "module"        # type
    "Jane Doe"      # author
    "nothing"       # use NodeJS
    "n"             # use GitHub release drafter
    "nothing"       # use GitHub pr auto-assign
    "nothing"       # use GitHub funding
    "nothing"       # use GitHub PR template
    "nothing"       # use Renovate
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_not_exists ".github/workflows/draft-release-notes.yml"
  assert_file_not_exists ".github/release-drafter.yml"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

# bats test_tags=smoke
@test "Init, no PR auto-assign, workflow" {
  answers=(
    "YodasHut"      # organisation
    "force_crystal" # project
    "Force Crystal" # name
    "module"        # type
    "Jane Doe"      # author
    "nothing"       # use NodeJS
    "nothing"       # use GitHub release drafter
    "n"             # use GitHub pr auto-assign
    "nothing"       # use GitHub funding
    "nothing"       # use GitHub PR template
    "nothing"       # use Renovate
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_not_exists ".github/assign-author.yml"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

@test "Init, no funding, workflow" {
  answers=(
    "YodasHut"      # organisation
    "force_crystal" # project
    "Force Crystal" # name
    "module"        # type
    "Jane Doe"      # author
    "nothing"       # use NodeJS
    "nothing"       # use GitHub release drafter
    "nothing"       # use GitHub pr auto-assign
    "n"             # use GitHub funding
    "nothing"       # use GitHub PR template
    "nothing"       # use Renovate
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_not_exists ".github/FUNDING.yml"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

@test "Init, no PR template, workflow" {
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
    "n"             # use GitHub PR template
    "nothing"       # use Renovate
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_not_exists ".github/PULL_REQUEST_TEMPLATE.md"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

@test "Init, no Renovate, workflow" {
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
    "n"             # use Renovate
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_not_exists "renovate.json"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

@test "Init, do not remove script, workflow" {
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
    "n"             # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_exists "init.sh"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

@test "Init, remove script, workflow" {
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
    "y"             # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_not_exists "init.sh"
  assert_output_contains "Initialization complete."

  #assert_workflow "${BUILD_DIR}"
}
