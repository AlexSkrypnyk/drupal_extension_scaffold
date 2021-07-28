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

# Drupal profile to use when installing site.
DRUPAL_PROFILE="${DRUPAL_PROFILE:-standard}"

# Module name, taken from .info file.
MODULE="$(basename -s .info -- ./*.info)"

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

echo "==> Initialise Drupal site from the latest scaffold"
php -d memory_limit=-1 "$(command -v composer)" create-project drupal-composer/drupal-project:7.x-dev build --no-interaction

echo "==> Install additional dev dependencies from module's composer.json"
cat <<< "$(jq --indent 4 -M -s '.[0] * .[1]' composer.json build/composer.json)" > build/composer.json
php -d memory_limit=-1 "$(command -v composer)" --working-dir=build update --lock

echo "==> Install other dev dependencies"
php -d memory_limit=-1 "$(command -v composer)" --working-dir=build require --dev drupal/coder
cat <<< "$(jq --indent 4 '.extra["phpcodesniffer-search-depth"] = 10' build/composer.json)" > build/composer.json
php -d memory_limit=-1 "$(command -v composer)" --working-dir=build require --dev dealerdirect/phpcodesniffer-composer-installer

echo "==> Start inbuilt PHP server in $(pwd)/build/web"
# Stop previously started services.
killall -9 php > /dev/null 2>&1  || true
pushd "$(pwd)/build/web" > /dev/null || exit 1
# Start the PHP webserver.
[ ! -f .ht.router.php ] && echo "==> Downloading .ht.router.php from Drupal.org " && curl -s https://git.drupalcode.org/project/drupal/raw/8.8.x/.ht.router.php > .ht.router.php
nohup php -S "${WEBSERVER_HOST}:${WEBSERVER_PORT}" .ht.router.php > /tmp/php.log 2>&1 &
popd > /dev/null || exit 1
sleep 4 # Waiting for the server to be ready.
# Check that the server was started.
netstat_opts='-tulpn'; [ "$(uname)" == "Darwin" ] && netstat_opts='-anv' || true;
netstat "${netstat_opts[@]}" | grep -q 8000 || (echo "ERROR: Unable to start inbuilt PHP server" && cat /tmp/php.log && exit 1)
# Check that the server can serve content.
curl -s -o /dev/null -w "%{http_code}" -L -I "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" | grep -q 200 || (echo "ERROR: Server is started, but site cannot be served" && exit 1)

echo "==> Install Drupal into SQLite database ${DB_FILE}"
build/vendor/bin/drush -r build/web si "${DRUPAL_PROFILE:-standard}" -y --db-url="sqlite://${DB_FILE}" --account-name=admin install_configure_form.update_status_module='array(FALSE,FALSE)'
build/vendor/bin/drush -r "$(pwd)/build/web" status

echo "==> Symlink module code"
rm -rf build/web/sites/all/modules/"${MODULE}"/* > /dev/null
mkdir -p "build/web/sites/all/modules/${MODULE}"
ln -s "$(pwd)"/* build/web/sites/all/modules/"${MODULE}" && rm build/web/sites/all/modules/"${MODULE}"/build

echo "==> Enable module ${MODULE}"
build/vendor/bin/drush -r build/web pm:enable "${MODULE}" -y
build/vendor/bin/drush -r build/web cc all
build/vendor/bin/drush -r build/web -l "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" uli --no-browser

echo "==> Enable dev modules"
build/vendor/bin/drush -r build/web pm:enable simpletest -y

# Visit site to pre-warm caches.
curl -s "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" > /dev/null

echo -n "==> One-time login link: "
"${BUILD_DIR}/vendor/bin/drush" -r "${BUILD_DIR}/web" -l "http://${WEBSERVER_HOST}:${WEBSERVER_PORT}" uli --no-browser

echo
echo "==> Build finished. The site is available at http://${WEBSERVER_HOST}:${WEBSERVER_PORT}."
echo
