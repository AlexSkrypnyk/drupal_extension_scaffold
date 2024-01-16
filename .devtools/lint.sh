#!/usr/bin/env bash
##
# Run lint checks.
#

set -eu
[ -n "${DEBUG:-}" ] && set -x

pushd "build" >/dev/null || exit 1

echo "> Run PHPCS, PHPMD, TWIGCS"
vendor/bin/phpcs

echo "> Run PHPMD"
vendor/bin/phpmd --exclude vendor . text phpmd.xml

echo "> Run TWIGCS"
vendor/bin/twigcs

echo "> Run PHPStan"
vendor/bin/phpstan

echo "> Run Rector"
vendor/bin/rector --dry-run

popd >/dev/null || exit 1
