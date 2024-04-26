#!/usr/bin/env bash

DOCKER_BIN="$(which docker)"
COMPOSE_BIN="$DOCKER_BIN"
COMPOSE_DIR="$(cd "$(dirname "$0")" && pwd)"

source "$COMPOSE_DIR/default.conf"
source "$COMPOSE_DIR/project.conf.dist"
[ -f "$COMPOSE_DIR/project.conf" ] && source "$COMPOSE_DIR/project.conf"

export COMPOSE_PROJECT_NAME="$PROJECT_NAME"
export HOST_UID=1000
export HOST_GID=1000

if [[ "$OSTYPE" == "linux"* ]]
then
  export HOST_UID=$(id -u)
  export HOST_GID=$(id -g)
fi

#############################################
# Utility functions
printc()
{
  if [ -t 1 ] # print colors if TTY
  then
    echo -ne $'\e'"[$2m$1"$'\e'"[0m"
  else
    echo -ne "$1"
  fi
}
notice()  { printc "$1" "1;34";       }
success() { printc "$1" "0;32";       }
error()   { printc "$1" "1;31" 2>&1;  }
warning() { printc "$1" "1;33" 2>&1;  }

#############################################
# Run docker-compose command
compose_cmd()
{
  local os=linux
  if [[ "$OSTYPE" == "darwin"* ]]
  then
    os=mac
  fi
  "$COMPOSE_BIN" compose \
    --file "$COMPOSE_DIR"/docker-compose.yml \
    --file "$COMPOSE_DIR"/docker-compose."$os".yml \
    --project-name "$COMPOSE_PROJECT_NAME" \
    "$@"
}

################################################################################
# Execute command in container
container_exec()
{
  local container="$1"; shift
  local user="$1"; shift
  if [ -t 0 ]
  then
    compose_cmd exec --user "$user" "$container" "$@"
  else
    docker exec -i --user "$user" "$(compose_cmd ps -q "$container")" "$@"
  fi
}

#############################################
# Run Laravel CLI command
laravel_cmd()
{
  container_exec appserver dockerhost \
    php -f "$ROX_BASE_DIR"'/artisan' -- "$@"
}

#############################################
# Run Composer command
composer_cmd()
{
  container_exec appserver dockerhost \
    composer --working-dir="$ROX_BASE_DIR" "$@"
}

#############################################
# Run PHPDOC command to render documentation for a folder
# Infohub uses: folder, others might use: vendor, or current folder: .
phpdoc_cmd()
{
    local folder="$1"

  if [ "x$folder" = 'x' ]
    then
      folder='.' # current folder
    fi

    local destination="$2"

  if [ "x$destination" = 'x' ]
    then
      destination='phpdoc' # current folder
    fi

  docker run --rm -v $(pwd):/data phpdoc/phpdoc -d "$folder" -t "$destination"
}

#############################################
# Run MySQL command
mysql_cmd()
{
  local cmd=mysql
  if [ "$1" = 'admin' ]
  then
    local cmd=mysqladmin
    shift
  elif [ "$1" = 'dump' ]
  then
    local cmd=mysqldump
    shift
  fi
  container_exec dbserver root \
    "$cmd" \
      --user="$ROX_DB_USER" \
      --password="$ROX_DB_PASS" \
      $([ "$cmd" != 'mysqladmin' ] && echo "$ROX_DB_NAME") \
      "$@"
}

mysql_admin()
{
  container_exec dbserver root mysqladmin\
      --user="$ROX_DB_USER" \
      --password="$ROX_DB_PASS" \
      "$@"
}

# rox db dump main
mysql_dump()
{
  local db="$ROX_DB_NAME"

  if [ "x$1" != 'x' ]
    then
      db="$1"
  fi

  local date="$(date +"%Y%m%d-%H%M%S")"

  container_exec dbserver root mysqldump \
      --user="$ROX_DB_USER" --password="$ROX_DB_PASS" \
      "$db" \
      | bzip2 > "$db-$date.sql.bz2"
}

