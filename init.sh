#!/usr/bin/env bash
##
# Adjust project repository based on user input.
#
# @usage:
# Interactive prompt:
# ./init.sh
#
# Silent:
# ./init.sh yournamespace "Extension Name" extension_machine_name extension_type "CI Provider"
#
# shellcheck disable=SC2162,SC2015

set -euo pipefail
[ "${SCRIPT_DEBUG-}" = "1" ] && set -x

namespace=${1-}
extension_name=${2-}
extension_machine_name=${3-}
extension_type=${4-}
ci_provider=${5-}

#-------------------------------------------------------------------------------

convert_string() {
  input_string="$1"
  conversion_type="$2"

  case "${conversion_type}" in
    "file_name" | "route_path" | "deployment_id")
      echo "${input_string}" | tr ' ' '_' | tr '[:upper:]' '[:lower:]'
      ;;
    "domain_name" | "package_namespace")
      echo "${input_string}" | tr ' ' '_' | tr '[:upper:]' '[:lower:]' | tr -d '-'
      ;;
    "namespace" | "class_name")
      echo "${input_string}" | awk -F" " '{for(i=1;i<=NF;i++) $i=toupper(substr($i,1,1)) tolower(substr($i,2));} 1' | tr -d ' -'
      ;;
    "package_name")
      echo "${input_string}" | tr ' ' '-' | tr '[:upper:]' '[:lower:]'
      ;;
    "function_name" | "ui_id" | "cli_command")
      echo "${input_string}" | tr ' ' '_' | tr '[:upper:]' '[:lower:]'
      ;;
    "log_entry" | "code_comment_title")
      echo "${input_string}"
      ;;
    *)
      echo "Invalid conversion type"
      ;;
  esac
}

replace_string_content() {
  local needle="${1}"
  local replacement="${2}"
  local sed_opts
  sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')
  set +e
  grep -rI --exclude-dir=".git" --exclude-dir=".idea" --exclude-dir="vendor" --exclude-dir="node_modules" -l "${needle}" "$(pwd)" | xargs sed "${sed_opts[@]}" "s!$needle!$replacement!g" || true
  set -e
}

to_lowercase() {
  echo "${1}" | tr '[:upper:]' '[:lower:]'
}

remove_string_content() {
  local token="${1}"
  local sed_opts
  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  grep -rI --exclude-dir=".git" --exclude-dir=".idea" --exclude-dir="vendor" --exclude-dir="node_modules" -l "${token}" "$(pwd)" | LC_ALL=C.UTF-8 xargs sed "${sed_opts[@]}" -e "/^${token}/d" || true
}

remove_string_content_line() {
  local token="${1}"
  local target="${2:-.}"
  local sed_opts
  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  grep -rI --exclude-dir=".git" --exclude-dir=".idea" --exclude-dir="vendor" --exclude-dir="node_modules" -l "${token}" "$(pwd)/${target}" | LC_ALL=C.UTF-8 xargs sed "${sed_opts[@]}" -e "/${token}/d" || true
}

remove_tokens_with_content() {
  local token="${1}"
  local sed_opts
  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  grep -rI --include=".*" --include="*" --exclude-dir=".git" --exclude-dir=".idea" --exclude-dir="vendor" --exclude-dir="node_modules" -l "#;> $token" "$(pwd)" | LC_ALL=C.UTF-8 xargs sed "${sed_opts[@]}" -e "/#;< $token/,/#;> $token/d" || true
}

uncomment_line() {
  local file_name="${1}"
  local start_string="${2}"
  local sed_opts
  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  LC_ALL=C.UTF-8 sed "${sed_opts[@]}" -e "s/^# ${start_string}/${start_string}/" "${file_name}"
}

remove_special_comments() {
  local token="#;"
  local sed_opts
  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  grep -rI --exclude-dir=".git" --exclude-dir=".idea" --exclude-dir="vendor" --exclude-dir="node_modules" -l "${token}" "$(pwd)" | LC_ALL=C.UTF-8 xargs sed "${sed_opts[@]}" -e "/${token}/d" || true
}

ask() {
  local prompt="$1"
  local default="${2-}"
  local result=""

  if [[ -n $default ]]; then
    prompt="${prompt} [${default}]: "
  else
    prompt="${prompt}: "
  fi

  while [[ -z ${result} ]]; do
    read -p "${prompt}" result
    if [[ -n $default && -z ${result} ]]; then
      result="${default}"
    fi
  done
  echo "${result}"
}

ask_yesno() {
  local prompt="${1}"
  local default="${2:-Y}"
  local result

  read -p "${prompt} [$([ "${default}" = "Y" ] && echo "Y/n" || echo "y/N")]: " result
  result="$(echo "${result:-${default}}" | tr '[:upper:]' '[:lower:]')"
  echo "${result}"
}

#-------------------------------------------------------------------------------

remove_ci_provider_github_actions() {
  # Remove only workflows dir.
  rm -rf .github/workflows >/dev/null 2>&1 || true
}

remove_ci_provider_circleci() {
  rm -rf .circleci >/dev/null 2>&1 || true
}

process_readme() {
  mv README.dist.md "README.md" >/dev/null 2>&1 || true

  curl "https://placehold.jp/000000/ffffff/200x200.png?text=${1// /+}&css=%7B%22border-radius%22%3A%22%20100px%22%7D" >logo.tmp.png || true
  if [ -s "logo.tmp.png" ]; then
    mv logo.tmp.png "logo.png" >/dev/null 2>&1 || true
  fi
  rm logo.tmp.png >/dev/null 2>&1 || true
}

