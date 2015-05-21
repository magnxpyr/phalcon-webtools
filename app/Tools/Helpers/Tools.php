<?php
/**
 * @copyright   2006 - 2015 Magnxpyr Network
 * @license     New BSD License; see LICENSE
 * @link        http://www.magnxpyr.com
 * @author      Stefan Chiriac <stefan@magnxpyr.com>
 */

namespace Tools\Helpers;

use Phalcon\Config;
use Phalcon\Di;
use Tools\Controllers\ControllerBase;

class Tools extends ControllerBase {

    /**
     * @var \Phalcon\Config
     */
    private static $_config;

    /**
     * @var \Phalcon\Config
     */
    private static $_toolsConfig;

    /**
     * @var \Phalcon\Mvc\Url
     */
    private static $_url;

    /**
     * @var \Phalcon\Mvc\Router
     */
    private static $_router;

    /**
     * @var \Phalcon\Mvc\Model
     */
    private static $_connection;

    /**
     * @var \Phalcon\Config
     */
    private static $_db;

    /**
     * Navigation
     *
     * @var array
     */
    private static $_options = array(
        'modules' => array(
            'caption' => 'Modules',
            'options' => array(
                'index' => array(
                    'caption' => 'Generate'
                ),
                'list' => array(
                    'caption' => 'List'
                )
            )
        ),
        'controllers' => array(
            'caption' => 'Controllers',
            'options' => array(
                'index' => array(
                    'caption' => 'Generate',
                )
            )
        ),
        'models' => array(
            'caption' => 'Models',
            'options' => array(
                'index' => array(
                    'caption' => 'Generate'
                )
            )
        ),
        'migrations' => array(
            'caption' => 'Migrations',
            'options' => array(
                'index' => array(
                    'caption' => 'Generate'
                ),
                'run' => array(
                    'caption' => 'Run'
                )
            )
        ),
        'scaffold' => array(
            'caption' => 'Scaffold',
            'options' => array(
                'index' => array(
                    'caption' => 'Generate'
                )
            )
        )
    );

    /**
     * Print navigation menu of the given controller
     *
     * @param  string $controllerName
     * @return string
     */
    public static function getNavMenu($controllerName) {
        $html = '';
        foreach (self::$_options as $controller => $option) {
            $ref = self::generateUrl($controller);
            if ($controllerName == $controller) {
                $html .= '<a class="list-group-item active"';
            } else {
                $html .= '<a class="list-group-item"';
            }
            $html .=  ' href="' . $ref . '">' . $option['caption'] . '</a>' . PHP_EOL;
        }
        return $html;
    }

    /**
     * Print menu of the given controller action
     *
     * @param  string $controllerName
     * @param  string $actionName
     * @return string
     */
    public static function getMenu($controllerName, $actionName) {
        $html = '';
        foreach (self::$_options[$controllerName]['options'] as $action => $option) {
            $ref = self::generateUrl($controllerName, $action);
            if ($actionName == $action) {
                $html .= '<li role="presentation" class="active"><a href="' . $ref . '">' . $option['caption'] . '</a></li>' . PHP_EOL;
            } else {
                $html .= '<li role="presentation"><a href="' . $ref . '">' . $option['caption'] . '</a></li>' . PHP_EOL;
            }
        }
        return $html;
    }

    /**
     * Tries to find the current configuration in the application
     *
     * @return mixed|\Phalcon\Config\Adapter\Ini
     * @throws \Exception
     */
    protected static function _getToolsConfig() {
        if(is_null(self::$_toolsConfig)) {
            $config = self::getConfig();
            if(!isset($config->tools))
                throw new \Exception ('Unable to find config file');
            if(is_string($config->tools))
                $config = new Config(require_once $config->tools);
            else
                $config = $config->tools;
            self::$_toolsConfig = $config;
        }

        return self::$_toolsConfig;
    }

    /**
     * Return the config object in the services container
     *
     * @return \Phalcon\Config
     */
    public static function getConfig() {
        if(is_null(self::$_config))
            self::$_config = Di::getDefault()->getShared('config');
        return self::$_config;
    }