#############################################
# Set the .env file and clear the env cache
set_env()
{
  local env_name="$1"

  if [ "x$env_name" = 'x' ]
    then
      env_name='local'
    fi

  local base_dir="$(cd "$COMPOSE_DIR"/.. && pwd)"

  container_exec appserver root sh -c "cp $ROX_BASE_DIR/$env_name $ROX_BASE_DIR/.env"
  notice "cp $ROX_BASE_DIR/$env_name $ROX_BASE_DIR/.env"; echo
  laravel_cmd config:clear
  success '[DONE] '; notice "Using $env_name as .env file"; echo
}

#############################################
# Create symlink
set_symlink()
{
  local base_dir="$(cd "$COMPOSE_DIR"/.. && pwd)"

  container_exec appserver root sh -c "ln -s $ROX_BASE_DIR/storage $ROX_BASE_DIR/public/storage"
  success '[DONE] '; notice "Created symlink from public/storage to storage"; echo
}

#############################################
# Run Redis command
redis_cmd()
{
  local port="$ROX_CACHE_BACKEND_REDIS_PORT"
  local db="$ROX_CACHE_BACKEND_REDIS_DB"
  if [ "$1" = 'fpc' ]
  then
    local port="$ROX_PAGE_CACHE_REDIS_PORT"
    local db="$ROX_PAGE_CACHE_REDIS_DB"
    shift
  elif [ "$1" = 'session' ]
  then
    local port="$ROX_SESSION_SAVE_REDIS_PORT"
    local db="$ROX_SESSION_SAVE_REDIS_DB"
    shift
  fi
  container_exec cacheserver root redis-cli -p "$port" -n "$db" "$@"
}

#############################################
# Run PHP-FPM command
fpm_cmd()
{
  container_exec appserver root \
    service "php$PHP_VERSION-fpm" "$@"
}

#############################################
# Run Varnish command
varnish_cmd()
{
  local cmd='varnishadm'
  if [ "$1" = 'log' ]
  then
    local cmd='varnishlog'
    shift
  fi
  container_exec proxyserver root "$cmd" "$@"
}

#############################################
# Run PhpStan command
# vendor/bin/phpstan analyse folder --level 2
# rox analyse -c /var/www/phpstan.neon.dist
phpstan_cmd()
{
  local cmd='vendor/bin/phpstan'

  if [ "x$1" = 'x' ]
    then
      container_exec appserver root \
        php -f "$ROX_BASE_DIR/""$cmd" "analyse" "-c" "$ROX_BASE_DIR/phpstan.neon.dist"
      return 0
  fi

  if [ "$1" = '--level' ]
    then
      container_exec appserver root \
        php -f "$ROX_BASE_DIR/""$cmd" "analyse" "-c" "$ROX_BASE_DIR/phpstan.neon.dist" "--level" "$2"
      return 0
  fi

  container_exec appserver root \
    php -f "$ROX_BASE_DIR/""$cmd" "analyse" "$@"
}

#############################################
# Run BASH shell in container
exec_shell()
{
  local container="$1"
  local user="$2"
  [ "x$container" = 'x' ] && local container='appserver'
  [[ "$container" == *'server' ]] || local container="$container"'server'
  if [ "x$user" = 'x' ]
  then
    if [ "$container" = 'appserver' -o "$container" = 'webserver' ]
    then
      local user='dockerhost'
    else
      local user='root'
    fi
  fi
  container_exec "$container" "$user" bash
}

#############################################
# Get the docker box IP number
get_ip()
{
  local container="$1"

  if [ "x$container" = 'x' ]
    then
      container='app'
    fi

  container="$PROJECT_NAME"-"$container"

  docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$container"
}

