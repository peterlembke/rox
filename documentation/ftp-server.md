# Your local FTP-server

You can now test your code against a real FTP-server. 
This keeps everything within your development environment. 

The [docker-ftp-server](https://github.com/garethflowers/docker-ftp-server) used.

## Configuration

The configuration is in [docker-compose.yml](../../docker-compose.yml)

Looks like this:

```
  # https://github.com/garethflowers/docker-ftp-server
  ftpserver:
    container_name: ${PROJECT_NAME}-ftp
    environment:
      - PUBLIC_IP=0.0.0.0
      - FTP_PASS=123
      - FTP_USER=user
    image: garethflowers/ftp-server
    ports:
      - "20-21:20-21/tcp"
      - "40000-40009:40000-40009/tcp" # For passive mode
    volumes:
      - "./ftp/user:/home/user"
```

The volumes section:
The "./ftp/user" is the host folder. The one you are in now.
The "/home/user" is inside the container.
Any change to either folder is shown in the other folder.

The "user" is the login FTP_USER. If you change that, then you also need to change the folder. 

## Filezilla settings

Import the settings from: [ftp-server.xml](ftp-server.xml)