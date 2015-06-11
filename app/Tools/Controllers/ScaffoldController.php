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
/**
 * @copyright   2006 - 2015 Magnxpyr Network
 * @license     New BSD License; see LICENSE
 * @link        http://www.magnxpyr.com
 * @author      Stefan Chiriac <stefan@magnxpyr.com>
 */

namespace Tools\Controllers;

use Tools\Builder\Scaffold;

/**
 * Class ScaffoldController
 * @package Tools\Controllers
 */
class ScaffoldController extends ControllerBase
{
    /**
     * Scaffold form
     */
    public function indexAction()
    {
        $this->listTables();
        $this->view->templateEngines = array(
            'volt' => 'volt',
            'php' => 'php'
        );

        $selectedModule = null;
        $params = $this->router->getParams();
        if(!empty($params))
            $selectedModule = $this->router->getParams()[0];
        $this->view->selectedModule = $selectedModule;
    }

    /**
     * Generate Scaffold Action
     */
    public function createAction()
    {
        if ($this->request->isPost()) {
            $module = $this->request->getPost('module', 'string');
            $schema = $this->request->getPost('schema', 'string');
            $tableName = $this->request->getPost('tableName', 'string');
            $version = $this->request->getPost('version', 'string');
            $templateEngine = $this->request->getPost('templateEngine');
            $force = $this->request->getPost('force', 'int');
            $genSettersGetters = $this->request->getPost('genSettersGetters', 'int');

            try {
                $scaffoldBuilder = new Scaffold(array(
                    'module' => $module,
                    'name' => null,
                    'tableName' => $tableName,
                    'schema' => $schema,
                    'force'	=> $force,
                    'genSettersGetters' => $genSettersGetters,
                    'directory' => null,
                    'templatePath' => __DIR__ . '/../Builder/templates/',
                    'templateEngine' => $templateEngine,
                    'modelsDir' => null,
                    'modelsNamespace' => null,
                    'controllersNamespace' => null,
                    'controllersDir' => null
                ));

                $scaffoldBuilder->build();

                $this->flash->success('Scaffold for table "'.$tableName.'" was generated successfully');

            } catch (\Exception $e) {
                $this->flash->error($e->getMessage());
            }
        }

        $this->dispatcher->forward(array(
            'action' => 'index'
        ));
    }
}