#############################################
# Update Get the docker HOST URL
update_hosts_file()
{
  # Database IP and URL
  local ip_host="$DB_IP $DB_URL"

  container_exec appserver root \
    sudo sh -c "grep -qxF \"$ip_host\" /etc/hosts || echo \"$ip_host\" >> /etc/hosts"

  success '[DONE] '; notice "$ip_host"; echo

  # Cache server
  local ip="$(get_ip cache)"
  local ip_host="$ip cache"

  container_exec appserver root \
    sudo sh -c "grep -qxF \"$ip_host\" /etc/hosts || echo \"$ip_host\" >> /etc/hosts"

  success '[DONE] '; notice "$ip_host"; echo

  # Web server
  local ip="$(get_ip web)"
  local ip_host="$ip $HOST_URL"

  container_exec appserver root \
    sudo sh -c "grep -qxF \"$ip_host\" /etc/hosts || echo \"$ip_host\" >> /etc/hosts"

  success '[DONE] '; notice "$ip_host"; echo
}

#############################################
# Clean all known cache layers in an independent setup
purge_all_independent()
{
  notice 'Banning Varnish URLs'; echo -n ' .. '
  e="$(varnish_cmd 'ban req.url ~ .*')" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Flushing Redis Cache'; echo -n ' .. '
  e="$(redis_cmd FLUSHDB)"
  # `redis-cli' returns 0 on error, look for output `OK' instead
  [ "$e" = "OK"$'\r' ] && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Clearing PHP OPCache'; echo -n ' .. '
  e="$(fpm_cmd reload)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
}

#############################################
# Clean all known cache layers for laravel
purge_all_laravel()
{
  notice 'Banning Varnish URLs'; echo -n ' .. '
  e="$(varnish_cmd 'ban req.url ~ .*')" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Flushing Redis Cache'; echo -n ' .. '
  e="$(redis_cmd FLUSHDB)"
  # `redis-cli' returns 0 on error, look for output `OK' instead
  [ "$e" = "OK"$'\r' ] && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  # https://codescompanion.com/how-to-clear-cache-in-laravel-5/
  notice 'Clearing Laravel Application Cache'; echo -n ' .. '
  # `laravel_cmd' returns 129 on exit if stdin is a tty, hence `echo |'
  e="$(echo | laravel_cmd cache:clear 2>&1)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Clearing Laravel Route Cache'; echo -n ' .. '
  # `laravel_cmd' returns 129 on exit if stdin is a tty, hence `echo |'
  e="$(echo | laravel_cmd route:cache 2>&1)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Clearing Laravel Config Cache'; echo -n ' .. '
  # `laravel_cmd' returns 129 on exit if stdin is a tty, hence `echo |'
  e="$(echo | laravel_cmd config:clear 2>&1)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Clearing Laravel compiled view files'; echo -n ' .. '
  # `laravel_cmd' returns 129 on exit if stdin is a tty, hence `echo |'
  e="$(echo | laravel_cmd view:clear 2>&1)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Clearing all cached events and listeners'; echo -n ' .. '
  # `laravel_cmd' returns 129 on exit if stdin is a tty, hence `echo |'
  e="$(echo | laravel_cmd event:clear 2>&1)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Clearing Laravel Lighthouse GraphQL schema cache'; echo -n ' .. '
  # `laravel_cmd' returns 129 on exit if stdin is a tty, hence `echo |'
  e="$(echo | laravel_cmd lighthouse:clear-cache 2>&1)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Clearing PHP OPCache'; echo -n ' .. '
  e="$(fpm_cmd reload)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Publish all vendor files'; echo -n ' .. '
  # `laravel_cmd' returns 129 on exit if stdin is a tty, hence `echo |'
  e="$(echo | laravel_cmd vendor:publish --all --force 2>&1)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
  notice 'Publish all livewire files'; echo -n ' .. '
  # `laravel_cmd' returns 129 on exit if stdin is a tty, hence `echo |'
  e="$(echo | laravel_cmd livewire:publish 2>&1)" && success '[DONE]' && echo || {
    error '[ERROR]'; echo
    echo "$e"
  }
}

