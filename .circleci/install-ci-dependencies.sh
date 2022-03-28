#!/usr/bin/env bash
##
# Install dependencies.

# shellcheck disable=SC2015,SC2094

set -e

sudo -E apt-get update

sudo -E apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev jq

sudo -E docker-php-ext-install -j"$(nproc)" iconv

if [ "$(php -r "echo PHP_MAJOR_VERSION;")" -gt 5 ] && [ "$(php -r "echo PHP_MINOR_VERSION;")" -gt 3 ] ; then
  sudo -E docker-php-ext-configure gd --with-freetype --with-jpeg;
elif [ "$(php -r "echo PHP_MAJOR_VERSION;")" -eq 8 ] ; then
  sudo -E docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg;
else
  sudo -E docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/;
fi

sudo -E docker-php-ext-install -j"$(nproc)" gd
