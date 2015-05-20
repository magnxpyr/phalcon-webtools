<?php

return new \Phalcon\Config(array(
    'database' => array(
        'adapter'     => 'Mysql',
        'host'        => 'localhost',
        'username'    => 'root',
        'password'    => 'bingo',
        'dbname'      => 'test',
        'charset'     => 'utf8',
    ),
    'application' => array(
        'controllersDir' => __DIR__ . '/../../app/Tools/Controllers/',
        'migrationsDir'  => __DIR__ . '/../../app/migrations/',
        'viewsDir'       => __DIR__ . '/../../app/Tools/Views/',
        'cacheDir'       => __DIR__ . '/../../app/cache/',
        'baseUri'        => '/phalcon-webtools/',
    ),
    'tools' => array(
        'copyright' => "",
        'modulesPath' => __DIR__ . '/../../app/Tools/',
        'migrationsPath' => __DIR__ . '/../../app/migrations/',
 //   'viewsDir' => '',
//    'modulesDir' => '',
//    'controllersDir' => '',
//    'formsDir' => '',
//    'allow' => '',
        'baseController' => [],
        'baseModel' => [],
        'baseForm' => [],
   //     'baseModule' => '',
 //   'baseRoute' => ''
 //       'full' => false
    )
));
