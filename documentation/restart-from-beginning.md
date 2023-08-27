# ROX - restart from the beginning

If you want to reinstall the docker environment then you can follow these instructions.  
If you want to install for the first time then **do not** follow these instructions

# Delete boxes
The below commands delete all docker images and containers. Not just the ones for this project.

If you know a command that take only the boxes for this project then please update the documentation.

```
docker stop $(docker ps -q)
docker image ls -a
docker image prune -a
docker container ls -a
docker container prune -a
```

# Rename the old installation
If you use rox with Infohub then Your installations name might be infohub.

mv infohub infohub-old  

# Clone down the latest code
If you work with Infohub then do this:

```
git clone https://github.com/peterlembke/infohub
```

# .env file
Copy the .env file from the old location.
Only useful if you use a .env file, like if you use Laravel.

```
cd infohub
cp infohub-old/.env infohub/.env
```

# Check your local hosts file

```
sudo nano /etc/hosts
```

127.0.0.1       infohub.local  

# Start the installation scripts

```
rox up
```
When the installation get stuck after 10-15 min then you can do this in another tab

```
rox stop
rox start
rox purge laravel
```

# Install all packages

```
rox composer install
```

The environment is now installed.
