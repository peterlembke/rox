# Build containers

When you do changes in the config you need to build and then update.
If you just start an already set up project then use `rox start`.

## Build a single container

Builds the container applying the changes

    rox build frankenphp

## Update a single container

Run after build.

    rox up -d frankenphp

## Build all containers that have changes

Update the containers that got any changes in the config files.

    rox build

## Build all containers from the beginning

We scrap everything and build everything. Even those that should already work.

    rox build --no-cache

## Update all containers

Do this after a build.
-d is detached, doing the job in the background.

    rox up -d

## Start all containers

A normal start of the project.

    rox start