# PHP version

Typed properties is [new to 7.4](https://kinsta.com/blog/php-7-4/#typed-properties).  
If you have trouble with PHP syntax then check your PHP version.

The ROX environment run PHP 8.1 locally.  

You can search in your "rox" folder for 8.1 to see if you have the latest code there.

You can check your ROX php version with
```
rox shell app root
php -v
exit
```

To upgrade to 8.1 you can run
```
rox stop  
rox build --no-cache
rox up
```

Set your PHP Storm to use PHP 8.1 like this:
```
PHP Storm -> Preferences -> Languages & Frameworks -> PHP -> PHP Language level -> 8.1 -> OK
```

If you do not have PHP 8 then upgrade your PHP Storm.