#############################################

# Run PHPUnit test in app container
test_unit_laravel()
{
  local subjects
  local nsubs=0
  local bd="$(cd "$COMPOSE_DIR"/.. && pwd)"
  local cwd="$(pwd)"
  local phpunit="$ROX_BASE_DIR"'/vendor/phpunit/phpunit/phpunit'
  local config='phpunit.xml'
  # [ -f "$bd/$config" ] || config="$config"'.dist'
  config="$ROX_BASE_DIR"'/'"$config"

  # Find number of arguments until first option (if any)
  for arg in "$@"
  do
    [[ "$arg" = -* ]] && break
    let ++nsubs
  done

  if [ $nsubs -eq 0 ]
  then
    # No test subjects given - run full suite
    container_exec appserver dockerhost \
      php -f "$phpunit" -- -c "$config" "${@:$nsubs+1}"
    return
  fi

  for subject in "${@:1:$nsubs}"
  do
    if [ ! -e "$subject" ]
    then
      error "Cannot access '$subject': No such file or directory"; echo 1>&2
      continue
    fi

    notice 'Testing '; success "$subject"; echo ' ..'
    local hostpath="$cwd/$subject"
    local guestpath="$ROX_BASE_DIR/${hostpath/$bd\//}"
    container_exec appserver dockerhost \
      php -f "$phpunit" -- -c "$config" "${@:$nsubs+1}" "$guestpath"
  done
}

#############################################

# Run PHPUnit test in app container
test_unit_paratest()
{
  local subjects
  local nsubs=0
  local bd="$(cd "$COMPOSE_DIR"/.. && pwd)"
  local cwd="$(pwd)"
  local phpunit="$ROX_BASE_DIR"'/vendor/bin/paratest'
  local config='phpunit.xml'
  # [ -f "$bd/$config" ] || config="$config"'.dist'
  config="$ROX_BASE_DIR"'/'"$config"

  # Find number of arguments until first option (if any)
  for arg in "$@"
  do
    [[ "$arg" = -* ]] && break
    let ++nsubs
  done

  if [ $nsubs -eq 0 ]
  then
    # No test subjects given - run full suite

    container_exec appserver dockerhost \
      ./var/www/dox/script/paratest
    return

    # php -f "$phpunit" -- -c "$config" --runner WrapperRunner --processes 6 "${@:$nsubs+1}"
    # php -f "$phpunit" -- -c "$config" --runner WrapperRunner --processes 6 --log-junit /var/www/report.xml "${@:$nsubs+1}"
    # Copy paste report.xml to https://marmelab.com/phpunit-d3-report/

  fi

  for subject in "${@:1:$nsubs}"
  do
    if [ ! -e "$subject" ]
    then
      error "Cannot access '$subject': No such file or directory"; echo 1>&2
      continue
    fi

    notice 'Testing '; success "$subject"; echo ' ..'
    local hostpath="$cwd/$subject"
    local guestpath="$ROX_BASE_DIR/${hostpath/$bd\//}"
    container_exec appserver dockerhost \
      php -f "$phpunit" -- -c "$config" "${@:$nsubs+1}" "$guestpath"
  done
}

#############################################

