# Docker Development Environment

ROX is a development environment focused on PHP and the web.
You can run ROX on MacOS or Linux computers. Windows is not supported. (I do not have a Windows machine available)

## Install ROX

[Installation](documentation/install.md)

## What you get

ROX sets up separate Docker boxes for PHP, Apache2, MariaDb (MySQL), MongoDb, KeyDb (Redis cache), LibreTranslate.
Inside the Docker box you get [Ubuntu 24.04 LTS](https://en.wikipedia.org/wiki/Ubuntu_version_history) in your PHP and Apache2 docker boxes.

ROX help you with the docker boxes. You need to write fewer and simpler [commands](documentation/commands.md).
You can quick load/unload xdebug, opcache, JIT or any other Apache2 module.
You can easily go into the docker boxes and run Linux commands there.

## Documentation

How to get started.  
[Installation](documentation/install.md)
[Restart from the beginning](documentation/restart-from-beginning.md)  
[GitHub token](documentation/github-token.md)

How to configure.  
[Tips](documentation/tips.md)  
[ROX Commands](documentation/commands.md)
[PHP version](documentation/php-version.md)

How to set up a database.  
[Database](documentation/database.md)
[MongoDb](documentation/mongodb.md)  

How to debug PHP.  
[Debug](documentation/debug.md)  
[Xdebug on Mac](documentation/xdebug-mac.md)  

Deeper with PHP.  
[PHPDOC](documentation/phpdoc.md)  
[Analyse with PHPStan](documentation/analyse-with-phpstan.md)  
[PHP Unit](documentation/phpunit.md)  

## ROX version

[CHANGELOG](CHANGELOG.md)