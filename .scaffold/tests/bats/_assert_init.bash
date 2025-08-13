#!/usr/bin/env bash
#
# Scaffold template assertions.
#

# This file structure should exist in every project type.
assert_files_present_common() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  # Assert that some files must exist.
  assert_file_exists ".editorconfig"
  assert_file_exists ".gitattributes"
  assert_file_exists ".gitignore"
  assert_file_exists "README.md"
  assert_file_exists "composer.dev.json"
  assert_file_exists "composer.json"
  assert_file_exists "force_crystal.info.yml"
  assert_file_exists "logo.png"
  assert_file_exists "phpcs.xml"
  assert_file_exists "phpmd.xml"
  assert_file_exists "phpstan.neon"
  assert_file_exists "rector.php"

  # Assert that some files must not exist.
  assert_dir_not_exists ".scaffold"
  assert_file_not_exists ".github/workflows/scaffold-release.yml"
  assert_file_not_exists ".github/workflows/scaffold-test.yml"
  assert_file_not_exists "LICENSE.txt"
  assert_file_not_exists "README.dist.md"
  assert_file_not_exists "logo.tmp.png"

  # Assert that .gitignore were processed correctly.
  assert_file_contains ".gitignore" "composer.lock"
  assert_file_contains ".gitignore" "build"
  assert_file_not_contains ".gitignore" "/coverage"

  # Assert that documentation was processed correctly.
  assert_file_not_contains README.md "META"

  # Assert that .gitattributes were processed correctly.
  assert_file_contains ".gitattributes" ".editorconfig"
  assert_file_contains ".gitattributes" ".gitattributes"
  assert_file_contains ".gitattributes" ".github"
  assert_file_contains ".gitattributes" ".gitignore"
  assert_file_contains ".gitattributes" "package-lock.json"
  assert_file_contains ".gitattributes" "package.json"
  assert_file_contains ".gitattributes" "patches"
  assert_file_contains ".gitattributes" "phpcs.xml"
  assert_file_contains ".gitattributes" "phpmd.xml"
  assert_file_contains ".gitattributes" "phpstan.neon"
  assert_file_contains ".gitattributes" "phpunit.d10.xml"
  assert_file_contains ".gitattributes" "phpunit.xml"
  assert_file_contains ".gitattributes" "tests"
  assert_file_not_contains ".gitattributes" "# .editorconfig"
  assert_file_not_contains ".gitattributes" "# .gitattributes"
  assert_file_not_contains ".gitattributes" "# .github"
  assert_file_not_contains ".gitattributes" "# .gitignore"
  assert_file_not_contains ".gitattributes" "# Uncomment the lines below in your project."
  assert_file_not_contains ".gitattributes" "# package-lock.json"
  assert_file_not_contains ".gitattributes" "# package.json"
  assert_file_not_contains ".gitattributes" "# patches"
  assert_file_not_contains ".gitattributes" "# phpcs.xml"
  assert_file_not_contains ".gitattributes" "# phpmd.xml"
  assert_file_not_contains ".gitattributes" "# phpstan.neon"
  assert_file_not_contains ".gitattributes" "# phpunit.d10.xml"
  assert_file_not_contains ".gitattributes" "# phpunit.xml"
  assert_file_not_contains ".gitattributes" "# tests"

  # Assert that composer.json was processed correctly.
  assert_file_contains "composer.json" '"name": "drupal/force_crystal"'
  assert_file_contains "composer.json" '"description": "Provides force_crystal functionality."'
  assert_file_contains "composer.json" '"homepage": "https://drupal.org/project/force_crystal"'
  assert_file_contains "composer.json" '"issues": "https://drupal.org/project/issues/force_crystal"'
  assert_file_contains "composer.json" '"source": "https://git.drupalcode.org/project/force_crystal"'

  # Assert that extension info file was processed correctly.
  assert_file_contains "force_crystal.info.yml" 'name: Force Crystal'

  # Assert other things.
  assert_dir_not_contains_string "${dir}" "your_extension"
  assert_dir_not_contains_string "${dir}" "your-extension"

  popd >/dev/null || exit 1
}