    /**
     * Return the url object in the services container
     *
     * @return \Phalcon\Mvc\Url
     */
    public static function getUrl() {
        if(is_null(self::$_url))
            self::$_url = Di::getDefault()->getShared('url');
        return self::$_url;
    }

    /**
     * Return the router object in the services container
     *
     * @return \Phalcon\Mvc\Router
     */
    public static function getRouter() {
        if(is_null(self::$_router))
            self::$_router = Di::getDefault()->getShared('router');
        return self::$_router;
    }

    /**
     * Return the db object in the services container
     *
     * @return \Phalcon\Mvc\Model
     */
    public static function getConnection() {
        if(is_null(self::$_connection))
            self::$_connection = Di::getDefault()->getShared('db');
        return self::$_connection;
    }

    /**
     * Return the db config object in the services container
     *
     * @throws \Exception
     * @return \Phalcon\Mvc\Model
     */
    public static function getDb() {
        if(is_null(self::$_db)) {
            if(isset(self::getConfig()->database)) {
                $dbConfig = self::getConfig()->database;
            } elseif (isset(self::getConfig()->db)) {
                $dbConfig = self::getConfig()->db;
            } else {
                throw new \Exception("Database configuration cannot be loaded from your config file");
            }
            return $dbConfig;
        }
        return self::$_db;
    }

    /**
     * Return a new url according to params
     *
     * @param string $controller
     * @param string $action
     * @param string $params
     * @return string
     */
    public static function generateUrl($controller, $action = 'index', $params = null) {
        $baseUri = self::getUrl()->get();
        if(self::getRouter()->getMatchedRoute() !== null) {
            $uriPath = self::getRouter()->getMatchedRoute()->getPattern();
            return str_replace(array('//', ':controller', ':action', ':params'), array('/', $controller, $action, $params), $baseUri . $uriPath);
        } else {
            return $baseUri . "$controller/$action/$params";
        }
    }

    /**
     * Return an array with modules name
     *
     * @return array
     */
    public static function listModules() {
        $iterator = new \DirectoryIterator(self::getModulesPath());
        $modules = array();
        foreach($iterator as $fileinfo){
            if(!$fileinfo->isDot() && file_exists($fileinfo->getPathname() . '/Module.php')){
                $modules[] = $fileinfo->getFileName();
            }
        }
        return $modules;
    }

    /**
     * Return the modules input with all modules
     *
     * @param string $selected
     * @return string
     */
    public static function renderModulesInput($selected = null) {
        $iterator = new \DirectoryIterator(self::getModulesPath());
        $options = null;
        foreach($iterator as $fileinfo){
            if(!$fileinfo->isDot() && file_exists($fileinfo->getPathname() . '/Module.php')){
                $options .= '<option value=' . $fileinfo->getFileName() . '>';
                if($selected == null) $selected = $fileinfo->getFileName();
            }
        }

        $input = '<label class="control-label" for="name">Module</label>
                    <input list="module" name="module" value="' . $selected . '" class="form-control">
                    <datalist id="module">' . $options . '</datalist>';

        return $input;
    }

    /**
     * Return the base controllers input
     *
     * @return string
     */
    public static function renderControllersInput() {
        $classes = [];
        if(isset(self::_getToolsConfig()->baseController)) {
            $classes =  self::_getToolsConfig()->baseController;
        }

        $input = '<label class="control-label" for="name">Base Class</label>
                    <input list="baseClass" name="baseClass" ' . (!empty($classes) ? 'value="' . current($classes) . '"' : '') .' class="form-control">
                    <datalist id="baseClass">';

        foreach($classes as $class) {
            $input .= '<option value=' . $class . '>';
        }

        $input .= '</datalist>';

        return $input;
    }

    /**
     * Return the base models input
     *
     * @return string
     */
    public static function renderModelsInput() {
        $classes = [];
        if(isset(self::_getToolsConfig()->baseModel)) {
            $classes =  self::_getToolsConfig()->baseModel;
        }

        $input = '<label class="control-label" for="name">Base Class</label>
                    <input list="baseClass" name="baseClass" ' . (!empty($classes) ? 'value="' . current($classes) . '"' : '') .' class="form-control sticky-value">
                    <datalist id="baseClass">';

        foreach($classes as $class) {
            $input .= '<option value=' . $class . '>';
        }

        $input .= '</datalist>';

        return $input;
    }

