# AI prompts

Goal: Echo the full path to the main file
Details:
When I run "rox up" it runs the rox/rox script. That script can be in any folder in my PATH so I can run the same command from everywhere.
The rox script then look for a rox/main.sh. I want to echo the full path to that main.sh from within main.sh

Same thing with rox/rox.php, it can be in my PATH and can be in any folder. It finds the main.php and run it. I want rox/main.php to echo the full path to itself.

Background:
I have issues with HOST_UID and HOST_GID. When I run "rox up" I get HOST_UID=1000 and HOST_GID=1000 despite they are set to 1100 both in main.sh and in main.php. I suspect that a different main.sh is run so I need to know the full path.
They are also set correctly in the rox/images/app/Dockerfile and rox/images/web/Dockerfile.

## Add FrankenPHP

Goal:
Add FrankenPHP as a docker container to rox/docker-compose.yml
If different settings are needed between Linux-hosts and MacOS-hosts then you can add FrankenPHP to these files:
rox/docker-compose.linux.yml
rox/docker-compose.mac.yml

FrankenPHP docker documentation:
https://frankenphp.dev/docs/docker/

Own folder:
FrankenPHP needs its own Dockerfile, so create folder rox/images/frankenphp/ and put the files there.
Only add the PHP extensions from rox/images/app/Dockerfile that are easy to install.
Do not build extensions.

Ports:
I want to continue running Apache2, see rox/images/web/Dockerfile on port 80 and 443.
FrankenPHP need its own ports.

Shared volume:
I want to also run FrankenPHP and run the same source code as app and web containers run.

    volumes:
    - ..:/var/www

Mode:
FrankenPHP can be run in classic mode.

Xdebug:
No need for xdebug at this time.

PHP version:
PHP version 8.3
If possible, then let rox/default.conf decide with the PHP_VERSION