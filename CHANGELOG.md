# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2024-12-01
[GitHub](https://github.com/peterlembke/rox/releases/tag/v1.4.0)

Changed to libretranslate-arm64 for M1 support on Mac.
Changed to MongoDb 8.0.3-noble - Increased performance and a lot of new features.

## [1.3.1] - 2024-08-21
[GitHub](https://github.com/peterlembke/rox/releases/tag/v1.3.1)

The right project.conf.dist is picked for Linux or macOS during the installation. 
Then xdebug works out of the box, and you do not need to copy the right file manually.

Added php-intl to installed php extensions.

## [1.3.0] - 2024-06-25
[GitHub](https://github.com/peterlembke/rox/releases/tag/v1.3.0)

Have updated xon to use the below changes for the best xdebug experience.
Have updated xoff for the best performance.

* rox opcache on - Uses the opcache.blacklist file to exclude files from opcache
* rox opcache full - Uses an empty opcache.blacklist file to include all files in opcache 
* rox jit on - To enable JIT in opcache.ini
* rox jit off - To disable JIT in opcache.ini
* Changes in main.sh - cleanup since v1.2.0

## [1.2.0] - 2024-06-25
[GitHub](https://github.com/peterlembke/rox/releases/tag/v1.2.0)

* Updated Ubuntu 22.04 -> 24.04 (PHP 8.3)
* Added :latest to all images from docker hub
* Added support for paratest and code coverage (PHPUnit)
* Removed Varnish - Have not used it for year
* Updated PHP 8.1 to PHP 8.3
* Uses apt instead of apt-get - Because apt is enough for most cases
* Uses docker compose instead of docker-compose - requirement for new versions of Docker

## [1.1.1] - 2023-08-22
[GitHub](https://github.com/peterlembke/rox/releases/tag/v1.1.1)

* Removed more about InfoHub from ROX
* Created a dev.local certificate
* Changed all config to use dev.local

## [1.1.0] - 2023-08-20
[GitHub](https://github.com/peterlembke/rox/releases/tag/v1.1.0)

* Removed support for Magento 2.x because Adobe killed it. 
* Removed Swoole, Vagrant, GrayLog, Elasticsearch, RabbitMQ, roxy, docker sync
* Cleaned up the documentation
* Removed config for the deprecated xdebug 2.x
* Removed the workspace image. Not needed
* Wrote an install document
* Cleaned up README and CHANGELOG

## [1.0.5] - 2022-05-03

* Folder share instead of docker-sync on macOS. Reason is that Docker Desktop is now much faster with shared folders.

## [1.0.4] - 2022-04-05

* Same as v1.0.3 but with these changes
  * Ubuntu 21.10 -> 20.04
  * PHP 8.0 -> PHP 7.4
  * php-mongodb v1.11.1 -> v1.12.1

## [1.0.3] - 2022-04-05

* PHPUnit can now be run from PHP Storm

## [1.0.2] - 2022-03-17

* Docker image: ubuntu:21.10
* PHP Version: 8.0
* Branch main now support Intel and M1
* You can switch between .env files
* Updated the README to also include Laravel

## [1.0.1] - 2021-11-12

Updated the README.md and the documentation/

## [1.0.0] - 2021-11-12

Docker image: debian:buster-slim
PHP version 7.3