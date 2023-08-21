# Debug
You get an insight in what happens in your program if you use a debugger.

## xdebug
xdebug is for debugging PHP.
Xdebug is a valuable tool. You can see what the variables contain in run time locally.

Unfortunately xdebug also makes PHP slow. That is why we need to enable/disable xdebug depending on what we do.

Make sure xdebug is ON
```
rox xdebug on
```

## Firewall
Make sure the client firewall accept incoming TCP on port 9000 and 9003.
You can for a short while disable your computer firewall. You can then see if the problem is the firewall.

If you run Linux Gufw Firewall the check the report. Mark logs entries for port 9000 and press the + below to create a rule.
Do the same with the 9003 port.

## xdebug browser plugin
You can tell your browser to activate xdebug debugging for your site.

If you use PHP storm you might want to use a xdebug helper plugin in your browser, so you can put the browser in debug mode.

See the [firefox addons](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-for-firefox/) or the [chrome addons](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc).

## xdebug in PhpStorm
If you use the excellent [PHP storm](https://www.jetbrains.com/phpstorm/download/).
Link a project's directory to a docker directory.
```
php storm >> preferences >> Languages & Frameworks >> PHP >> Servers
```
Here you can set up the mapping between the source code and the server request so that xdebug knows what code to debug.

You have the files in: `/var/www` inside of the Docker container.

PHP Storm has a phone-button that must be green so that PHP storm knows it should listen for xdebug data.

## xdebug in NetBeans
If you use the free and good [Netbeans](https://netbeans.apache.org/download/index.html).

NetBeans has a debug button that opens up the URL in the default browser. The URL is enough to set the browser in debug mode.
