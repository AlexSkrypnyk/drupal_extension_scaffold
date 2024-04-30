#!/usr/bin/env bash
#
# Helpers related to common testing functionality.
#
# Run with "--verbose-run" to see debug output.
#

################################################################################
#                       BATS HOOK IMPLEMENTATIONS                              #
################################################################################

setup() {
  [ ! -d ".git" ] && echo "Tests must be run from the repository root directory." && exit 1

  # For a list of available variables see:
  # @see https://bats-core.readthedocs.io/en/stable/writing-tests.html#special-variables

  # Register a path to libraries.
  export BATS_LIB_PATH="${BATS_TEST_DIRNAME}/node_modules"

  # Load 'bats-helpers' library.
  bats_load_library bats-helpers

  # Setup command mocking.
  setup_mock

  # Current directory where the test is run from.
  # shellcheck disable=SC2155
  export CUR_DIR="$(pwd)"

  # Project directory root (where .git is located).
  export ROOT_DIR="${CUR_DIR}"

  # Directory where the shell command script will be running in.
  export BUILD_DIR="${BUILD_DIR:-"${BATS_TEST_TMPDIR//\/\//\/}/scaffold-$(date +%s)"}"
  fixture_prepare_dir "${BUILD_DIR}"

  # Copy codebase at the last commit into the BUILD_DIR.
  # Tests requiring to work with the copy of the codebase should opt-in using
  # BATS_FIXTURE_EXPORT_CODEBASE_ENABLED=1.
  # Note that during development of tests the local changes need to be
  # committed.
  fixture_export_codebase "${BUILD_DIR}" "${ROOT_DIR}"

  # Print debug information if "--verbose-run" is passed.
  # LCOV_EXCL_START
  if [ "${BATS_VERBOSE_RUN-}" = "1" ]; then
    echo "BUILD_DIR: ${BUILD_DIR}" >&3
  fi
  # LCOV_EXCL_END

  # Change directory to the current project directory for each test. Tests
  # requiring to operate outside of BUILD_DIR should change directory explicitly
  # within their tests.
  pushd "${BUILD_DIR}" >/dev/null || exit 1
}

teardown() {
  # Move back to the original directory.
  popd >/dev/null || cd "${CUR_DIR}" || exit 1
}
