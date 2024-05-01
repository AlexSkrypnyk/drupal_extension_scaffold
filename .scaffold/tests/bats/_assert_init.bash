#!/usr/bin/env bash
#
# Scaffold template assertions.
#

# This file structure should exist in every project type.
assert_files_present_common() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  # Assert that some files must be exist.
  assert_file_exists "force_crystal.info.yml"
  assert_file_exists "phpcs.xml"
  assert_file_exists "phpmd.xml"
  assert_file_exists "phpstan.neon"
  assert_file_exists ".editorconfig"
  assert_file_exists ".gitattributes"
  assert_file_exists ".gitignore"
  assert_file_exists "README.md"
  assert_file_exists "logo.png"
  assert_file_exists ".github/workflows/test.yml"
  assert_file_exists ".github/workflows/deploy.yml"
  assert_file_exists ".circleci/config.yml"

  # Assert that some files must not be exist.
  assert_file_not_exists "README.dist.md"
  assert_file_not_exists "logo.tmp.png"
  assert_file_not_exists "LICENSE"
  assert_file_not_exists ".github/workflows/test-scaffold.yml"

  # Assert some dirs/files must not be exist.
  assert_dir_not_exists ".scaffold"
  assert_dir_exists "tests"

  # Assert that .gitignore were processed correctly.
  assert_file_contains ".gitignore" "composer.lock"
  assert_file_contains ".gitignore" "build"
  assert_file_not_contains ".gitignore" "/coverage"

  # Assert that documentation was processed correctly.
  assert_file_not_contains README.md "Generic project scaffold template"
  assert_file_not_contains README.md "META"

  # Assert that .gitattributes were processed correctly.
  assert_file_contains ".gitattributes" ".editorconfig"
  assert_file_not_contains ".gitattributes" "# .editorconfig"
  assert_file_contains ".gitattributes" ".gitattributes"
  assert_file_not_contains ".gitattributes" "# .gitattributes"
  assert_file_contains ".gitattributes" ".github"
  assert_file_not_contains ".gitattributes" "# .github"
  assert_file_contains ".gitattributes" ".gitignore"
  assert_file_not_contains ".gitattributes" "# .gitignore"
  assert_file_not_contains ".gitattributes" "# Uncomment the lines below in your project."
  assert_file_contains ".gitattributes" "tests"
  assert_file_contains ".gitattributes" "phpcs.xml"
  assert_file_contains ".gitattributes" "phpmd.xml"
  assert_file_contains ".gitattributes" "phpstan.neon"
  assert_file_not_contains ".gitattributes" "# tests"
  assert_file_not_contains ".gitattributes" "# phpcs.xml"
  assert_file_not_contains ".gitattributes" "# phpmd.xml"
  assert_file_not_contains ".gitattributes" "# phpstan.neon"

  # Assert that composer.json were processed correctly.
  assert_file_contains "composer.json" '"name": "drupal/force_crystal"'
  assert_file_contains "composer.json" '"description": "Provides force_crystal functionality."'
  assert_file_contains "composer.json" '"name": "Jane Doe"'
  assert_file_contains "composer.json" '"homepage": "https://drupal.org/project/force_crystal"'
  assert_file_contains "composer.json" '"issues": "https://drupal.org/project/issues/force_crystal"'
  assert_file_contains "composer.json" '"source": "https://git.drupalcode.org/project/force_crystal"'

  # Assert that extension info file were processed correctly.
  assert_file_contains "force_crystal.info.yml" 'name: Force Crystal'

  # Assert other things.
  assert_dir_not_contains_string "${dir}" "your_extension"
  assert_dir_contains_string "${dir}" "YodasHut"

  popd >/dev/null || exit 1
}

assert_files_present_extension_type_module() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  # Assert that extension info file were processed correctly.
  assert_file_contains "force_crystal.info.yml" 'type: module'
  assert_file_not_contains "force_crystal.info.yml" 'type: theme'

  # Assert that composer.json file were processed correctly.
  assert_file_contains "composer.json" '"type": "drupal-module"'

  # Assert some dirs/files must be exist.
  assert_dir_exists "tests/src/Unit"
  assert_dir_exists "tests/src/Functional"

  popd >/dev/null || exit 1
}

assert_files_present_extension_type_theme() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  # Assert that extension info file were processed correctly.
  assert_file_contains "force_crystal.info.yml" 'type: theme'
  assert_file_not_contains "force_crystal.info.yml" 'type: module'

  # Assert that composer.json file were processed correctly.
  assert_file_contains "composer.json" '"type": "drupal-theme"'

  # Assert some dirs/files must not be exist.
  assert_dir_not_exists "tests/src/Unit"
  assert_dir_not_exists "tests/src/Functional"

  popd >/dev/null || exit 1
}

assert_workflow_php() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  ./.devtools/build-codebase.sh
  ./.devtools/start-server.sh
  ./.devtools/provision.sh

#   # Lint.
#   pushd "build" >/dev/null || exit 1
#   vendor/bin/phpcs
#   vendor/bin/phpstan
#   vendor/bin/rector --clear-cache --dry-run
#   vendor/bin/phpmd . text phpmd.xml
#   vendor/bin/twigcs
#   popd >/dev/null || exit 1
#
#   # Test.
#   ./.devtools/test.sh
#
#   # Change mode to make bats have enough permission to clean tmp test directory.
#   chmod -R 777 "build/web/sites/default"

  popd >/dev/null || exit 1
}
