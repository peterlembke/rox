# Database
How to connect to the local MySQL Docker container.
Audience: developers

## Switch between different env files
If you run a framework like Laravel in your rox environment then they depend on the .env file.
In the .env file there are connections to the databases.
If you want to switch between using a local copy of the database, or running against the dev db server or against the live db server (read only) then you need to change the .env file.

ROX has a built-in command for switching env file in Laravel.
First you need the three env files.

Put the .env.xxxxx files in your root Laravel folder.

Set an env file with this rox command
```
rox db local
rox db dev
rox db live
```

Name the env files:

* .env.rox-local-db-with-passwords
* .env.rox-dev-db-with-passwords
* .env.rox-live-db-with-passwords

Example
```
rox db local                                
cp /var/www/.env.rox-local-db-with-passwords /var/www/.env
Configuration cache cleared!
[DONE] Using .env.rox-local-db-with-passwords as .env file 
```

## Software
You need some software to connect to the database container.
With this software you can create databases and view the data.

### Mac OS
On Mac OS you have the excellent [Sequel Ace](https://sequel-ace.com/).

Create a new standard connection.
Host: 127.0.0.1
Username: root
Password: topsecret

### Linux / macOS / Windows
On Linux and macOS you have the good [Dbeaver](https://dbeaver.io/download/) application.

You can also use [MySQL workbench](https://dev.mysql.com/downloads/workbench/) if you run MySQL. Do not use with MariaDb.

## Let PHP find the database
You could get super slow database connections from the docker box if you have an external database on a server somewhere.
To prevent that check that project.conf has your database IP.
```
export DB_URL="dev.local"
export DB_IP="192.168.0.100"
```

When you run `rox start` the docker app hosts file will be updated.
Check that the app docker box has the information
```
rox shell app root
nano /etc/hosts
```
