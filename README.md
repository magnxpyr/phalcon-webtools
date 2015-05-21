Phalcon Web Tools
=================
This is an alternative to Phalcon Web Tools provided as a stand alone application.
It can be used as a module as long you set up everything.

Requirements
------------
To run this application, you need at least:
- >= PHP 5.4
- Phalcon 2.0.x
- Apache Web Server with mod rewrite enabled


Set your environment setting in 'app/config/config.php'

- set your database details on 'database'
- set your base url on 'application > baseUri'
- set tools config
```php
'tools' => array(
        'copyright' => "", // copyright header for generated files; default empty
        'modulesPath' => '', // path to your modules/app directory; mandatory
        'migrationsPath' => '', // path to migrations directory; mandatory
        'viewsDir' => '', // default Views
        'modulesDir' => '', // default Modules
        'controllersDir' => '', // default Controllers
        'formsDir' => '', // default Forms
        'allow' => '', // IP, default only 127.0.0.1
        'baseController' => [], // default
        'baseModel' => [], // default
        'baseForm' => [], // default
        'baseModule' => '', // default
        'baseRoute' => '' // default empty
    )
```
Define only what you need and remove/comment the rest.
If you're fine with the default configuration, define only 'modulesPath' and 'migrationsPath'

Third Party
-----------
* jQuery 1.11.3: https://jquery.org/ (MIT)
* jQuery UI 1.11.4 https://jqueryui.com/ (MIT)
* Bootstrap 3 http://getbootstrap.com/ (MIT)

