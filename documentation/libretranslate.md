# LibreTranslate

## amd64

There are builds on Docker hub for amd64 so no extra care is needed.

## arm64

Apple silicon is arm64.

Docker hub do not have a [LibreTranslate image](https://hub.docker.com/r/libretranslate/libretranslate) for arm64.
You can build yourself because the source code on GitHub have a [Dockerfile for arm64](https://github.com/LibreTranslate/LibreTranslate/blob/main/docker/arm.Dockerfile).

```
git clone https://github.com/LibreTranslate/LibreTranslate.git
cd LibreTranslate
docker build -t libretranslate-arm64 -f docker/arm.Dockerfile . 
```
You can now start the docker image like this below or let it start by a docker-compose file.
```
docker run -d -p 5000:5000 libretranslate-arm64
```

The docker-compose file below is for amd64.
```
services:

  # http://0.0.0.0:5050/
  translate:
    container_name: ${PROJECT_NAME}-translate
    image: libretranslate-arm64
    restart: always
    expose:
      - "5050"
    ports:
      - "${PROJECT_IP}:5050:5000"
```