Phalcon Web Tools
=================
This is an alternative to Phalcon Web Tools provided as a stand alone application.
It can be used as a module as long you set up everything.

Get Started
===========
Requirements
------------
To run this application, you need at least:
- >= PHP 5.4
- Phalcon 2.0.x
- Apache Web Server with mod rewrite enabled


Set your environment setting in 'app/config/config.php'

database

application > baseUri

'tools' => array(
        'copyright' => "", // copyright header for generated files; default empty
        'modulesPath' => __DIR__ . '/../', // path to your modules/app directory
        'migrationsPath' => __DIR__ . '/../../migrations/', // path to migrations directory
        //  'viewsDir' => '', // default Views
        //  'modulesDir' => '', // default Modules
        //  'controllersDir' => '', // default Controllers
        //  'formsDir' => '', // default Forms
        //  'allow' => '', // IP, default only 127.0.0.1
        //  'baseController' => [], // default
        //  'baseModel' => [], // default
        //  'baseForm' => [], // default
        //  'baseModule' => '', // default
        //  'baseRoute' => '' // default empty
    )
    
Define only what you need and remove/comment the rest.
If you're fine with the default configuration, define only 'modulesPath' and 'migrationsPath'

Third Party
===========
* jQuery 1.11.3: https://jquery.org/ (MIT)
* jQuery UI 1.11.4 https://jqueryui.com/ (MIT)
* Bootstrap 3 http://getbootstrap.com/ (MIT)

