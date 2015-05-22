<?php

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

/**
 * Register config
 */
$di->setShared('config', $config);

/**
 * Registering a dispatcher
 */
$di->set('dispatcher', function() {
    $dispatcher = new \Phalcon\Mvc\Dispatcher();
    $dispatcher->setDefaultNamespace('Tools\Controllers');
    return $dispatcher;
});

/**
 * Register routers
 */
$di->setShared('router', function () use ($config) {
    $router = new \Phalcon\Mvc\Router();
    $router->removeExtraSlashes(true);
    $router->setDefaults(array(
        'namespace' => 'Tools\Controllers',
        'controller' => 'index',
        'action' => 'index'
    ));
    $router->add('/:controller/:action/:params', array(
        'namespace' => 'Tools\Controllers',
        'controller' => 1,
        'action' => 2,
        'params' => 3
    ));

    return $router;
});

/**
 *  Register assets that will be loaded in every page
 */
$di->setShared('assets', function() {
    $assets = new \Phalcon\Assets\Manager();
    $assets
        ->collection('header-js')
        ->addJs('js/jquery-1.11.3.min.js')
        ->addJs('js/jquery-ui.min.js')
        ->addJs('js/bootstrap.min.js')
        ->addJs('js/mg.js');

    $assets
        ->collection('header-css')
        ->addCss('css/jquery-ui.min.css')
        ->addCss('css/bootstrap.min.css')
        ->addCss('css/style.css');

    return $assets;
});


/**
 * Register the flash service with custom CSS classes
 */
$di->setShared('flash', function() {
    return new \Phalcon\Flash\Session(array(
        'success' => 'alert alert-success',
        'notice'  => 'alert alert-info',
        'warning' => 'alert alert-warning',
        'error'   => 'alert alert-danger'
    ));
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
}, true);

/**
 * Setting up the view component
 */
$di->set('view', function () use ($config) {

    $view = new View();

    $view->setViewsDir($config->application->viewsDir);
    $view->setLayout('default');

    $view->registerEngines(array(
        '.volt' => function ($view, $di) use ($config) {

            $volt = new VoltEngine($view, $di);

            $volt->setOptions(array(
                'compiledPath' => $config->application->cacheDir,
                'compiledSeparator' => '_'
            ));

            return $volt;
        },
        '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
    ));

    return $view;
}, true);

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function () use ($config) {
    return new DbAdapter(array(
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname
    ));
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    $session = new SessionAdapter();
    $session->start();

    return $session;
});