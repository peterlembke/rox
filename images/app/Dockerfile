FROM ubuntu:24.04

MAINTAINER Peter Lembke <rox@charzam.com>

ARG HOST_UID=1000
ARG HOST_GID=1000
ARG PHP_VERSION=8.3
ARG DEBIAN_FRONTEND=noninteractive

ENV HOST_UID $HOST_UID
ENV HOST_GID $HOST_GID
ENV FPM_USER dockerhost
ENV FPM_GROUP dockerhost
ENV PHP_VERSION $PHP_VERSION
ENV GITHUB_TOKEN ghp_something

ENV TZ=Europe/Stockholm
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt update && apt install -y \
  apt-utils \
  apt-transport-https \
  cron \
  exim4 \
  git \
  gnupg \
  nano \
  sudo \
  wget \
  unzip \
  iputils-ping

RUN apt update && apt install -y \
  php${PHP_VERSION}-apcu \
  php${PHP_VERSION}-cli \
  php${PHP_VERSION}-curl \
  php${PHP_VERSION}-dev \
  php${PHP_VERSION}-fpm \
  php${PHP_VERSION}-mbstring \
  php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-opcache \
  php${PHP_VERSION}-redis \
  php${PHP_VERSION}-sqlite3 \
  php${PHP_VERSION}-xdebug

# Install composer
RUN wget -qO- https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer

COPY fpm.conf "/etc/php/${PHP_VERSION}/fpm/php-fpm.conf"
COPY www.conf "/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
COPY xdebug.ini "/etc/php/${PHP_VERSION}/mods-available/xdebug.ini"
COPY opcache-on.ini "/etc/php/${PHP_VERSION}/mods-available/opcache.ini"
COPY opcache-on.ini "/etc/php/${PHP_VERSION}/mods-available/opcache-on.ini"
COPY opcache-jit.ini "/etc/php/${PHP_VERSION}/mods-available/opcache-jit.ini"
COPY opcache-on.blacklist /etc/php/opcache.blacklist
COPY opcache-on.blacklist /etc/php/opcache-on.blacklist
COPY opcache-full.blacklist /etc/php/opcache-full.blacklist
COPY common.ini /etc/php/common.ini
RUN ln -s /etc/php/common.ini "/etc/php/${PHP_VERSION}/cli/conf.d/99-common.ini"
RUN ln -s /etc/php/common.ini "/etc/php/${PHP_VERSION}/fpm/conf.d/99-common.ini"
RUN mkdir -p /run/php && touch "/run/php/php${PHP_VERSION}-fpm.sock"

# PECL
RUN mkdir -p /tmp/pear/cache
RUN pecl channel-update pecl.php.net
RUN apt install -y libgpgme11-dev
RUN apt install -y php-pear

# MongoDb
COPY mongodb.ini "/etc/php/${PHP_VERSION}/mods-available/mongodb.ini"
RUN pecl install mongodb

# GnuPG https://www.gnupg.org/
COPY gnupg.ini "/etc/php/${PHP_VERSION}/mods-available/gnupg.ini"
RUN pecl install gnupg

# xDebug
# The xdebug distributed with Ubuntu 20.04 LTS is v2.9.2, we want v3.0.x
# Make sure the client firewall accept incoming tcp on port 9000 and 9003
# RUN pecl install xdebug

# Enable xdebug by default
# RUN phpenmod xdebug

# Set exim config for mailcatcher
RUN rm -f /etc/exim4/update-exim4.conf.conf
COPY exim4.debconf /tmp/
RUN debconf-set-selections /tmp/exim4.debconf
RUN dpkg-reconfigure -f noninteractive exim4-config

# Clean up
RUN apt clean && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
EXPOSE 9000
EXPOSE 9003
EXPOSE 35729
