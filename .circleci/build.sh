#!/usr/bin/env bash
##
# Build.
#
# shellcheck disable=SC2015,SC2094

set -e

echo "==> Validate composer"
composer validate --ansi --strict

[ -d build ] && echo "==> Remove existing build directory" && chmod -Rf 777 build && rm -rf build

echo "==> Initialise Drupal site from the latest scaffold"
php -d memory_limit=-1 "$(command -v composer)" create-project drupal-composer/drupal-project:7.x-dev build --no-interaction

echo "==> Apply custom patches"
pushd "$(pwd)/build/web" > /dev/null || exit 1
curl -s https://www.drupal.org/files/issues/2019-02-21/1713332-83.patch | patch -p1 -f
popd > /dev/null || exit 1

echo "==> Install additional dev dependencies from module's composer.json"
cat <<< "$(jq --indent 4 -M -s '.[0] * .[1]' composer.json build/composer.json)" > build/composer.json
php -d memory_limit=-1 "$(command -v composer)" --working-dir=build update --lock

echo "==> Install other dev dependencies"
php -d memory_limit=-1 "$(command -v composer)" --working-dir=build require --dev drupal/coder
php -d memory_limit=-1 "$(command -v composer)" --working-dir=build require --dev dealerdirect/phpcodesniffer-composer-installer:^0.5

echo "==> Start inbuilt PHP server in $(pwd)/build/web"
killall -9 php > /dev/null 2>&1  || true
pushd "$(pwd)/build/web" > /dev/null || exit 1
[ ! -f .ht.router.php ] && echo "==> Downloading .ht.router.php from Drupal.org " && curl -s https://git.drupalcode.org/project/drupal/raw/8.8.x/.ht.router.php > .ht.router.php
nohup php -S localhost:8000 .ht.router.php > /tmp/php.log 2>&1 &
popd > /dev/null || exit 1
sleep 4 # Waiting for the server to be ready.
netstat_opts='-tulpn'; [ "$(uname)" == "Darwin" ] && netstat_opts='-anv' || true;
netstat "${netstat_opts[@]}" | grep -q 8000 || (echo "ERROR: Unable to start inbuilt PHP server" && cat /tmp/php.log && exit 1)
curl -s -o /dev/null -w "%{http_code}" -L -I http://localhost:8000 | grep -q 200 || (echo "ERROR: Server is started, but site cannot be served" && exit 1)

MODULE=$(basename -s .info -- ./*.info)
DB_FILE="${DB_FILE:-/tmp/site_${MODULE}.sqlite}"

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
build/vendor/bin/drush -r build/web -l http://localhost:8000 uli --no-browser

echo "==> Enable dev modules"
build/vendor/bin/drush -r build/web pm:enable simpletest -y
