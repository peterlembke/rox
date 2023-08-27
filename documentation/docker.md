# Docker in general

Docker commands that are practical to know.

# Images

An image is the template for a container.
See [unused and dangling images](https://stackoverflow.com/questions/45142528/what-is-a-dangling-image-and-what-is-an-unused-image)

## Lists all images
docker image ls -a

## Delete one image
docker image rm {repository name or image id}

## List dangling images
docker images -f dangling=true

## Delete dangling images
docker image rm $(docker images -qf "dangling=true")

## List unused images
According to ChatGPT this can be used.
docker images -q --filter "dangling=false" | xargs -I {} docker image inspect {} -f '{{ .Id }} {{ .RepoTags }}' | awk '$2 == "null:latest" {print $1}'

## Delete unused images and dangling images
Delete unused images (dangling and not associated with containers)
docker image prune -a

## Delete all images
docker image rm -a

# Containers

A container is the virtual computer. Its data is based on an image.

## Lists all containers
docker container ls -a

## Delete one container
docker container rm {container id}

## Delete all containers
docker container rm -a

# Build

When you do changes to the DockerFile or config and want to activate that.

See [Details](https://stackoverflow.com/questions/35594987/how-to-force-docker-for-a-clean-build-of-an-image)

Build all steps, not just the changed ones
docker build --no-cache

Build the changed steps.
docker build

# Clean most things

docker system prune

```
WARNING! This will remove:
    - all stopped containers
    - all volumes not used by at least one container
    - all networks not used by at least one container
    - all images without at least one container associated to them
Are you sure you want to continue? [y/N] 
```
