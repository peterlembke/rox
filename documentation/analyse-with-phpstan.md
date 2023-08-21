# Analyse with PHPStan

See [PHPStan manual](https://phpstan.org/)

## With parameters

Level is 0 to 9 where 0 is the least strict.
/var/www/folder is the absolute path in the Docker container.

``` 
rox analyse /var/www/folder --level 2
```
Or you can use the shorter version where the config is used and level is overridden.
``` 
rox analyse --level 2
```

## With config file
You can specify a config file
``` 
rox analyse -c /var/www/phpstan.neon.dist
```
Or use the shorter version where the config file in the root is used.
``` 
rox analyse
```

## Package config
We will probably have to have a PHPStan config file in some packages.
We might not want to fix some errors. A config file can skip them. 

## Example config
Example of a config file used in InfoHub
``` 
parameters:
    level: 2
    paths:
        - folder
    bootstrapFiles:
        - /var/www/public_html/define_folders.php
    excludePaths:
        analyse:
            - folder/plugins/infohub/checksum/doublemetaphone/DoubleMetaphone.php
    ignoreErrors:
        -
            message: '#Variable \$parts in empty\(\) always exists and is not falsy#'
            path: folder/plugins/infohub/doc/infohub_doc.php
        -
            message: '#Parameter \#1 \$data of function bin2hex expects string, array<int, string> given.#'
            path: folder/plugins/infohub/uuid/infohub_uuid.php
        -
            message: '#no value type specified in iterable type array#'
            path: folder
        -
            message: '#Variable \$keyWordsArray in empty\(\) always exists and is not falsy.#'
            path: folder/include/application_data.php
```

The bootstrapFiles load the constants.
