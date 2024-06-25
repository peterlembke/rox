# Tips how to use ROX

## Cache
`rox purge` - The super cleaner that cleans the magento cache, redis, varnish, op cache restarts PHP and Apache.

## Flags
```
rox xdebug off
rox xdebug on
rox opcache off
rox opcache on
```

## Nesting
If you experience php error, try to increase max_children in `docker/images/app/www.conf` and `xdebug.max_nesting_level` in `docker/images/app/xdebug.ini`

## Database Backup
Dump the local database to a file
```
rox db dump > backup.sql
```

## Install module
You use composer for installing the modules. One issue with composer is that it must be run inside the box or else you need to install the same
php version outside the box too.
This command solve it all
```
rox composer require smile/elasticsuite
```

## List boxes
With rox all commands are piped through to Docker.
```
rox ps
```
will show you a list of all active Docker boxes.

## Shell
You can enter the shell for all boxes. Add root to the end to become root in the box.

### With ROX
Add "root" at the end to log in as root.
```
rox shell web
rox shell web root
rox shell app
rox shell proxy
rox shell search
rox shell cache
```
In the box you run Linux. If a Linux command is missing you can use `apt install` to install what you need.

### or with Docker

You can log in as user dockerhost
`docker exec -it --user dockerhost infohub-app bash`

You can log in as root
`docker exec -it infohub-app bash`

## Mailcatcher
There is a mail catcher installed in Docker. It catches the emails sent. Surf into the URL below :1080

Example: [http://local.infohub.se:1080/](http://local.infohub.se:1080/)

## Cron
You can set up cron jobs into crontab in the Docker box
```
rox magento cron:install
```
You can start crontab inside the Docker box: `rox shell app root`
```
service cron start
```

## Delete and start over
Sometimes it is just better to delete all Docker containers and start over.
Read more about [container prune](https://docs.docker.com/engine/reference/commandline/container_prune/) and [image prune](https://docs.docker.com/engine/reference/commandline/image_prune/).

```
rox stop
docker ps
docker container prune
docker image prune -a
```

Then run the normal set up with `rox up`.

## Rebuild images
If you made changes to the Docker environment you might have to rebuild the images and lose all data.
To rebuild images you must use `docker-compose build` or `docker-compose up --build`.

### Install ping in the docker box

```
rox shell app root
apt update
apt install iputils-ping
ping private.infohub.local
```

### Same network
Make sure your Docker box is not on the same IP range as the network you try to ping.
On your macOS terminal write:
```
rox container ip 
``` 
You will get the IP for the app container.

Docker uses the 172.x series. So if your network also uses that series you get into problems.

Try the tips below "One bridge too many" but unfortunately you probably need to do "Factory reset Docker desktop" and "Switch number series". 

### One bridge too many
Check if you have the new bridge.
```
docker network ls
```
If you have the `docker_gwbridge` then [people have reported](https://github.com/docker/for-mac/issues/2345) that removing this bridge made things work again.
```
docker network rm docker_gwbridge
```

### Factory reset Docker desktop
[people have reported](https://github.com/docker/for-mac/issues/2345) that resetting Docker for Mac to factory reset then it works. I can confirm this.
Back draw is that you lose all your containers and have to start them up again with `rox up`.

If you have databases in the containers then make a db dump first.

Docker Desktop >> Preferences >> Press the "Bug" icon in the top bar >> Reset to factory defaults.

### Switch number series
If you have too many troubles with number series 172. then you can switch number series to some other two private number series.
* `172.` - Can be used as number series for servers and for Docker.
* `192.168` - Normally used for local area networks
* `10.` - Normally used for connections between networks. Use this one

**macOS with Docker desktop**  

These tips work if you have no boxes to start with. See "Factory reset Docker desktop" and then:

Preferences -> Docker engine

```JSON
{
  "experimental": false,
  "debug": true,
  "bip": "10.200.0.1/24",
  "default-address-pools": [
    {
      "base": "10.201.0.0/16",
      "size": 24
    },
    {
      "base": "10.202.0.0/16",
      "size": 24
    }
  ]
}
```
Code comes from [StackExchange](https://serverfault.com/questions/916941/configuring-docker-to-not-use-the-172-17-0-0-range)

Now run
```
rox up
```
When it is done CTRL+X and run `rox start`.
Hopefully it will show:  
10.201.0.6 infohub.local
Or similar in the 10. number series.

This solved the problem for me, but it could have been the "Factory reset Docker desktop" that did the actual difference.
