#!/usr/bin/env bash
##
# Build.
#
set -e

echo "==> Validate composer"
composer validate --ansi --strict

echo "==> Initialise Drupal site"
composer create-project drupal-composer/drupal-project:8.x-dev build --no-interaction

echo "==> Add additional dev dependencies"
composer --working-dir=build require --dev dealerdirect/phpcodesniffer-composer-installer:^0.5

echo "==> Copy module code"
MODULE=$(basename -s .info.yml -- ./*.info.yml)
mkdir -p "build/web/modules/${MODULE}"
git archive --format=tar HEAD | (cd "build/web/modules/${MODULE}" && tar -xf -)

echo "==> Start inbuilt PHP server in $(pwd)/build/web"
nohup php -S localhost:8000 -t $(pwd)/build/web > /tmp/php.log 2>&1 &
sleep 2 # Waiting for the server to be ready.
netstat -tulpn | grep -q 8000 || (echo "ERROR: Unable to start inbuilt PHP server" && cat /tmp/php.log && exit 1)
curl -s -o /dev/null -w "%{http_code}" -L -I http://localhost:8000 | grep -q 200 || (echo "ERROR: Server is started, but site cannot be served" && exit 1)
