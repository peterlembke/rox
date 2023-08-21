# Commands list

## Install component
* rox install <component>

Install component (Ex. 'install magento').
  
## Configure component
* rox configure <component>

Configure component (Ex. 'configure magento')

## Laravel commands

* rox laravel

Run Laravel Artisan CLI command in app container

## Composer commands
* rox composer
                  
Run composer command in app container

## Shell
You can get into the container and run commands there:

* rox shell <container> <user>

Example: `rox shell infohub-app root`
  
## Database
You can run MySQL / MariaDb commands.

Open MySQL CLI

    rox db

Output MySQL dump

    rox db dump

Run mysqladmin command

    rox db admin
    
Drop and re-create empty database
    
    db nuke
    
## Cache
    
  cache                     Open Redis CLI
    cache fpc               Open Page Cache Redis CLI
    cache session           Open Session Redis CLI
  httpd                     Run apachectl command
  xdebug {on|off}           Enable/disable xdebug
  opcache {on|off}          Enable/disable opcache
  npm                       Run npm command
  grunt                     Run Grunt command
  proxy                     Run varnishadm command
    proxy log               Open varnishlog
  purge                     Clean all cache layers for a neutral platform
    laravel                 Clean all cache layers for Laravel
  container                 Container commands
    ip {app/web/cache/db}   Get IP for a container
    url                     Set web server URL in app HOSTS file
  data-sync                 Synchronize with remote data
    data-sync db            Download and import a remote database
    data-sync media         Download remote media files
  unit <path> <options>     Run PHPUnit tests

All unrecognized command are passed on to docker-compose:
Ex. './${0##*/} up' will call 'docker-compose up'