assert_composer_archive() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  # Add files not included in the project, but supported by the scripts.
  touch .skip_npm_build
  mkdir patches
  touch patches/patch1.patch

  # Archive the project using Composer.
  archive_file_no_ext="force_crystal-1.x-dev"
  archive_file="${archive_file_no_ext}.zip"
  composer archive --format=zip --file="${archive_file_no_ext}"
  assert_file_exists "${archive_file}"

  # Move archive file to keep the current directory clean.
  archive_dir="${RUN_DIR}/archive"
  fixture_prepare_dir "${archive_dir}"
  mv "${archive_file}" "${archive_dir}"
  assert_file_exists "${archive_dir}/${archive_file}"
  assert_file_not_exists "${archive_file}"

  # Extract the archive.
  archive_extract_dir="${RUN_DIR}/archive_extract"
  fixture_prepare_dir "${archive_extract_dir}"
  unzip "${archive_dir}/${archive_file}" -d "${archive_extract_dir}"

  pushd "${archive_extract_dir}" >/dev/null || exit 1

  assert_dir_exists config
  assert_dir_exists src
  assert_file_exists README.md
  assert_file_exists composer.json
  assert_file_exists force_crystal.info.yml
  assert_file_exists force_crystal.install
  assert_file_exists force_crystal.links.menu.yml
  assert_file_exists force_crystal.module
  assert_file_exists force_crystal.routing.yml
  assert_file_exists force_crystal.services.yml
  assert_file_exists logo.png

  assert_dir_not_exists tests
  assert_file_not_exists .ahoy.yml
  assert_file_not_exists .circleci
  assert_file_not_exists .devtools
  assert_file_not_exists .editorconfig
  assert_file_not_exists .gitattributes
  assert_file_not_exists .github
  assert_file_not_exists .gitignore
  assert_file_not_exists .skip_npm_build
  assert_file_not_exists .twig-cs-fixer.php
  assert_file_not_exists Makefile
  assert_file_not_exists composer.dev.json
  assert_file_not_exists package-lock.json
  assert_file_not_exists package-lock.json
  assert_file_not_exists package.json
  assert_file_not_exists package.json
  assert_file_not_exists phpcs.xml
  assert_file_not_exists phpmd.xml
  assert_file_not_exists phpstan.neon
  assert_file_not_exists phpunit.d10.xml
  assert_file_not_exists phpunit.xml
  assert_file_not_exists rector.php
  assert_file_not_exists renovate.json

  popd >/dev/null || exit 1

  popd >/dev/null || exit 1
}

assert_files_present_extension_type_module() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  # Assert that extension info file were processed correctly.
  assert_file_contains "force_crystal.info.yml" 'type: module'
  assert_file_not_contains "force_crystal.info.yml" 'type: theme'
  assert_file_not_contains "force_crystal.info.yml" 'base theme: false'

  # Assert that composer.json file was processed correctly.
  assert_file_contains "composer.json" '"type": "drupal-module"'

  # Assert some dirs/files must exist.
  assert_dir_exists "tests/src/Unit"
  assert_dir_exists "tests/src/Functional"

  popd >/dev/null || exit 1
}

assert_files_present_extension_type_theme() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  # Assert that extension info file were processed correctly.
  assert_file_contains "force_crystal.info.yml" 'type: theme'
  assert_file_contains "force_crystal.info.yml" 'base theme: false'
  assert_file_not_contains "force_crystal.info.yml" 'type: module'

  # Assert that composer.json file were processed correctly.
  assert_file_contains "composer.json" '"type": "drupal-theme"'

  # Assert some dirs/files must not exist.
  assert_dir_not_exists "tests/src/Unit"
  assert_dir_not_exists "tests/src/Functional"

  popd >/dev/null || exit 1
}

assert_ci_provider_circleci() {
  local dir="${1:-$(pwd)}"
  pushd "${dir}" >/dev/null || exit 1

  assert_file_exists ".circleci/config.yml"

  popd >/dev/null || exit 1
}

assert_ci_provider_gha() {
  local dir="${1:-$(pwd)}"
  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_exists ".circleci/config.yml"

  popd >/dev/null || exit 1
}

assert_command_wrapper_ahoy() {
  local dir="${1:-$(pwd)}"
  pushd "${dir}" >/dev/null || exit 1

  assert_file_exists ".ahoy.yml"
  assert_file_not_exists "Makefile"

  popd >/dev/null || exit 1
}

assert_command_wrapper_makefile() {
  local dir="${1:-$(pwd)}"
  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_exists ".ahoy.yml"
  assert_file_exists "Makefile"

  popd >/dev/null || exit 1
}

assert_command_wrapper_none() {
  local dir="${1:-$(pwd)}"
  pushd "${dir}" >/dev/null || exit 1

  assert_file_not_exists ".ahoy.yml"
  assert_file_not_exists "Makefile"

  popd >/dev/null || exit 1
}

assert_workflow_run() {
  local dir="${1:-$(pwd)}"

  pushd "${dir}" >/dev/null || exit 1

  ./.devtools/assemble.sh
  ./.devtools/start.sh
  ./.devtools/provision.sh

  pushd "build" >/dev/null || exit 1

  vendor/bin/phpcs
  vendor/bin/phpstan
  vendor/bin/rector --clear-cache --dry-run
  vendor/bin/phpmd . text phpmd.xml
  vendor/bin/twig-cs-fixer

  vendor/bin/phpunit

  popd >/dev/null || exit 1

  popd >/dev/null || exit 1
}

assert_test_coverage() {
  local dir="${1:-$(pwd)}"
  pushd "${dir}" >/dev/null || exit 1

  assert_file_exists ".logs/coverage/phpunit/cobertura.xml"
  assert_file_not_contains ".logs/coverage/phpunit/cobertura.xml" 'coverage line-rate="0"'

  assert_file_exists ".logs/coverage/phpunit/.coverage-html/index.html"
  assert_file_contains ".logs/coverage/phpunit/.coverage-html/index.html" "33.33% covered"

  popd >/dev/null || exit 1
}