process_internal() {
  local namespace="${1}"
  local extension_name="${2}"
  local extension_machine_name="${3}"
  local extension_type="${4}"
  local ci_provider="${5}"
  local namespace_lowercase

  namespace_lowercase="$(to_lowercase "${namespace}")"

  replace_string_content "YourNamespace" "${namespace}"
  replace_string_content "yournamespace" "${namespace_lowercase}"
  replace_string_content "AlexSkrypnyk" "${namespace}"
  replace_string_content "alexskrypnyk" "${namespace_lowercase}"
  replace_string_content "yourproject" "${extension_machine_name}"
  replace_string_content "Yourproject logo" "${extension_name} logo"
  replace_string_content "Your extension" "${extension_name}"
  replace_string_content "your extension" "${extension_name}"
  replace_string_content "Your+Extension" "${extension_machine_name}"
  replace_string_content "your_extension" "${extension_machine_name}"
  replace_string_content "Provides your_extension functionality." "Provides ${extension_machine_name} functionality."
  replace_string_content "drupal-module" "drupal-${extension_type}"
  replace_string_content "Drupal module scaffold FE example used for template testing" "Provides ${extension_machine_name} functionality."
  replace_string_content "drupal_extension_scaffold" "${extension_machine_name}"
  replace_string_content "Drupal extension scaffold" "${extension_name}"
  replace_string_content "type: module" "type: ${extension_type}"

  remove_string_content "# Uncomment the lines below in your project."
  uncomment_line ".gitattributes" ".ahoy.yml"
  uncomment_line ".gitattributes" ".circleci"
  uncomment_line ".gitattributes" ".devtools"
  uncomment_line ".gitattributes" ".editorconfig"
  uncomment_line ".gitattributes" ".gitattributes"
  uncomment_line ".gitattributes" ".github"
  uncomment_line ".gitattributes" ".gitignore"
  uncomment_line ".gitattributes" ".twig_cs.php"
  uncomment_line ".gitattributes" "composer.dev.json"
  uncomment_line ".gitattributes" "phpcs.xml"
  uncomment_line ".gitattributes" "phpmd.xml"
  uncomment_line ".gitattributes" "phpstan.neon"
  uncomment_line ".gitattributes" "rector.php"
  uncomment_line ".gitattributes" "renovate.json"
  uncomment_line ".gitattributes" "tests"
  remove_string_content "# Remove the lines below in your project."
  remove_string_content ".github\/FUNDING.yml export-ignore"
  remove_string_content "LICENSE             export-ignore"

  mv "your_extension.info.yml" "${extension_machine_name}.info.yml"

  rm -f LICENSE >/dev/null || true
  rm -Rf "tests/scaffold" >/dev/null || true
  rm -f ".github/workflows/test-scaffold.yml" >/dev/null || true
  rm -Rf .scaffold >/dev/null || true

  remove_tokens_with_content "META"
  remove_special_comments

  if [ "${extension_type}" = "theme" ]; then
    rm -rf tests >/dev/null || true
    echo 'base theme: false' >> "${extension_machine_name}.info.yml"
  fi

  if [ "${ci_provider}" = "GitHub Actions" ]; then
    remove_ci_provider_circleci
  else
    remove_ci_provider_github_actions
  fi
}

#-------------------------------------------------------------------------------

main() {
  echo "Please follow the prompts to adjust your extension configuration"
  echo

  [ -z "${namespace}" ] && namespace="$(ask "namespace")"
  [ -z "${extension_name}" ] && extension_name="$(ask "name")"
  [ -z "${extension_machine_name}" ] && extension_machine_name="$(ask "machine_name")"
  [ -z "${extension_type}" ] && extension_type="$(ask "type: module or theme")"
  [ -z "${ci_provider}" ] && ci_provider="$(ask "ci_provider: GitHub Actions or CircleCI")"

  remove_self="$(ask_yesno "Remove this script")"

  echo
  echo "            Summary"
  echo "---------------------------------"
  echo "namespace                        : ${namespace}"
  echo "name                             : ${extension_name}"
  echo "machine_name                     : ${extension_machine_name}"
  echo "type: module or theme            : ${extension_type}"
  echo "ci_provider                      : ${ci_provider}"
  echo "Remove this script               : ${remove_self}"
  echo "---------------------------------"
  echo

  should_proceed="$(ask_yesno "Proceed with project init")"

  if [ "${should_proceed}" != "y" ]; then
    echo
    echo "Aborting."
    exit 1
  fi

  #
  # Processing.
  #

  : "${namespace:?namespace is required}"
  : "${extension_name:?name is required}"
  : "${extension_machine_name:?machine_name is required}"
  : "${extension_type:?type is required}"
  : "${ci_provider:?ci_provider is required}"

  process_readme "${extension_name}"

  process_internal "${namespace}" "${extension_name}" "${extension_machine_name}" "${extension_type}" "${ci_provider}"

  [ "${remove_self}" != "n" ] && rm -- "$0" || true

  echo
  echo "Initialization complete."
}

if [ "$0" = "${BASH_SOURCE[0]}" ]; then
  main "$@"
fi
