# Install on Linux

Install Docker like this:

```
sudo apt install docker.io
```

The package is called `docker.io` in recent versions of Debian but might be called something else in your distribution.

After the installation, make sure you add your user to the `docker` group, so you can run Docker commands without `sudo` ([read more](https://docs.docker.com/engine/installation/linux/linux-postinstall/)).

# Install on macOS

With Mac you use docker desktop. It works with Intel processors, and with Apple-silicon.

Install [Docker for Mac](https://docs.docker.com/docker-for-mac/install/).

Then do [these steps](documentation/tips.md#factory-reset-docker-desktop) to configure Docker desktop to avoid having the same ip series as the servers.

## pv
You need [pv](http://www.ivarch.com/programs/pv.shtml)
https://www.geeksforgeeks.org/pv-command-in-linux-with-examples/

```
which pv
brew install pv
```

## The rox script

In this folder you find the rox file.  
This is a small proxy script that lets you run Docker commands anywhere in the project file tree.
```
sudo nano /usr/local/bin/rox # Paste contents from the rox file in this folder
sudo chmod +x /usr/local/bin/rox
```

## Modify the HOST file
You need to add some names to your local HOSTS file.
```
sudo nano /etc/hosts
```
and add this to the file
```
127.0.0.1   dev.local
127.0.0.1   cacheserver
127.0.0.1   dbserver
127.0.0.1   proxyserver
127.0.0.1   webserver
127.0.0.1   searchserver
127.0.0.1   pgsql
127.0.0.1   translate
127.0.0.1   mongo
127.0.0.1  infohub.local
```

## Clone rox to your project
Stand in your projects root folder and type
```
git clone https://github.com/peterlembke/rox 
```
That will create the rox-folder and all files.

## Create a Github token
Go to [Github tokens](https://github.com/settings/tokens) and register a new personal token.
You need to mark "repo". Set the expiration date. Set a title. Copy the token from the top of the page.

Search in the rox-folder for ENV GITHUB_TOKEN and set your token here.
``` 
ENV GITHUB_TOKEN ghp_xxxxxxxxxxxxxxxxxxxxx
```

If you already have run rox up and want to do this afterwards:
```
rox shell app root
sudo -u "dockerhost" composer config --global github-oauth.github.com "ghp_xxxxxxxxxxxxxxxxxxxxx" 
```

## Copy the project.conf.dist
Copy the file project.conf.dist to project.conf
Review the settings and update them if needed.

## Let PHP find the database
You could get super slow database connections from the docker box.
To prevent that check that project.conf has your database ip
```
export DB_URL="dev.local"
export DB_IP="192.168.0.100"
```
Read more about [database](documentation/database.md)

## Xdebug on macOS
xdebug should work out-of-the-box on Linux.  
On macOS, however, you have to create an alias for your loop-back device, as explained [here](https://gist.github.com/ralphschindler/535dc5916ccbd06f53c1b0ee5a868c93) and restart your computer. Then you have to override the default xdebug settings in the file `docker/project.conf` (create it if it doesn't exist), and add the following line:
```
export XDEBUG_CONFIG="client_host=10.254.254.254 discover_client_host=false idekey=PHPSTORM"
```
On Linux you'll have to supply a remote host as well if you want to debug CLI commands.

Read more about [debug](documentation/debug.md) and [debug on macOS](documentation/xdebug-mac.md)

## Name your project

You can set your unique project name in file project.conf
``` 
export PROJECT_NAME=myproject
```
The PROJECT_NAME is used to set a prefix on your docker images.

* myproject-app 
* myproject-web 
* myproject-cache 
* myproject-db 
* myproject-mongo 
* myproject-translate

## First Boot
This assumes you installed `rox`. If you didn't, all `rox` commands should be replaced
with `docker/run.sh` and you have to `cd` to the project root directory to run them.
```
rox up
```
The first boot will take 10 min or so. And it will never be finished. When nothing has happened for a while, open a new terminal tab and write `rox ps` or `docker ps` to see if any docker boxes are still being starting.
When all have started. Write `rox start`. That command do some extra things to the boxes on each system start.

## Test that it works

Create folder `public_html`

Create file `index.php`
Add this content to the file:
```
<?php
echo "Hello"; 
```
Now surf to https://localhost/

## Get Laravel working
If you want to use Laravel you can do these steps:
Make sure you did not skip the step "Create a Github token" and "Let PHP find the database".

* Put your .env files in the project root.
* Run `rox start` to start and enable things needed.
* Run `git checkout -t origin/main` or whatever branch you have your code in
* Run `ssh-add`
* Run `rox composer install` to install all packages in the vendor folder.
* Run `rox composer update` to update all packages in the vendor folder.
* Run `rox db local` to get an .env file.
* Run `rox purge laravel` to clear all caches

## Ordinary Usage
```
rox # Shows a list of available commands
rox start # Boots up the environment
rox stop # Shuts down the environment
```
See the full [command list](documentation/commands.md)
See more [tips](documentation/tips.md)

## Modify config files
If you modify the config files like `/rox/images/web/default.conf` and you want them to be activated you need to do this:
```
rox stop  
rox build --no-cache
rox up
```
And you can also [restart from the beginning](documentation/restart-from-beginning.md) if you feel that all went wrong.
