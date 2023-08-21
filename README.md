# Docker Development Environment

ROX is a development environment focused on PHP and the web.
Works on Linux and macOS.

## Install ROX

[Installation](documentation/install.md)

## Benefits with docker

ROX sets up Docker boxes where it runs the software. The software is installed in the boxes.
A Docker development environment give you some advantages compared to installing all software locally on the computer

Docker can run any version of Linux on your Mac/Linux/Windows computer.
You can have Ubuntu 23.4 or macOS 13.5 or Windows on your computer and run for example [Ubuntu 22.04 LTS](https://en.wikipedia.org/wiki/Ubuntu_version_history) in Docker.

Avoids problems when developing on Mac for Linux servers. 
[Mac file system is case-iNSensItiVe](https://discussions.apple.com/thread/251191099) - While Linux is always case-sensitive

You can have several projects with different server configuration.  
One project might need PHP 5.6, one might need MongoDb and so on.

A drawback is that only run one project can run simultaneously. You need to stop one project and start another.
The reason is that the boxes all run on your localhost and two projects that both have a web server at port 80 will collide.
If you make sure the ports do not collide then you can have many projects started at the same time.

## Benefits with rox

First the drawback. ROX is not tested on Windows.

ROX help you with the docker boxes. You need to write fewer and simpler [commands](documentation/commands.md).

You can easily go into the docker boxes and run Linux commands there.  
Quick load/unload of xdebug, opcache or any other Apache2 module.

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