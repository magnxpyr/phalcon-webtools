<?php
/*
  +------------------------------------------------------------------------+
  | Phalcon Developer Tools                                                |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2014 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

namespace Tools\Builder;

/**
 * Tools\Builder\Component
 *
 * Base class for builder components
 *
 * @category 	Tools
 * @package 	Builder
 * @subpackage  Component
 * @copyright   Copyright (c) 2011-2014 Phalcon Team (team@phalconphp.com)
 * @license 	New BSD License
 */
abstract class Component
{
    /**
     * @var array
     */
    protected $_options = array();

    /**
     * @param $options
     */
    public function __construct($options)
    {
        $this->_options = $options;
    }

    /**
     * Check if a path is absolute
     *
     * @param $path
     * @return bool
     */
    public function isAbsolutePath($path)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (preg_match('/^[A-Z]:\\\\/', $path)) {
                return true;
            }
        } else {
            if (substr($path, 0, 1) == DIRECTORY_SEPARATOR) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the current adapter is supported by Phalcon
     *
     * @param  string $adapter
     * @throws \Exception
     */
    public function isSupportedAdapter($adapter)
    {
        if (!class_exists('\Phalcon\Db\Adapter\Pdo\\' . $adapter)) {
            throw new \Exception("Adapter $adapter is not supported");
        }
    }

    abstract public function build();
}