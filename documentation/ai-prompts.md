# AI prompts

Goal: Echo the full path to the main file
Details:
When I run "rox up" it runs the rox/rox script. That script can be in any folder in my PATH so I can run the same command from everywhere.
The rox script then look for a rox/main.sh. I want to echo the full path to that main.sh from within main.sh

Same thing with rox/rox.php, it can be in my PATH and can be in any folder. It finds the main.php and run it. I want rox/main.php to echo the full path to itself.

Background:
I have issues with HOST_UID and HOST_GID. When I run "rox up" I get HOST_UID=1000 and HOST_GID=1000 despite they are set to 1100 both in main.sh and in main.php. I suspect that a different main.sh is run so I need to know the full path.
They are also set correctly in the rox/images/app/Dockerfile and rox/images/web/Dockerfile.