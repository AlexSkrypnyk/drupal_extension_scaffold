#!/usr/bin/env bash
##
# Run lint checks.
#

set -eu
[ -n "${DEBUG:-}" ] && set -x

# Module name, taken from .info file.
module="$(basename -s .info.yml -- ./*.info.yml)"

pushd "build" >/dev/null || exit 1

echo "==> Lint code for module $module."
echo "  > Running PHPCS, PHPMD, TWIGCS"
vendor/bin/phpcs

echo "  > Running PHPMD"
vendor/bin/phpmd --exclude vendor . text phpmd.xml

echo "  > Running TWIGCS"
vendor/bin/twigcs

echo "  > Running phpstan."
vendor/bin/phpstan

echo "  > Running Drupal Rector."
vendor/bin/rector --dry-run

popd >/dev/null || exit 1