    /**
     * Return an optional IP address for securing Phalcon Developers Tools area
     * @return string
     */
    public static function getToolsIp() {
        if(!empty(self::_getToolsConfig()->allow)) {
            return self::_getToolsConfig()->allow;
        }
        return '';
    }

    /**
     * Return the base controllers class
     * @return array
     */
    public static function getBaseController() {
        if(!empty(self::_getToolsConfig()->baseController)) {
            return self::_getToolsConfig()->baseController;
        }
        return array('Phalcon\Mvc\Controller');
    }

    /**
     * Return the base model class
     * @return array
     */
    public static function getBaseModel() {
        if(!empty(self::_getToolsConfig()->baseModel)) {
            return self::_getToolsConfig()->baseModel;
        }
        return array('Phalcon\Mvc\Model');
    }

    /**
     * Return the base form class
     * @return array
     */
    public static function getBaseForm() {
        if(!empty(self::_getToolsConfig()->baseForm)) {
            return self::_getToolsConfig()->baseForm;
        }
        return array('Phalcon\Mvc\Model');
    }

    /**
     * Return the base module class
     * @return string
     */
    public static function getBaseModule() {
        if(!empty(self::_getToolsConfig()->baseModule)) {
            return self::_getToolsConfig()->baseModule;
        }
        return '';
    }

    /**
     * Return the base route class
     * @return string
     */
    public static function getBaseRoute() {
        if(!empty(self::_getToolsConfig()->baseModule)) {
            return self::_getToolsConfig()->baseModule;
        }
        return '';
    }

    /**
     * Return the base namespace
     * @return string
     */
    public static function getBaseNamespace() {
        if(!empty(self::_getToolsConfig()->baseNamespace)) {
            return self::_getToolsConfig()->baseNamespace . '\\';
        }
        return '';
    }

    /**
     * Return the path to modules directory
     * @return string
     * @throws \Exception
     */
    public static function getModulesPath() {
        if(!empty(self::_getToolsConfig()->modulesPath)) {
            return self::_getToolsConfig()->modulesPath;
        }
        throw new \Exception('Modules path is not defined');
    }

    /**
     * Return the View directory
     * @return string
     */
    public static function getMigrationsPath() {
        if(!empty(self::_getToolsConfig()->migrationsPath)) {
            return self::_getToolsConfig()->migrationsPath;
        }
        throw new \Exception("Migrations path is not defined");
    }

    /**
     * Return the Controller directory
     * @return string
     */
    public static function getControllersDir() {
        if(!empty(self::_getToolsConfig()->controllersDir)) {
            return self::_getToolsConfig()->controllersDir;
        }
        return 'Controllers';
    }

    /**
     * Return the Model directory
     * @return string
     */
    public static function getModelsDir() {
        if(!empty(self::_getToolsConfig()->modelsDir)) {
            return self::_getToolsConfig()->modelsDir;
        }
        return 'Models';
    }

    /**
     * Return the Form directory
     * @return string
     */
    public static function getFormsDir() {
        if(!empty(self::_getToolsConfig()->formsDir)) {
            return self::_getToolsConfig()->formsDir;
        }
        return 'Forms';
    }

    /**
     * Return the View directory
     * @return string
     */
    public static function getViewsDir() {
        if(!empty(self::_getToolsConfig()->viewsDir)) {
            return self::_getToolsConfig()->viewsDir;
        }
        return 'Views';
    }

    /**
     * Return the copyright header
     * @return string
     */
    public static function getCopyright() {
        if(!empty(self::_getToolsConfig()->copyright)) {
            return self::_getToolsConfig()->copyright;
        }
        return '';
    }

    /**
     * Use minimal version
     * @return bool
     */
    public static function fullVersion() {
        if(!empty(self::_getToolsConfig()->full)) {
            return self::_getToolsConfig()->full;
        }
        return true;
    }
}