# Run PHPUnit test in app container
# https://stackoverflow.com/questions/11829931/setting-xdebug-coverage-enable-on-on-command-line-for-phpunit
# Observe that xdebug must be enabled for the code coverage to work.
test_unit_coverage()
{
  local subjects
  local nsubs=0
  local bd="$(cd "$COMPOSE_DIR"/.. && pwd)"
  local cwd="$(pwd)"
  local phpunit="$ROX_BASE_DIR"'/vendor/phpunit/phpunit/phpunit'
  local config='phpunit.xml'
  # [ -f "$bd/$config" ] || config="$config"'.dist'
  config="$ROX_BASE_DIR"'/'"$config"

  # Find number of arguments until first option (if any)
  for arg in "$@"
  do
    [[ "$arg" = -* ]] && break
    let ++nsubs
  done

  if [ $nsubs -eq 0 ]
  then
    # No test subjects given - run full suite
    container_exec appserver dockerhost \
      php -dxdebug.mode=coverage -f "$phpunit" -- -c "$config" --coverage-html /var/www/public/reports/ "${@:$nsubs+1}"
    return
  fi

  for subject in "${@:1:$nsubs}"
  do
    if [ ! -e "$subject" ]
    then
      error "Cannot access '$subject': No such file or directory"; echo 1>&2
      continue
    fi

    notice 'Testing '; success "$subject"; echo ' ..'
    local hostpath="$cwd/$subject"
    local guestpath="$ROX_BASE_DIR/${hostpath/$bd\//}"
    container_exec appserver dockerhost \
      php -dxdebug.mode=coverage -f "$phpunit" -- -c "$config" --coverage-html /var/www/public/reports/ "${@:$nsubs+1}" "$guestpath"
  done
}

#############################################
# Handle "laravel" action
if [ "$1" = 'laravel' ]
  then
    shift
    laravel_cmd "$@"

#############################################
# Handle laravel artisan action
elif [ "$1" = 'artisan' ]
  then
    shift
    laravel_cmd "$@"

#############################################
# Handle "composer" action
elif [ "$1" = 'composer' ]
  then
    shift
    composer_cmd "$@"

#############################################
# Handle "phpdoc" action
elif [ "$1" = 'phpdoc' ]
  then
    shift
    phpdoc_cmd "$@"

#############################################
# Handle "shell" action
elif [ "$1" = 'shell' ]
  then
    shift
    exec_shell "$@"

#############################################
# Handle "db" action
elif [ "$1" = 'db' ]
  then
    shift
    if [ "$1" = 'nuke' ]
    then
      mysql_cmd admin --force drop "$ROX_DB_NAME"
      mysql_cmd admin --default-character-set=utf8 create "$ROX_DB_NAME"
    elif [ "$1" = 'admin' ]
    then
      shift
      mysql_admin "$@"
    elif [ "$1" = 'dump' ]
    then
      shift
      mysql_dump "$@"
    elif [ "$1" = 'local' ]
    then
      set_env ".env.rox-local-db-with-passwords"
    elif [ "$1" = 'dev' ]
    then
      set_env ".env.rox-dev-db-with-passwords"
    elif [ "$1" = 'live' ]
    then
      set_env ".env.rox-live-db-with-passwords"
    else
      mysql_cmd "$@"
    fi

#############################################
# Handle "cache" action
elif [ "$1" = 'cache' ]
  then
    shift
    redis_cmd "$@"

#############################################
# Handle "httpd" action
elif [ "$1" = 'httpd' ]
  then
    shift
    container_exec webserver root apachectl "$@"

#############################################
# Handle "xdebug" action
elif [ "$1" = 'xdebug' -o "$1" = 'opcache' -o "$1" = 'gnupg' -o "$1" = 'mongodb' ]
  then
    if [ "$2" = 'off' ]
    then
      notice 'Disabling '"$1"; echo
      container_exec appserver root phpdismod "$1"
    else
      notice 'Enabling '"$1"; echo
      container_exec appserver root phpenmod "$1"
    fi
    fpm_cmd reload

#############################################
# Handle "npm" and "grunt" actions
elif [ "$1" = 'npm' -o "$1" = 'grunt' ]
  then
    cmd="$@"
    container_exec appserver dockerhost \
      bash -c "cd /var/www/ && $cmd"

#############################################
# Handle "purge" action
elif [ "$1" = 'purge' ]
  then
    if [ "$2" = 'laravel' ]
    then
      purge_all_laravel
    else
      purge_all_independent
    fi

