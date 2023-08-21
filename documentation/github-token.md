# Github token

You need a github token to access all GitHub repos, both private and public. 

## Create a token
Go to [Github tokens](https://github.com/settings/tokens) and register a new personal token.
You need to mark "repo". Set the expiration date. Set a title. Copy the token from the top of the page.

Document your token.

## Token in ROX before installation

Search in the rox-folder for ENV GITHUB_TOKEN and set your token here.
``` 
ENV GITHUB_TOKEN ghp_xxxxxxxxxxxxxxxxxxxxx
```

## Token in ROX after installation
You can set a new token in an existing installation.
```
rox shell app root
sudo -u "dockerhost" composer config --global github-oauth.github.com "ghp_xxxxxxxxxxxxxxxxxxxxx"  
```
