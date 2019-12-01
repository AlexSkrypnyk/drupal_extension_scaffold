#!/usr/bin/env bash
##
# Build.
#
set -e

echo "==> Validate composer"
composer validate --ansi --strict

[ -d build ] && echo "==> Remove existing build directory" && chmod -Rf 777 build && rm -rf build

echo "==> Initialise Drupal site"
composer create-project drupal-composer/drupal-project:8.x-dev build --no-interaction

echo "==> Add additional dev dependencies"
composer --working-dir=build require --dev dealerdirect/phpcodesniffer-composer-installer:^0.5

echo "==> Start inbuilt PHP server in $(pwd)/build/web"
killall -9 php > /dev/null 2>&1  || true
nohup php -S localhost:8000 -t "$(pwd)/build/web" "$(pwd)/build/web/.ht.router.php" > /tmp/php.log 2>&1 &
sleep 4 # Waiting for the server to be ready.
netstat_opts='-tulpn'; [ "$(uname)" == "Darwin" ] && netstat_opts='-anv' || true;
netstat "${netstat_opts[@]}" | grep -q 8000 || (echo "ERROR: Unable to start inbuilt PHP server" && cat /tmp/php.log && exit 1)
curl -s -o /dev/null -w "%{http_code}" -L -I http://localhost:8000 | grep -q 200 || (echo "ERROR: Server is started, but site cannot be served" && exit 1)

echo "==> Install Drupal"
build/vendor/bin/drush -r build/web si "${DRUPAL_PROFILE:-standard}" -y --db-url sqlite:///tmp/site.sqlite --account-name=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL

MODULE=$(basename -s .info.yml -- ./*.info.yml)

echo "==> Symlink module code"
rm -rf build/web/modules/"${MODULE}"/* > /dev/null
mkdir -p "build/web/modules/${MODULE}"
ln -s "$(pwd)"/* build/web/modules/"${MODULE}" && rm build/web/modules/"${MODULE}"/build

echo "==> Enable module"
build/vendor/bin/drush -r build/web pm:enable "${MODULE}" -y
build/vendor/bin/drush -r build/web cr
build/vendor/bin/drush -r build/web -l http://localhost:8000 uli --no-browser
