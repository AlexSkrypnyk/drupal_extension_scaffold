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
@test "Init, defaults - extension module, workflow" {
  answers=(
    "YodasHut"       # namespace
    "Force Crystal"  # name
    "force_crystal"  # machine_name
    "module"         # type
    "GitHub Actions" # ci_provider
    "nothing"        # remove init script
    "nothing"        # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_not_exists ".circleci/config.yml"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

# bats test_tags=smoke
@test "Init, extension theme, workflow" {
  answers=(
    "YodasHut"       # namespace
    "Force Crystal"  # name
    "force_crystal"  # machine_name
    "theme"          # type
    "GitHub Actions" # ci_provider
    "nothing"        # remove init script
    "nothing"        # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_theme "${BUILD_DIR}"
  assert_file_not_exists ".circleci/config.yml"
  assert_output_contains "Initialization complete."

  assert_workflow "${BUILD_DIR}"
}

# bats test_tags=smoke
@test "Init, CircleCI" {
  answers=(
    "YodasHut"       # namespace
    "Force Crystal"  # name
    "force_crystal"  # machine_name
    "module"         # type
    "CircleCI"       # ci_provider
    "nothing"        # remove init script
    "nothing"        # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_exists ".circleci/config.yml"
  assert_dir_not_exists ".github/workflows"
  assert_output_contains "Initialization complete."
}

@test "Init, do not remove script" {
  answers=(
    "YodasHut"       # namespace
    "Force Crystal"  # name
    "force_crystal"  # machine_name
    "module"         # type
    "CircleCI"       # ci_provider
    "n"              # remove init script
    "nothing"        # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_exists "init.sh"
  assert_output_contains "Initialization complete."
}

@test "Init, remove script" {
  answers=(
    "YodasHut"       # namespace
    "Force Crystal"  # name
    "force_crystal"  # machine_name
    "module"         # type
    "CircleCI"       # ci_provider
    "y"              # remove init script
    "nothing"        # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_file_not_exists "init.sh"
  assert_output_contains "Initialization complete."
}
