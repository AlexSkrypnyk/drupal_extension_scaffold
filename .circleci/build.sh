#!/usr/bin/env bash
##
# Build.
#
# shellcheck disable=SC2015,SC2094

set -e

echo "==> Validate composer"
composer validate --ansi --strict

[ -d build ] && echo "==> Remove existing build directory" && chmod -Rf 777 build && rm -rf build

# Allow installing custom version of Drupal core, but only coupled with
# drupal-project SHA (required to get correct dependencies).
if [ -n "${DRUPAL_PROJECT_SHA}" ] && [ -n "${DRUPAL_VERSION}" ] ; then
  echo "==> Initialise Drupal site from the scaffold commit $DRUPAL_PROJECT_SHA"

  git clone -n https://github.com/drupal-composer/drupal-project.git build
  git --git-dir=build/.git --work-tree=build checkout "${DRUPAL_PROJECT_SHA}"
  rm -rf build/.git > /dev/null

  echo "==> Pin Drupal to a specific version"
  sed_opts=(-i) && [ "$(uname)" == "Darwin" ] && sed_opts=(-i '')
  sed "${sed_opts[@]}" 's|\(.*"drupal\/core"\): "\(.*\)",.*|\1: '"\"$DRUPAL_VERSION\",|" build/composer.json
  cat build/composer.json

  echo "==> Install dependencies"
  php -d memory_limit=-1 "$(command -v composer)" --working-dir=build install
else
  echo "==> Initialise Drupal site from the latest scaffold"
  php -d memory_limit=-1 "$(command -v composer)" create-project drupal-composer/drupal-project:8.x-dev build --no-interaction
fi

echo "==> Install additional dev dependencies from module's composer.json"
cat <<< "$(jq --indent 4 -M -s '.[0] * .[1]' composer.json build/composer.json)" > build/composer.json
php -d memory_limit=-1 "$(command -v composer)" --working-dir=build update --lock

echo "==> Install other dev dependencies"
cat <<< "$(jq --indent 4 '.extra["phpcodesniffer-search-depth"] = 10' build/composer.json)" > build/composer.json
php -d memory_limit=-1 "$(command -v composer)" --working-dir=build require --dev dealerdirect/phpcodesniffer-composer-installer

echo "==> Start inbuilt PHP server in $(pwd)/build/web"
killall -9 php > /dev/null 2>&1  || true
nohup php -S localhost:8000 -t "$(pwd)/build/web" "$(pwd)/build/web/.ht.router.php" > /tmp/php.log 2>&1 &
sleep 4 # Waiting for the server to be ready.
netstat_opts='-tulpn'; [ "$(uname)" == "Darwin" ] && netstat_opts='-anv' || true;
netstat "${netstat_opts[@]}" | grep -q 8000 || (echo "ERROR: Unable to start inbuilt PHP server" && cat /tmp/php.log && exit 1)
curl -s -o /dev/null -w "%{http_code}" -L -I http://localhost:8000 | grep -q 200 || (echo "ERROR: Server is started, but site cannot be served" && exit 1)

MODULE=$(basename -s .info.yml -- ./*.info.yml)
DB_FILE="${DB_FILE:-/tmp/site_${MODULE}.sqlite}"

echo "==> Install Drupal into SQLite database ${DB_FILE}"
build/vendor/bin/drush -r build/web si "${DRUPAL_PROFILE:-standard}" -y --db-url "sqlite://${DB_FILE}" --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL
build/vendor/bin/drush -r "$(pwd)/build/web" status

echo "==> Symlink module code"
rm -rf build/web/modules/"${MODULE}"/* > /dev/null
mkdir -p "build/web/modules/${MODULE}"
ln -s "$(pwd)"/* build/web/modules/"${MODULE}" && rm build/web/modules/"${MODULE}"/build

echo "==> Enable module ${MODULE}"
build/vendor/bin/drush -r build/web pm:enable "${MODULE}" -y
build/vendor/bin/drush -r build/web cr
build/vendor/bin/drush -r build/web -l http://localhost:8000 uli --no-browser
