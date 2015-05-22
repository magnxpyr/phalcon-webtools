<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of namespaces
 */
$loader->registerNamespaces(
    array(
        'Tools' => __DIR__ . '/../Tools/'
    )
)->register();