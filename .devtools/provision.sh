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

echo "-------------------------------"
echo " Installing Drupal and modules "
echo "-------------------------------"

drush() { "build/vendor/bin/drush" -r "$(pwd)/build/web" -y "$@"; }

# Module name, taken from .info file.
module="$(basename -s .info.yml -- ./*.info.yml)"
[ "${module}" == "*" ] && echo "ERROR: No .info.yml file found." && exit 1

# Database file path.
db_file="/tmp/site_${module}.sqlite"

echo "> Installing Drupal into SQLite database ${db_file}."
drush si "${DRUPAL_PROFILE}" -y --db-url "sqlite://${db_file}" --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL
drush status

echo "> Enabling module ${module}."
drush pm:enable "${module}" -y
drush cr

echo "> Enabling suggested modules, if any."
drupal_suggests=$(cat composer.json | jq -r 'select(.suggest != null) | .suggest | keys[]' | sed "s/drupal\///" | cut -f1 -d":")
for drupal_suggest in $drupal_suggests; do
  drush pm:enable "${drupal_suggest}" -y
done

# Visit site to pre-warm caches.
curl -s "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" > /dev/null

echo
echo "-------------------------------"
echo "    Install finished 🚀🚀🚀   "
echo "-------------------------------"
echo
echo "Site URL:            http://${WEBSERVER_HOST}:${WEBSERVER_PORT}"
echo -n "One-time login link: "
drush -l "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" uli --no-browser
echo
echo "> Available commands:"
echo "  ahoy build  # Rebuild"
echo "  ahoy lint   # Check coding standards"
echo "  ahoy test   # Run tests"
echo
