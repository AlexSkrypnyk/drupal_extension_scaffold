#!/usr/bin/env bash
##
# Build Drupal site using SQLite database, install current module and serve
# using in-built PHP server.
#
# Allows to use the latest Drupal core as well as specified versions (for
# testing backward compatibility).
#
# - builds Drupal site codebase with current module and it's dependencies.
# - installs Drupal using SQLite database.
# - starts in-built PHP-server
# - enables module
# - serves site
# - generates one-time login link
#
# This script will re-build everything from scratch every time it runs.

# shellcheck disable=SC2015,SC2094

set -e

#-------------------------------------------------------------------------------
# Variables (passed from environment; provided for reference only).
#-------------------------------------------------------------------------------

# Directory where Drupal site will be built.
BUILD_DIR="${BUILD_DIR:-build}"

# Webserver hostname.
WEBSERVER_HOST="${WEBSERVER_HOST:-localhost}"

# Webserver port.
WEBSERVER_PORT="${WEBSERVER_PORT:-8000}"

# Drupal core version to use. If not provided - latest version will be used.
# Must be coupled with DRUPAL_PROJECT_SHA below.
DRUPAL_VERSION="${DRUPAL_VERSION:-}"

# Commit SHA of the drupal-project to install custom core version. If not
# provided - the latest version will be used.
# Must be coupled with DRUPAL_VERSION above.
DRUPAL_PROJECT_SHA="${DRUPAL_PROJECT_SHA:-}"

# Drupal profile to use when installing site.
DRUPAL_PROFILE="${DRUPAL_PROFILE:-standard}"

# Module name, taken from .info file.
MODULE="$(basename -s .info.yml -- ./*.info.yml)"

# Database file path.
DB_FILE="${DB_FILE:-/tmp/site_${MODULE}.sqlite}"

#-------------------------------------------------------------------------------

echo "==> Validate requirements."
! command -v git > /dev/null && echo "ERROR: Git is required for this script to run." && exit 1
! command -v php > /dev/null && echo "ERROR: PHP is required for this script to run." && exit 1
! command -v composer > /dev/null && echo "ERROR: Composer (https://getcomposer.org/) is required for this script to run." && exit 1
! command -v jq > /dev/null && echo "ERROR: jq (https://stedolan.github.io/jq/) is required for this script to run." && exit 1

echo "==> Validate Composer config."
composer validate --ansi --strict

# Reset the environment.
[ -d "${BUILD_DIR}" ] && echo "==> Remove existing ${BUILD_DIR} directory." && chmod -Rf 777 "${BUILD_DIR}" && rm -rf "${BUILD_DIR}"

# Allow installing custom version of Drupal core, but only coupled with
# drupal-project SHA (required to get correct dependencies).
if [ -n "${DRUPAL_VERSION}" ] && [ -n "${DRUPAL_PROJECT_SHA}" ]; then
  echo "==> Initialise Drupal site from the scaffold commit ${DRUPAL_PROJECT_SHA}."

  # Clone Drupal core at the specific commit SHA.
  git clone -n https://github.com/drupal-composer/drupal-project.git "${BUILD_DIR}"
  git --git-dir="${BUILD_DIR}/.git" --work-tree="${BUILD_DIR}" checkout "${DRUPAL_PROJECT_SHA}"
  rm -rf "${BUILD_DIR}/.git" > /dev/null

  echo "==> Pin Drupal to a specific version ${DRUPAL_VERSION}."
  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  sed "${sed_opts[@]}" 's|\(.*"drupal\/core"\): "\(.*\)",.*|\1: '"\"$DRUPAL_VERSION\",|" "${BUILD_DIR}/composer.json"
  cat "${BUILD_DIR}/composer.json"

  echo "==> Install dependencies."
  php -d memory_limit=-1 "$(command -v composer)" --working-dir="${BUILD_DIR}" install
else
  echo "==> Initialise Drupal site from the latest scaffold."
  php -d memory_limit=-1 "$(command -v composer)" create-project drupal-composer/drupal-project:9.x-dev "${BUILD_DIR}" --no-interaction
fi

echo "==> Install additional dev dependencies from module's composer.json."
cat <<< "$(jq --indent 4 -M -s '.[0] * .[1]' composer.json "${BUILD_DIR}/composer.json")" > "${BUILD_DIR}/composer.json"
php -d memory_limit=-1 "$(command -v composer)" --working-dir="${BUILD_DIR}" update --lock

echo "==> Install other dev dependencies."
cat <<< "$(jq --indent 4 '.extra["phpcodesniffer-search-depth"] = 10' "${BUILD_DIR}/composer.json")" > "${BUILD_DIR}/composer.json"
php -d memory_limit=-1 "$(command -v composer)" --working-dir="${BUILD_DIR}" require --dev dealerdirect/phpcodesniffer-composer-installer
php -d memory_limit=-1 "$(command -v composer)" --working-dir="${BUILD_DIR}" require --dev phpspec/prophecy-phpunit:^2

echo "==> Start inbuilt PHP server at http://${WEBSERVER_HOST}:${WEBSERVER_PORT} in $(pwd)/${BUILD_DIR}/web."
# Stop previously started services.
killall -9 php > /dev/null 2>&1 || true
# Start the PHP webserver.
nohup php -S "${WEBSERVER_HOST}:${WEBSERVER_PORT}" -t "$(pwd)/${BUILD_DIR}/web" "$(pwd)/${BUILD_DIR}/web/.ht.router.php" > /tmp/php.log 2>&1 &
sleep 4 # Waiting for the server to be ready.
netstat_opts='-tulpn'; [ "$(uname)" == "Darwin" ] && netstat_opts='-anv' || true;
# Check that the server was started.
netstat "${netstat_opts[@]}" | grep -q "${WEBSERVER_PORT}" || (echo "ERROR: Unable to start inbuilt PHP server" && cat /tmp/php.log && exit 1)
# Check that the server can serve content.
curl -s -o /dev/null -w "%{http_code}" -L -I "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" | grep -q 200 || (echo "ERROR: Server is started, but site cannot be served" && exit 1)

echo "==> Install Drupal into SQLite database ${DB_FILE}."
"${BUILD_DIR}/vendor/bin/drush" -r "${BUILD_DIR}/web" si "${DRUPAL_PROFILE}" -y --db-url "sqlite://${DB_FILE}" --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL
"${BUILD_DIR}/vendor/bin/drush" -r "$(pwd)/${BUILD_DIR}/web" status

echo "==> Symlink module code."
rm -rf "${BUILD_DIR}/web/modules/${MODULE}"/* > /dev/null
mkdir -p "${BUILD_DIR}/web/modules/${MODULE}"
ln -s "$(pwd)"/* "${BUILD_DIR}/web/modules/${MODULE}" && rm "${BUILD_DIR}/web/modules/${MODULE}/${BUILD_DIR}"

echo "==> Enable module ${MODULE}."
"${BUILD_DIR}/vendor/bin/drush" -r "${BUILD_DIR}/web" pm:enable "${MODULE}" -y
"${BUILD_DIR}/vendor/bin/drush" -r "${BUILD_DIR}/web" cr

# Visit site to pre-warm caches.
curl -s "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" > /dev/null

echo -n "==> One-time login link: "
"${BUILD_DIR}/vendor/bin/drush" -r "${BUILD_DIR}/web" -l "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" uli --no-browser

echo
echo "==> Build finished. The site is available at http://${WEBSERVER_HOST}:${WEBSERVER_PORT}."
echo
