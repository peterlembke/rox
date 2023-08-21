#!/usr/bin/env bash

set -e

# Create a user if it doesn't exist
id --user "$HOST_UID" > /dev/null 2>&1 || {
  getent group "$HOST_GID" > /dev/null 2>&1 || {
    groupadd --gid "$HOST_GID" "$APACHE_GROUP"
  }
  useradd --uid "$HOST_UID" \
    --gid "$HOST_GID" \
    --shell /bin/bash \
    --create-home \
    "$APACHE_USER"
}

APACHE_USER="$(id --name --user "$HOST_UID")"
APACHE_GROUP="$(getent group "$HOST_GID" | cut -d: -f1)"

sed -i 's/APACHE_RUN_USER=www-data/APACHE_RUN_USER='"$APACHE_USER"'/g' /etc/apache2/envvars
sed -i 's/APACHE_RUN_GROUP=www-data/APACHE_RUN_GROUP='"$APACHE_GROUP"'/g' /etc/apache2/envvars

apachectl -d /etc/apache2 -f apache2.conf -e info -DFOREGROUND
