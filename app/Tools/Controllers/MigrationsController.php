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
namespace Tools\Controllers;

use Tools\Builder\Migrations;
use Tools\Helpers\Tools;

/**
 * Class MigrationsController
 * @package Tools\Controllers
 */
class MigrationsController extends ControllerBase
{
    /**
     * @throws \Exception
     * @return string
     */
    protected function _getMigrationsDir()
    {
        $migrationsDir = Tools::getMigrationsPath();
        if (!file_exists($migrationsDir)) {
            if(!@mkdir($migrationsDir)) {
                throw new \Exception("Unable to create migration directory on ".Tools::getMigrationsPath());
            }
            @chmod($migrationsDir, 0777);
        }

        return $migrationsDir;
    }

    /**
     * @throws \Exception
     */
    protected function _prepareVersions()
    {
        $migrationsDir = $this->_getMigrationsDir();

        $folders = array();

        $iterator = new \DirectoryIterator($migrationsDir);
        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isDot() && $fileinfo->isDir()) {
                $folders[$fileinfo->getFileName()] = $fileinfo->getFileName();
            }
        }

        natsort($folders);
        $folders = array_reverse($folders);
        $foldersKeys = array_keys($folders);

        if (isset($foldersKeys[0])) {
            $this->view->setVar('version', $foldersKeys[0]);
        } else {
            $this->view->setVar('version', 'None');
        }
    }

    public function indexAction()
    {
        $this->_prepareVersions();
        $this->listTables(true);
    }

    /**
     * Generates migrations
     */
    public function generateAction()
    {
        if ($this->request->isPost()) {
            $exportData = '';
            $tableName = $this->request->getPost('table-name', 'string');
            $version = $this->request->getPost('version', 'string');
            $noAi = $this->request->getPost('noAi', 'int');
            $force = $this->request->getPost('force', 'int');

            $migrationsDir = $this->_getMigrationsDir();

            try {
                Migrations::generate(array(
                    'config' => Tools::getConfig(),
                    'directory' => null,
                    'tableName' => $tableName,
                    'exportData' => $exportData,
                    'migrationsDir' => $migrationsDir,
                    'originalVersion' => $version,
                    'force' => $force,
                    'no-ai' => $noAi
                ));

                $this->flash->success("The migration was generated successfully");
            } catch (\Exception $e) {
             $this->flash->error($e->getMessage());
            }
        }

        $this->dispatcher->forward(array(
         'action' => 'index'
        ));
    }

    /**
     * Run Migrations
     */
    public function runAction()
    {
        if ($this->request->isPost()) {
            $version = '';
            $exportData = '';
            $force = $this->request->getPost('force', 'int');

            try {
                $migrationsDir = $this->_getMigrationsDir();

                Migrations::run(array(
                    'config' => Tools::getConfig(),
                    'directory' => null,
                    'tableName' => 'all',
                    'migrationsDir' => $migrationsDir,
                    'force' => $force
                ));

                $this->flash->success("The migration was executed successfully");
            } catch (\Exception $e) {
                $this->flash->error($e->getMessage());
            }
        }

        $this->_prepareVersions();
    }
}
