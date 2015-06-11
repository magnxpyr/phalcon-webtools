<?php
/*
  +------------------------------------------------------------------------+
  | Phalcon Developer Tools                                                |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2015 Phalcon Team (http://www.phalconphp.com)       |
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
  |          Serghei Iakovlev <sadhooklay@gmail.com>                       |
  +------------------------------------------------------------------------+
*/

namespace Tools\Builder;

use Tools\Builder\Version\Item as VersionItem;
use Tools\Builder\Mvc\Model\Migration as ModelMigration;
use Tools\Builder\Component;
use Tools\Helpers\Tools;

/**
 * Migrations Class
 *
 * @package     Tools
 * @copyright   Copyright (c) 2011-2015 Phalcon Team (team@phalconphp.com)
 * @license     New BSD License
 */
class Migrations
{
    /**
     * Generate migrations
     *
     * @param array $options
     * @throws \Exception
     */
    public static function generate(array $options)
    {
        $tableName = $options['tableName'];
        $exportData = $options['exportData'];
        $migrationsDir = $options['migrationsDir'];
        $originalVersion = $options['originalVersion'];
        $force = $options['force'];
        $config = $options['config'];

        if ($migrationsDir && !file_exists($migrationsDir)) {
            mkdir($migrationsDir, 0777, true);
        }

        if ($originalVersion) {
            if (!preg_match('/[a-z0-9](\.[a-z0-9]+)*/', $originalVersion, $matches)) {
                throw new \Exception('Version '.$originalVersion.' is invalid');
            }

            $originalVersion = $matches[0];
            $version = new VersionItem($originalVersion, 3);
            if (file_exists($migrationsDir.'/'.$version) && !$force) {
                throw new \Exception('Version '.$version.' is already generated');
            }
        } else {
            $versions = array();
            $iterator = new \DirectoryIterator($migrationsDir);
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isDir()) {
                    if (preg_match('/[a-z0-9](\.[a-z0-9]+)+/', $fileInfo->getFilename(), $matches)) {
                        $versions[] = new VersionItem($matches[0], 3);
                    }
                }
            }

            if (count($versions) == 0) {
                $version = new VersionItem('1.0.0');
            } else {
                $version = VersionItem::maximum($versions);
                $version = $version->addMinor(1);
            }
        }

        if (!file_exists($migrationsDir.'/'.$version)) {
            if(!@mkdir($migrationsDir.'/'.$version)) {
                throw new \Exception("Cannot create migration version directory");
            }
            @chmod($migrationsDir.'/'.$version, 0777);
        }

        if (isset($config->database)) {
            ModelMigration::setup($config->database);
        } else {
            throw new \Exception("Cannot load database configuration");
        }

        ModelMigration::setSkipAutoIncrement($options['no-ai']);
        ModelMigration::setMigrationPath($migrationsDir.'/'.$version);
        if ($tableName == 'all') {
            $migrations = ModelMigration::generateAll($version, $exportData);
            foreach ($migrations as $tableName => $migration) {
                file_put_contents($migrationsDir.'/'.$version.'/'.$tableName.'.php', '<?php'.PHP_EOL.Tools::getCopyright().PHP_EOL.PHP_EOL.$migration);
                @chmod($migrationsDir.'/'.$version.'/'.$tableName.'.php', 0777);
            }
        } else {
            $migration = ModelMigration::generate($version, $tableName, $exportData);
            file_put_contents($migrationsDir.'/'.$version.'/'.$tableName.'.php', '<?php '.PHP_EOL.Tools::getCopyright().PHP_EOL.PHP_EOL.$migration);
            @chmod($migrationsDir.'/'.$version.'/'.$tableName.'.php', 0777);
        }
    }

    /**
     * Run migrations
     *
     * @param array $options
     * @throws \Exception
     */
    public static function run(array $options)
    {
        $path = $options['migrationsDir'] . DIRECTORY_SEPARATOR . 'migration-version';
        $migrationsDir = $options['migrationsDir'];
        $config = $options['config'];
        $version = null ;

        if ( isset($options['version']) && $options['version'] !== null){
            $version = new VersionItem($options['version']);
        }

        if (isset($options['tableName'])) {
            $tableName = $options['tableName'];
        } else {
            $tableName = 'all';
        }

        if (!file_exists($migrationsDir)) {
            throw new \Exception('Migrations directory could not found');
        }

        $versions = array();
        $iterator = new \DirectoryIterator($migrationsDir);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir()) {
                if (preg_match('/[a-z0-9](\.[a-z0-9]+)+/', $fileinfo->getFilename(), $matches)) {
                    $versions[] = new VersionItem($matches[0], 3);
                }
            }
        }

        if ( count($versions) == 0) {
            throw new \Exception('Migrations were not found at '.$migrationsDir);
        } else {
            if ($version === null){
                $version = VersionItem::maximum($versions);
            }
        }

        if (file_exists($path)) {
            $fromVersion = trim(file_get_contents($path));
        } else {
            $fromVersion = null;
        }

        if (isset($config->database)) {
            ModelMigration::setup($config->database);
        } else {
            throw new \Exception("Cannot load database configuration");
        }

        ModelMigration::setMigrationPath($migrationsDir.'/'.$version . '/') ;
        $versionsBetween = VersionItem::between($fromVersion, $version, $versions);

	    // get rid of the current version, we don't want migrations to run for our
	    // existing version.
	    if (isset($versionsBetween[0]) && (string)$versionsBetween[0] == $fromVersion) {
		    unset($versionsBetween[0]);
	    }

        foreach ($versionsBetween as $version) {
            if ($tableName == 'all') {
                $iterator = new \DirectoryIterator($migrationsDir.'/'.$version);
                foreach ($iterator as $fileinfo) {
                    if ($fileinfo->isFile()) {
                        if (preg_match('/\.php$/', $fileinfo->getFilename())) {
                            ModelMigration::migrateFile((string) $version, $migrationsDir.'/'.$version.'/'.$fileinfo->getFilename());
                        }
                    }
                }
            } else {
                $migrationPath = $migrationsDir.'/'.$version.'/'.$tableName.'.php';
                if (file_exists($migrationPath)) {
                    ModelMigration::migrateFile((string) $version, $migrationPath);
                } else {
                    throw new \Exception('Migration class was not found '.$migrationPath);
                }
            }
        }

        file_put_contents($path, (string) $version);
        @chmod($path, 0777);
    }
}

