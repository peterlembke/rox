#!/usr/bin/env bash

set -e

# Create a user if it doesn't exist
id --user "$HOST_UID" > /dev/null 2>&1 || {
  getent group "$HOST_GID" > /dev/null 2>&1 || {
    groupadd --gid "$HOST_GID" "$FPM_GROUP"
  }
  useradd --uid "$HOST_UID" \
    --gid "$HOST_GID" \
    --shell /bin/bash \
    --create-home \
    "$FPM_USER"
}

FPM_USER="$(id --name --user "$HOST_UID")"
FPM_GROUP="$(getent group "$HOST_GID" | cut -d: -f1)"

sudo -u "$FPM_USER" composer config --global \
  github-oauth.github.com "$GITHUB_TOKEN"

PHP_DIR="/etc/php/${PHP_VERSION}"
sed -i 's/${PHP_VERSION}/'"${PHP_VERSION}"'/' "${PHP_DIR}/fpm/php-fpm.conf"
sed -i 's/${FPM_USER}/'"${FPM_USER}"'/' "${PHP_DIR}/fpm/pool.d/www.conf"
sed -i 's/${FPM_GROUP}/'"${FPM_GROUP}"'/' "${PHP_DIR}/fpm/pool.d/www.conf"

"php-fpm${PHP_VERSION}" --fpm-config "${PHP_DIR}/fpm/php-fpm.conf"
