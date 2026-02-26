#!/usr/bin/env bash
##
# Provision a website using existing codebase.
#
# - Installs Drupal using SQLite database.
# - Enables modules
# - Serves site and generates one-time login link
#
# shellcheck disable=SC2015,SC2094,SC2002

set -eu
[ -n "${DEBUG:-}" ] && set -x

#-------------------------------------------------------------------------------
# Variables (passed from environment; provided for reference only).
#-------------------------------------------------------------------------------

# Webserver hostname.
WEBSERVER_HOST="${WEBSERVER_HOST:-localhost}"

# Webserver port.
WEBSERVER_PORT="${WEBSERVER_PORT:-8000}"

# Drupal profile to use when installing the site.
DRUPAL_PROFILE="${DRUPAL_PROFILE:-standard}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

drush() { "build/vendor/bin/drush" -r "$(pwd)/build/web" -y "$@"; }

#-------------------------------------------------------------------------------

echo "==============================="
echo "         🚀 PROVISION          "
echo "==============================="
echo

# Extension name, taken from .task file.
extension="$(basename -s .info.yml -- ./*.info.yml)"
[ "${extension}" == "*" ] && fail "ERROR: No .info.yml file found." && exit 1
extension_type="module"
if cat "${extension}.info.yml" | grep -Fq "type: theme"; then
  extension_type="theme"
fi

# Database file path.
db_file="/tmp/site_${extension}.sqlite"

task "Installing Drupal into SQLite database ${db_file}."
db_status=$(drush status --field=db-status)
if [ "${db_status}" = "Connected" ]; then
  drush sql:drop -y || true >/dev/null
fi
drush site-install "${DRUPAL_PROFILE}" -y --db-url="sqlite://localhost/${db_file}" --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL

pass "Drupal installed."

drush status

task "Enabling extension ${extension}."
if [ "${extension_type}" = "theme" ]; then
  drush theme:enable "${extension}" -y
else
  drush pm:enable "${extension}" -y
fi

task "Clearing caches."
drush cr

task "Enabling suggested modules, if any."
drupal_suggests=$(cat composer.json | jq -r 'select(.suggest != null) | .suggest | keys[]' | sed "s/drupal\///" | cut -f1 -d":")
for drupal_suggest in $drupal_suggests; do
  drush pm:enable "${drupal_suggest}" -y
done
pass "Suggested modules enabled."

task "Pre-warming caches."
curl -s "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" >/dev/null

echo
echo "==============================="
echo "   🚀 PROVISION COMPLETE  ✅  "
echo "==============================="
echo
echo "Site URL:            http://${WEBSERVER_HOST}:${WEBSERVER_PORT}"
echo -n "One-time login link: "
drush -l "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" uli --no-browser
echo
if [ -f ".ahoy.yml" ]; then
  # shellcheck disable=SC2016
  echo 'Run `ahoy` to see available commands.'
fi
if [ -f "Makefile" ]; then
  # shellcheck disable=SC2016
  echo 'Run `make` to see available commands.'
fi
echo