#############################################
# Handle "container" action
elif [ "$1" = 'container' ]
  then
    shift
    if [ "$1" = 'ip' ]
    then
      shift
      get_ip "$@"
    elif [ "$1" = 'url' ]
    then
      shift
      update_hosts_file "$@"
    fi

#############################################
# Handle "data-sync" action
elif [ "$1" = 'data-sync' ]
  then
    shift
    if [ "$1" = 'db' ]
    then
      shift
      "$COMPOSE_DIR"'/data-sync/db.sh' "$@"
    elif [ "$1" = 'media' ]
    then
      shift
      "$COMPOSE_DIR"'/data-sync/media.sh' "$@"
    else
      error 'Synchronize what?'; echo >&2
      echo 'Did you mean "data-sync db" or "data-sync media"?' >&2
      exit 1
    fi

#############################################
# Handle "unit" action
elif [ "$1" = 'unit' ]
  then
    if [ "$2" = 'laravel' ]
    then
        shift
        shift
        test_unit_laravel "$@"
    elif [ "$2" = 'paratest' ]
    then
        shift
        shift
        # Turn off xdebug. We want speed
        container_exec appserver root phpdismod "xdebug"
        test_unit_paratest "$@"
    elif [ "$2" = 'coverage' ]
    then
        shift
        shift
        # Turn ON xdebug. It is required
        container_exec appserver root phpenmod "xdebug"
        test_unit_coverage "$@"
    fi
#############################################
# Handle "analyse" action
elif [ "$1" = 'analyse' ]
  then
    shift
    phpstan_cmd "$@"

#############################################
# Handle empty action
elif [ "x$1" = 'x' ]
  then
    echo "\
Usage:
  ./${0##*/} <command>
--------------------------------------
  laravel                   Run Laravel Artisan CLI command in app container
  artisan                   Run Laravel Artisan CLI command in app container
  phpdoc                    Render documentation of the named folder into a phpdoc folder
  composer                  Run composer command in app container
  shell <container> <user>  Run bash in given container
  db                        Open MySQL CLI
    db dump <database>      Output MySQL dump. Database = main or any existing name
    db admin                Run mysqladmin command
    db nuke                 Drop and re-create empty database
    db local                Sets the env file for local database in docker
    db dev                  Sets the env file for dev database. Use VPN.
    db live                 Sets the env file for live database You get read only. Use VPN.
  cache                     Open Redis CLI
    cache fpc               Open Page Cache Redis CLI
    cache session           Open Session Redis CLI
  httpd                     Run apachectl command
  xdebug {on|off}           Enable/disable xdebug
  opcache {on|off}          Enable/disable opcache
  npm                       Run npm command
  grunt                     Run Grunt command
  purge                     Clean all cache layers for a neutral platform
    laravel                 Clean all cache layers for Laravel
  container                 Container commands
    ip {app/web/cache/db}   Get IP for a container
    url                     Set web server URL in app HOSTS file
  data-sync                 Synchronize with remote data
    data-sync db            Download and import a remote database
    data-sync media         Download remote media files
  unit <path> <options>     Run PHPUnit tests
  analyse                   Analyse the PHP code with PHPStan

All unrecognized command are passed on to docker-compose:
Ex. './${0##*/} up' will call 'docker-compose up'
"

#############################################
# Dispatch all other commands to docker-compose
else
  compose_cmd "$@"
  if [ "$1" = 'start' ]
  then
    pwd
    cp rox/phpstan.neon.dist phpstan.neon.dist
    update_hosts_file "$@"
    notice 'Enabling opcache'; echo
    container_exec appserver root phpenmod "opcache"
    notice 'Enabling xdebug'; echo
    container_exec appserver root phpenmod "xdebug"
    notice 'Enabling gnupg'; echo
    container_exec appserver root phpenmod "gnupg"
    notice 'Enabling mongodb'; echo
    container_exec appserver root phpenmod "mongodb"
    set_symlink
  fi
  if [ "$1" = 'stop' ]
  then
    echo "Stopping"; echo >&2
  fi
fi