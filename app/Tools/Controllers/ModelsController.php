<?php
/**
 * @copyright   2006 - 2015 Magnxpyr Network
 * @license     New BSD License; see LICENSE
 * @link        http://www.magnxpyr.com
 * @author      Stefan Chiriac <stefan@magnxpyr.com>
 */

namespace Tools\Controllers;

use Tools\Builder\AllModels;
use Tools\Builder\Model;
use Tools\Helpers\Tools;

/**
 * Class ModelsController
 * @package Tools\Controllers
 */
class ModelsController extends ControllerBase
{
    /**
     * @throws \Exception
     */
    public function indexAction()
    {
        $selectedModule = null;
        $params = $this->router->getParams();
        if(!empty($params))
            $selectedModule = $this->router->getParams()[0];
        $this->view->selectedModule = $selectedModule;
        $this->view->directoryPath = Tools::getModulesPath() . $selectedModule . Tools::getModelsDir();

        $this->listTables(true);
    }

    /**
     * Generate models
     */
    public function createAction()
    {
        if ($this->request->isPost()) {

            $name = $this->request->getPost('name', 'string');
            $module = $this->request->getPost('module', 'string');
            $force = $this->request->getPost('force', 'int');
            $schema = $this->request->getPost('schema', 'string');
            $directory = $this->request->getPost('directory', 'string');
            $namespace = $this->request->getPost('namespace', 'string');
            $baseClass = $this->request->getPost('baseClass', 'string');
            $tableName = $this->request->getPost('tableName', 'string');
            $genSettersGetters = $this->request->getPost('genSettersGetters', 'int');
            $foreignKeys = $this->request->getPost('foreignKeys', 'int');
            $defineRelations = $this->request->getPost('defineRelations', 'int');

            try {
                $component = array(
                    'module' => $module,
                    'name' => $name,
                    'baseClass' => $baseClass,
                    'tableName' => $tableName,
                    'schema' => $schema,
                    'force' => $force,
                    'directory' => $directory,
                    'foreignKeys' => $foreignKeys,
                    'defineRelations' => $defineRelations,
                    'genSettersGetters' => $genSettersGetters,
                    'namespace' => $namespace,
                );

                if ($tableName == 'all')
                    $modelBuilder = new AllModels($component);
                else
                    $modelBuilder = new Model($component);

                $modelBuilder->build();

                if ($tableName == 'all') {
                    if (($n = count($modelBuilder->exist)) > 0) {
                        $mList = implode('</strong>, <strong>', $modelBuilder->exist);

                        if ($n == 1) {
                            $notice = 'Model <strong>' . $mList . '</strong> was skipped because it already exists!';
                        } else {
                            $notice = 'Models <strong>' . $mList . '</strong> were skipped because they already exists!';
                        }

                        $this->flash->notice($notice);
                    }
                }

                if ($tableName == 'all') {
                    $this->flash->success('Models were created successfully');
                } else {
                    $this->flash->success('Model "'.$tableName.'" was created successfully');
                }
            } catch (\Exception $e) {
                $this->flash->error($e->getMessage());
            }
        }

        $this->dispatcher->forward(array(
            'action' => 'index'
        ));
    }
}
