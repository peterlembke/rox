ARG PHP_VERSION=8.3

# Use the official FrankenPHP image with PHP 8.3 on Debian Bookworm
# Docs: https://frankenphp.dev/docs/docker/
FROM dunglas/frankenphp:1-php${PHP_VERSION}-bookworm

LABEL org.opencontainers.image.authors="Peter Lembke rox@charzam.com"

# Work from the shared project dir
WORKDIR /var/www

# Keep the image minimal. Do not build extensions here.
# The base image already includes common PHP extensions suitable for many apps.
# If additional extensions are required later, prefer enabling prebuilt packages
# or separate services — avoid building from source per project guidelines.

# Expose HTTP/HTTPS used by Caddy/FrankenPHP (will be mapped to 8080/8443 by compose)
EXPOSE 80 443

# Default command comes from the base image (Caddy with FrankenPHP in classic mode)