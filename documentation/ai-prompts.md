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
No need for doing anything extra to add xdebug. I might need it later. 
But it should not interfer with the xdebug that currently works.

PHP version:
PHP version 8.3
If possible, then let rox/default.conf decide with the PHP_VERSION

## FrankenPHP - problems

I just got help from Junie AI to add the FrankenPHP continer.
I can start all docker containers. But I can then not see the labs-frankenphp container with docker ps.
When I surf to http://dev.local/ I get the phpinfo from the index.php file.
When I surf to http://dev.local:8080 it can not reach the port.
If I telnet into port 80 I get a connection. If I Telnet into port 8080 I get no connection.
Can you help me debug this situation?

```  
➜  rox git:(HUB-1908) ✗ rox start
rox main.sh path: /home/peter/sites/labs/rox/main.sh
[+] Running 9/9
 ✔ Container labs-translate   Started                                                                                                                                                                                              0.3s 
 ✔ Container labs-db          Started                                                                                                                                                                                              0.4s 
 ✔ Container labs-mongo       Started                                                                                                                                                                                              0.3s 
 ✔ Container labs-phpdoc      Started                                                                                                                                                                                              0.3s 
 ✔ Container labs-frankenphp  Started                                                                                                                                                                                              0.3s 
 ✔ Container labs-ftp         Started                                                                                                                                                                                              0.2s 
 ✔ Container labs-cache       Started                                                                                                                                                                                              0.3s 
 ✔ Container labs-app         Started                                                                                                                                                                                              0.1s 
 ✔ Container labs-web         Started                                                                                                                                                                                              0.1s 
/home/peter/sites/labs/rox
cp: kan inte ta status på 'rox/phpstan.neon.dist': Inte en katalog
[DONE] 127.0.0.1 dev.local
[DONE] 172.18.0.5 cache
[DONE] 172.18.0.6 dev.local
Enabling opcache
Enabling xdebug
Enabling gnupg
Enabling mongodb
ln: failed to create symbolic link '/var/www/public/storage': No such file or directory
[DONE] Created symlink from public/storage to storage
```

Docker ps
```  
➜  rox git:(HUB-1908) ✗ docker ps
CONTAINER ID   IMAGE                                  COMMAND                  CREATED      STATUS                    PORTS                                                                                                                      NAMES
ba7dd1282de2   docker/model-runner:latest-cuda        "/app/model-runner"      5 days ago   Up 2 hours                127.0.0.1:12434->12434/tcp, 172.17.0.1:12434->12434/tcp                                                                    docker-model-runner
c5233f2ec91f   labs-app                               "/entrypoint.sh"         5 days ago   Up 49 seconds             1234/tcp, 9000/tcp, 9003/tcp, 127.0.0.1:35729->35729/tcp                                                                   labs-app
dc70aa420c2f   labs-web                               "/entrypoint.sh"         5 days ago   Up 49 seconds             127.0.0.1:80->80/tcp, 127.0.0.1:443->443/tcp                                                                               labs-web
d7a4832abedc   mongo:latest                           "docker-entrypoint.s…"   5 days ago   Up 49 seconds             127.0.0.1:27017->27017/tcp                                                                                                 labs-mongo
fba411af0194   garethflowers/ftp-server               "/docker-entrypoint.…"   5 days ago   Up 49 seconds (healthy)   0.0.0.0:20-21->20-21/tcp, [::]:20-21->20-21/tcp, 0.0.0.0:40000-40009->40000-40009/tcp, [::]:40000-40009->40000-40009/tcp   labs-ftp
ea00023affad   mariadb:latest                         "docker-entrypoint.s…"   5 days ago   Up 49 seconds             127.0.0.1:3306->3306/tcp                                                                                                   labs-db
edd1bd9d9bde   libretranslate/libretranslate:latest   "./venv/bin/libretra…"   5 days ago   Up 49 seconds (healthy)   5050/tcp, 127.0.0.1:5050->5000/tcp                                                                                         labs-translate
46bf5d0c655c   eqalpha/keydb:latest                   "docker-entrypoint.s…"   7 days ago   Up 49 seconds             127.0.0.1:6379->6379/tcp                                                                                                   labs-cache
➜  rox git:(HUB-1908) ✗ 
```

Telnet
``` 
➜  rox git:(HUB-1908) ✗ telnet dev.local 80    
Trying 127.0.0.1...
Connected to dev.local.
Escape character is '^]'.
^Z
Connection closed by foreign host.
➜  rox git:(HUB-1908) ✗ telnet dev.local 8080
Trying 127.0.0.1...
Connection failed: Förbindelsen förvägrad
Trying 127.0.0.1...
telnet: Unable to connect to remote host: Förbindelsen förvägrad
➜  rox git:(HUB-1908) ✗ 
```