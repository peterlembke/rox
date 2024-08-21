# Docker (Mac) De-facto Standard Host Address Alias
From [ralphschindler](https://gist.github.com/ralphschindler/535dc5916ccbd06f53c1b0ee5a868c93)

This launchd script will ensure that your Docker environment on your Mac will have `10.254.254.254` as an alias on your loop back device (127.0.0.1).  The command being run is `ifconfig lo0 alias 10.254.254.254`.

Once your machine has a well known IP address, your PHP container will then be able to connect to it, specifically XDebug can connect to it at the configured `xdebug.client_host`.

### Installation Of IP Alias (This survives reboot)

Copy/Paste the following in terminal with sudo (must be root as the target directory is owned by root)...

```bash
sudo curl -o /Library/LaunchDaemons/com.ralphschindler.docker_10254_alias.plist https://gist.githubusercontent.com/ralphschindler/535dc5916ccbd06f53c1b0ee5a868c93/raw/com.ralphschindler.docker_10254_alias.plist
```

Or copy the above Plist file to /Library/LaunchDaemons/com.ralphschindler.docker_10254_alias.plist

Next and every successive reboot will ensure your lo0 will have the proper ip address.

DEPRECATED 2024-07-04 - Not needed any more:
Finally, make sure to configure your xdebug correctly. However, you get your `xdebug.client_host` into the container, ensure it has similar settings:

```ini
zend_extension=xdebug.so
xdebug.client_host=10.254.254.254
xdebug.default_enable = On
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.discover_client_host=yes
xdebug.max_nesting_level = -1
xdebug.log = "/var/www/log/xdebug.log"
xdebug.output_dir = "/var/www/log/profiler"
```

It is also useful to pass the following environment variable into your container:

`PHP_IDE_CONFIG="serverName=localhost"`

PHPStorm will use `localhost` as the server name when you set up the `Preferences > PHP > Debugging` profile.

#### Why?

Because `docker.local` is gone. This seems to be the easiest way to set up xdebug to connect back to your IDE running on your host.  Similarly, this is a solution for any kind of situations where a container needs to connect back to the host container at a known ip address.
