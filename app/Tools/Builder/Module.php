<?php
/**
 * @copyright   2006 - 2015 Magnxpyr Network
 * @license     New BSD License; see LICENSE
 * @link        http://www.magnxpyr.com
 * @author      Stefan Chiriac <stefan@magnxpyr.com>
 */

namespace Tools\Builder;

use Phalcon\Exception;
use Tools\Helpers\Tools;

class Module extends Component {

    /**
     * Controller constructor
     *
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options) {
        if (empty($options['name'])) {
            throw new \Exception("Please specify the module name");
        }
        if (empty($options['directory'])) {
            $options['directory'] = Tools::getModulesPath() . $options['name'] . DIRECTORY_SEPARATOR;
        } else {
            $options['directory'] .= DIRECTORY_SEPARATOR;
        }
        if (empty($options['namespace']) || $options['namespace'] != 'None') {
            $options['namespace'] = Tools::getBaseNamespace() . $options['name'];
        }
        if (empty($options['routes'])) {
            $options['routes'] = false;
        }
        if (empty($options['force'])) {
            $options['force'] = false;
        }
        $this->_options = $options;
    }

    /**
     * Build the controller
     *
     * @return string
     * @throws \Exception
     */
    public function build() {
        if (!is_dir($this->_options['directory']) || $this->_options['force'] == true) {
            if (!is_dir($this->_options['directory'])) {
                if(!@mkdir($this->_options['directory']))
                    throw new \Exception("Unable to create module directory!");
                @chmod($this->_options['directory'], 0777);
            }
        } else {
            throw new \Exception("Module directory already exists!");
        }

        if(!is_dir($this->_options['directory'] . Tools::getControllersDir())) {
            if (!@mkdir($this->_options['directory'] . Tools::getControllersDir()))
                throw new \Exception("Unable to create controller directory!");
            @chmod($this->_options['directory'] . Tools::getControllersDir(), 0777);
        }

        $controller = new Controller(array(
            'module' => $this->_options['name'],
            'name' => 'IndexController',
            'namespace' => null,
            'baseClass' => current(Tools::getBaseController()),
            'directory' => $this->_options['directory'] . DIRECTORY_SEPARATOR . Tools::getControllersDir(),
            'force' => $this->_options['force']
        ));
        $controller->build();

        if(!is_dir($this->_options['directory'] . Tools::getModelsDir())) {
            if (!@mkdir($this->_options['directory'] . Tools::getModelsDir()))
                throw new \Exception("Unable to create model directory!");
            @chmod($this->_options['directory'] . Tools::getModelsDir(), 0777);
        }

        if(!is_dir($this->_options['directory'] . Tools::getFormsDir())) {
            if (!@mkdir($this->_options['directory'] . Tools::getFormsDir()))
                throw new \Exception("Unable to create form directory!");
            @chmod($this->_options['directory'] . Tools::getFormsDir(), 0777);
        }

        if(!is_dir($this->_options['directory'] . Tools::getViewsDir())) {
            if (!@mkdir($this->_options['directory'] . Tools::getViewsDir()))
                throw new \Exception("Unable to create controller directory!");
            @chmod($this->_options['directory'] . Tools::getViewsDir(), 0777);
        }

        $view = new View(array(
            'name' => 'IndexController',
            'module' => $this->_options['name'],
            'force' => $this->_options['force']
        ));
        $view->build();

        if($this->_options['routes']) {
            $this->_createRoute();
        }

        $this->_createModule();
    }

    private function _createRoute() {

        $code = "<?php\n".Tools::getCopyright()."\n\nnamespace ".$this->_options['namespace'].';'.PHP_EOL.PHP_EOL;
        if(!empty(Tools::getBaseRoute())) {
            $base = explode('\\', Tools::getBaseRoute());
            $baseClass = end($base);

            $useClass = 'use '.Tools::getBaseRoute().';'.PHP_EOL.PHP_EOL;
            $code .= $useClass;
        }

        $code .= "class Routes";
        if(!empty(Tools::getBaseRoute())) {
            $code .= " extends $baseClass";
        }
        $code .= " {\n\n\tpublic function init(\$router) {
        \$router->add('/:module/:controller/:action/:params', array(
            'module' => 1,
            'controller' => 2,
            'action' => 3,
            'params' => 4
        ));\n\t}\n}";
        $code = str_replace("\t", "    ", $code);

        $routePath = $this->_options['directory'] . DIRECTORY_SEPARATOR . 'Routes.php';
        if (!file_exists($routePath) || $this->_options['force'] == true) {
            if (!@file_put_contents($routePath, $code)) {
                throw new \Exception("Unable to write to '$routePath'");
            }
            @chmod($routePath, 0777);
        } else {
            throw new \Exception("Routes.php file already exists");
        }
    }

    private function _createModule() {
        $code = "<?php\n".Tools::getCopyright().PHP_EOL.PHP_EOL.'namespace ' .$this->_options['namespace'].';'.PHP_EOL.PHP_EOL;

        if(Tools::fullVersion()) {
            $code .= 'use Phalcon\Loader;'.PHP_EOL.'use Phalcon\Mvc\View;
                use Phalcon\Mvc\Dispatcher;'.PHP_EOL.'use Phalcon\Mvc\ModuleDefinitionInterface;';
        }

        if(!empty(Tools::getBaseModule())) {
            $base = explode('\\', Tools::getBaseModule());
            $baseClass = end($base);

            $useClass = 'use '.Tools::getBaseModule().';'.PHP_EOL.PHP_EOL;
            $code .= $useClass;
        }

        $code .= "class Module";
        if(!empty(Tools::getBaseModule())) {
            $code .= " extends $baseClass";
        }
        if(Tools::fullVersion()) {
            $code .= " implements ModuleDefinitionInterface {\n\n\t/**
             * Register a specific autoloader for the module
             */
            public function registerAutoloaders(DiInterface \$di = null) {

                \$loader = new Loader();
                \$loader->registerNamespaces(
                    array(" . PHP_EOL .
                        $this->_options['namespace'] . Tools::getControllersDir() . " => " .
                        $this->_options['directory'] . Tools::getControllersDir() . PHP_EOL . "," .
                        $this->_options['namespace'] . Tools::getControllersDir() . " => " .
                        $this->_options['directory'] . Tools::getModelsDir() . PHP_EOL . "," .
                        ")
                );
                \$loader->register();
            }

            /**
             * Register specific services for the module
             */
            public function registerServices(DiInterface \$di) {

                //Registering a dispatcher
                \$di->set('dispatcher', function() {
                    \$dispatcher = new Dispatcher();
                    \$dispatcher->setDefaultNamespace(" . $this->_options['namespace'] . ");
                    return \$dispatcher;
                });

                //Registering the view component
                \$di->set('view', function() {
                    \$view = new View();
                    \$view->setViewsDir('" . $this->_options['directory'] . Tools::getViewsDir() . "');
                    return \$view;
                });
            }\n}";
        } else {
            $code .= " {" . PHP_EOL . PHP_EOL . "}";
        }

        $code = str_replace("\t", "    ", $code);

        $modulePath = $this->_options['directory'] . DIRECTORY_SEPARATOR . 'Module.php';
        if (!file_exists($modulePath) || $this->_options['force'] == true) {
            if (!@file_put_contents($modulePath, $code)) {
                throw new \Exception("Unable to write to '$modulePath'");
            }
            @chmod($modulePath, 0777);
        } else {
            throw new \Exception("Module.php file already exists");
        }
    }
}