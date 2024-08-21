# xdebug linux

Change in file project.conf.dist

```
# MacOS
export XDEBUG_CONFIG="client_host=10.254.254.254 discover_client_host=false idekey=PHPSTORM"
# Linux
# export XDEBUG_CONFIG="client_host=localhost discover_client_host=true idekey=PHPSTORM"
export PROJECT_IP=127.0.0.1
export PROJECT_NAME=project
export ROX_DB_NAME="dev_local"
export ROX_BASE_DIR="/var/www"
export ROX_BASE_URL="http://dev.local/"
export PHP_IDE_CONFIG="serverName=dev.local"
export HOST_URL="dev.local"
export DB_URL="dev.local"
export DB_IP="127.0.0.1" 
```

Comment the MacOS row and uncomment the Linux row.

## Modify config files
If you modify the config files like `/rox/images/web/default.conf` and you want them to be activated you need to do this:
```
rox stop  
rox build --no-cache
rox up
```
And you can also [restart from the beginning](documentation/restart-from-beginning.md) if you feel that all went wrong.
