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

# bats file_tags=p0
# bats test_tags=smoke
@test "Init, defaults - extension module, workflow" {
  answers=(
    "Force Crystal" # name
    "force_crystal" # machine_name
    "module"        # type
    "gha"           # ci_provider
    "nothing"       # remove init script
    "nothing"       # command_wrapper
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_ci_provider_gha "${BUILD_DIR}"
  assert_command_wrapper_ahoy "${BUILD_DIR}"
  assert_output_contains "Initialization complete."

  assert_workflow_run "${BUILD_DIR}"
}

@test "Init, extension theme, workflow" {
  answers=(
    "Force Crystal" # name
    "force_crystal" # machine_name
    "theme"         # type
    "gha"           # ci_provider
    "nothing"       # command_wrapper
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_theme "${BUILD_DIR}"
  assert_ci_provider_gha "${BUILD_DIR}"
  assert_command_wrapper_ahoy "${BUILD_DIR}"
  assert_output_contains "Initialization complete."

  assert_workflow_run "${BUILD_DIR}"
}

@test "Init, circleci" {
  answers=(
    "Force Crystal" # name
    "force_crystal" # machine_name
    "module"        # type
    "circleci"      # ci_provider
    "nothing"       # command_wrapper
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_ci_provider_circleci "${BUILD_DIR}"
  assert_command_wrapper_ahoy "${BUILD_DIR}"
  assert_output_contains "Initialization complete."
}

@test "Init, Makefile" {
  answers=(
    "Force Crystal" # name
    "force_crystal" # machine_name
    "module"        # type
    "circleci"      # ci_provider
    "makefile"      # command_wrapper
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_ci_provider_circleci "${BUILD_DIR}"
  assert_command_wrapper_makefile "${BUILD_DIR}"
  assert_output_contains "Initialization complete."
}

@test "Init, no command wrapper" {
  answers=(
    "Force Crystal" # name
    "force_crystal" # machine_name
    "module"        # type
    "circleci"      # ci_provider
    "none"          # command_wrapper
    "nothing"       # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_ci_provider_circleci "${BUILD_DIR}"
  assert_command_wrapper_none "${BUILD_DIR}"
  assert_output_contains "Initialization complete."
}

@test "Init, do not remove script" {
  answers=(
    "Force Crystal" # name
    "force_crystal" # machine_name
    "module"        # type
    "gha"           # ci_provider
    "nothing"       # command_wrapper
    "n"             # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_ci_provider_gha "${BUILD_DIR}"
  assert_command_wrapper_ahoy "${BUILD_DIR}"
  assert_file_exists "init.sh"
  assert_output_contains "Initialization complete."
}

@test "Init, remove script" {
  answers=(
    "Force Crystal" # name
    "force_crystal" # machine_name
    "module"        # type
    "gha"           # ci_provider
    "nothing"       # command_wrapper
    "y"             # remove init script
    "nothing"       # proceed with init
  )
  tui_run "${answers[@]}"

  assert_output_contains "Please follow the prompts to adjust your extension configuration"
  assert_files_present_common "${BUILD_DIR}"
  assert_files_present_extension_type_module "${BUILD_DIR}"
  assert_ci_provider_gha "${BUILD_DIR}"
  assert_command_wrapper_ahoy "${BUILD_DIR}"
  assert_file_not_exists "init.sh"
  assert_output_contains "Initialization complete."
}
