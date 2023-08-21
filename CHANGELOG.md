# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
