# PHP Documentor
The PHP Documentor can generate a web documentation by parsing all the PHPDOCs we add to all functions and classes and variables.

# Generate documentation
The PHP Documentor can generate an HTML documentation by parsing all our source code like this

```
rox phpdoc vendor/your_domain public/phpdoc
```

The generated files will be saved in folder: public/phpdoc

Now open the index.html file like this

``` 
http://infohub.local/phpdoc/index.html
```

Do not commit the documentation. Just use it locally.

# Usage
With the documentation you can:

* Find all misses in the PHPDOCs
* Find all TODOs
* Find all deprecated features - that you will remove in the next major version 

In general the PHP Documentor will help us write better PHPDOC headers to guide developers in our code.

It also claims to have a full text search, but I have not found it. It would be very useful.

# Create PHPDOC like this
This PHPDOC header works very well in PHP documentor.

First one for the file at the top of the file.
```
/**
 * The main service provider
 *
 * @package     mydomain
 * @subpackage  mydomain_mypackage
 */
```

# PHP Storm can create for you
PHP Storm can be defined to create the template for each new class file.
More info [here](https://www.jetbrains.com/help/phpstorm/settings-file-and-code-templates.html).
  
PHP Storm -> Preferences -> Editor -> File and Code Templates -> Includes -> PHP File Header.
  
Here you can add this:
``` 
  /**
   * 
   *
  #if (${NAMESPACE}) * @package     mydonaim
  #set($subpackage_name = $NAMESPACE.substring(8).toLowerCase())
  * @subpackage  mydomain_${subpackage_name}
  #end
   */
```
PHP Storm -> Preferences -> Editor -> File and Code Templates -> Includes -> PHP Class Doc Comment.
  
Here you can add this:
```php 
  /**
   *
   * 
   #set($package_name = $NAMESPACE.substring(8).toLowerCase())
   * @author      ${USER}
   * @version     ${DATE}
   * @since       ${DATE}
   * @copyright   Copyright (c) ${YEAR}, My Company AB
   * @license     https://opensource.org/licenses/gpl-license.php GPL-3.0-or-later
   * @see         https://github.com/MyDomain/mydomain_${package_name} GitHub
   * @link        https://my-domain.com/ My company name
   */
```  
     
## Change username
If ${USER} does not give you the username you want then it can be changed like this:
  
Menu “Help” → “Edit custom VM options…”
  
I added this to the end:
  
``` 
-Duser.name=Peter Lembke <peter@infohub.se>
```  

Restart PHP Storm just in case. Now when you create a new PHP Class you get your name.
  
More details [here](https://intellij-support.jetbrains.com/hc/en-us/community/posts/207046805-How-to-change-USER-vaule-) and [here](https://www.jetbrains.com/help/phpstorm/tuning-the-ide.html#default-dirs).

EndOfDocument
