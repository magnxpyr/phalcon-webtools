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
 * @authors     Stefan Chiriac <stefan@magnxpyr.com>
 */

namespace Tools\Builder;

use Phalcon\Db\Column;
use Phalcon\Text;
use Tools\Helpers\Tools;

/**
 * Class Model
 * @package Tools\Builder
 */
class Model extends Component
{
    /**
     * Model Construct
     * @param $options
     * @throws \Exception
     */
    public function __construct($options)
    {
        if (empty($options['name'])) {
            $options['name'] = Text::camelize($options['tableName']);
        }
        if (empty($options['directory'])) {
            $options['directory'] = Tools::getModulesPath() . $options['module'] .DIRECTORY_SEPARATOR. Tools::getModelsDir(). DIRECTORY_SEPARATOR;
        } else {
            $options['directory'] .= DIRECTORY_SEPARATOR;
        }
        if (empty($options['namespace']) || $options['namespace'] != 'None') {
            if(empty($options['module']))
                $options['namespace'] = Tools::getBaseNamespace() . Tools::getModelsDir();
            else
                $options['namespace'] = Tools::getBaseNamespace() . $options['module'] . '\\' . Tools::getModelsDir();
        }
        if (empty($options['baseClass'])) {
            $options['baseClass'] = 'Phalcon\Mvc\Model';
        }
        if (empty($options['tableName'])) {
            throw new \Exception("Please, specify the table name");
        }
        if (!isset($options['force'])) {
            $options['force'] = false;
        }
        $this->_options = $options;
    }

    /**
     * Returns the associated PHP type
     *
     * @param  string $type
     * @return string
     */
    public function getPHPType($type)
    {
        switch ($type) {
            case Column::TYPE_INTEGER:
                return 'integer';
                break;
            case Column::TYPE_DECIMAL:
            case Column::TYPE_FLOAT:
                return 'double';
                break;
            case Column::TYPE_DATE:
            case Column::TYPE_VARCHAR:
            case Column::TYPE_DATETIME:
            case Column::TYPE_CHAR:
            case Column::TYPE_TEXT:
                return 'string';
                break;
            default:
                return 'string';
                break;
        }
    }

    public function build()
    {
        $getSource = "
    public function getSource()
    {
        return '%s';
    }
";
        $templateThis = "        \$this->%s(%s);" . PHP_EOL;
        $templateRelation = "        \$this->%s('%s', '%s', '%s', %s);" . PHP_EOL;
        $templateSetter = "
    /**
     * Method to set the value of field %s
     *
     * @param %s \$%s
     * @return \$this
     */
    public function set%s(\$%s)
    {
        \$this->%s = \$%s;

        return \$this;
    }
";

        $templateValidateInclusion = "
        \$this->validate(
            new InclusionIn(
                array(
                    'field'    => '%s',
                    'domain'   => array(%s),
                    'required' => true,
                )
            )
        );";

        $templateValidateEmail = "
        \$this->validate(
            new Email(
                array(
                    'field'    => '%s',
                    'required' => true,
                )
            )
        );";

        $templateValidationFailed = "
        if (\$this->validationHasFailed() == true) {
            return false;
        }";

        $templateAttributes = "
    /**
     * @var %s
     */
    %s \$%s;
";

        $templateGetterMap = "
    /**
     * Returns the value of field %s
     *
     * @return %s
     */
    public function get%s()
    {
        if (\$this->%s) {
            return new %s(\$this->%s);
        } else {
           return null;
        }
    }
";

        $templateGetter = "
    /**
     * Returns the value of field %s
     *
     * @return %s
     */
    public function get%s()
    {
        return \$this->%s;
    }
";

        $templateValidations = "
    /**
     * Validations and business logic
     */
    public function validation()
    {
%s
    }
";

        $templateInitialize = "
    /**
     * Initialize method for model.
     */
    public function initialize()
    {
%s
    }
";

        $templateFind = "
    /**
     * @return %s[]
     */
    public static function find(\$parameters = array())
    {
        return parent::find(\$parameters);
    }

    /**
     * @return %s
     */
    public static function findFirst(\$parameters = array())
    {
        return parent::findFirst(\$parameters);
    }
";

        $templateUse = 'use %s;';
        $templateUseAs = 'use %s as %s;';

        $templateCode = "<?php
%s%s%s%s
class %s extends %s
{
%s
}
";

        $methodRawCode = array();
        $modelPath = $this->_options['directory'] . DIRECTORY_SEPARATOR . $this->_options['name'] . '.php';

        if (file_exists($modelPath)) {
            if (!$this->_options['force']) {
                throw new \Exception(
                    "The model file '" . $this->_options['name'] .
                    ".php' already exists in " . $this->_options['directory']
                );
            }
        }

        if(Tools::getDb()) {
            $dbConfig = Tools::getDb();
        }

        if (!isset($dbConfig->adapter)) {
            throw new \Exception(
                "Adapter was not found in the config. " .
                "Please specify a config variable [database][adapter]"
            );
        }

        if (isset($this->_options['namespace'])) {
            $namespace = PHP_EOL . PHP_EOL. 'namespace ' . $this->_options['namespace'] . ';'
                . PHP_EOL . PHP_EOL;
            $methodRawCode[] = sprintf($getSource, $this->_options['tableName']);
        } else {
            $namespace = '';
        }

        $useSettersGetters = $this->_options['genSettersGetters'];
        if (isset($this->_options['genDocMethods'])) {
            $genDocMethods = $this->_options['genDocMethods'];
        } else {
            $genDocMethods = false;
        }

        $this->isSupportedAdapter($dbConfig->adapter);

        if (isset($dbConfig->adapter)) {
            $adapter = $dbConfig->adapter;
        } else {
            $adapter = 'Mysql';
        }

        $configArray = $dbConfig->toArray();

        // An array for use statements
        $uses = array(sprintf($templateUse, $this->_options['baseClass']));

        $adapterName = '\Phalcon\Db\Adapter\Pdo\\' . $adapter;
        unset($configArray['adapter']);
        $db = new $adapterName($configArray);

        $initialize = array();
        if (isset($this->_options['schema'])) {
            if ($this->_options['schema'] != $dbConfig->dbname) {
                $initialize[] = sprintf(
                    $templateThis, 'setSchema', '"' . $this->_options['schema'] . '"'
                );
            }
            $schema = $this->_options['schema'];
        } elseif ($adapter == 'Postgresql') {
            $schema = 'public';
            $initialize[] = sprintf(
                $templateThis, 'setSchema', '"' . $schema . '"'
            );
        } else {
            $schema = $dbConfig->dbname;
        }

        if ($this->_options['name'] != $this->_options['tableName']) {
            $initialize[] = sprintf(
                $templateThis, 'setSource',
                '\'' . $this->_options['tableName'] . '\''
            );
        }

        $table = $this->_options['tableName'];
        if ($db->tableExists($table, $schema)) {
            $fields = $db->describeColumns($table, $schema);
        } else {
            throw new \Exception('Table "' . $table . '" does not exist');
        }

        foreach ($db->listTables() as $tableName) {
            foreach ($db->describeReferences($tableName) as $reference) {
                if ($reference->getReferencedTable() == $this->_options['tableName']) {
                    if (isset($this->_options['namespace'])) {
                        $entityNamespace = "{$this->_options['namespace']}\\";
                    } else {
                        $entityNamespace = '';
                    }
                    $initialize[] = sprintf(
                        $templateRelation,
                        'hasMany',
                        $reference->getReferencedColumns()[0],
                        $entityNamespace . ucfirst($tableName),
                        $reference->getColumns()[0],
                        "array('alias' => '" . ucfirst($tableName) . "')"
                    );
                }
            }
        }
        foreach ($db->describeReferences($this->_options['tableName']) as $reference) {
            if (isset($this->_options['namespace'])) {
                $entityNamespace = "{$this->_options['namespace']}\\";
            } else {
                $entityNamespace = '';
            }
            $initialize[] = sprintf(
                $templateRelation,
                'belongsTo',
                $reference->getColumns()[0],
                $entityNamespace . ucfirst($reference->getReferencedTable()),
                $reference->getReferencedColumns()[0],
                "array('alias' => '" . ucfirst($reference->getReferencedTable()) . "')"
            );
        }

        if (isset($this->_options['hasMany'])) {
            if (count($this->_options['hasMany'])) {
                foreach ($this->_options['hasMany'] as $relation) {
                    if (is_string($relation['fields'])) {
                        $entityName = $relation['camelizedName'];
                        if (isset($this->_options['namespace'])) {
                            $entityNamespace = "{$this->_options['namespace']}\\";
                            $relation['options']['alias'] = $entityName;
                        } else {
                            $entityNamespace = '';
                        }
                        $initialize[] = sprintf(
                            $templateRelation,
                            'hasMany',
                            $relation['fields'],
                            $entityNamespace . $entityName,
                            $relation['relationFields'],
                            $this->_buildRelationOptions( isset($relation['options']) ? $relation["options"] : NULL)
                        );
                    }
                }
            }
        }

        if (isset($this->_options['belongsTo'])) {
            if (count($this->_options['belongsTo'])) {
                foreach ($this->_options['belongsTo'] as $relation) {
                    if (is_string($relation['fields'])) {
                        $entityName = $relation['referencedModel'];
                        if (isset($this->_options['namespace'])) {
                            $entityNamespace = "{$this->_options['namespace']}\\";
                            $relation['options']['alias'] = $entityName;
                        } else {
                            $entityNamespace = '';
                        }
                        $initialize[] = sprintf(
                            $templateRelation,
                            'belongsTo',
                            $relation['fields'],
                            $entityNamespace . $entityName,
                            $relation['relationFields'],
                            $this->_buildRelationOptions(isset($relation['options']) ? $relation["options"] : NULL)
                        );
                    }
                }
            }
        }

        $alreadyInitialized = false;
        $alreadyValidations = false;
        if (file_exists($modelPath)) {
            try {
                $possibleMethods = array();
                if ($useSettersGetters) {
                    foreach ($fields as $field) {
                        $methodName = Text::camelize($field->getName());
                        $possibleMethods['set' . $methodName] = true;
                        $possibleMethods['get' . $methodName] = true;
                    }
                }

                require_once $modelPath;

                $linesCode = file($modelPath);
                $fullClassName = $this->_options['namespace'];
                $reflection = new \ReflectionClass($fullClassName);
                foreach ($reflection->getMethods() as $method) {
                    if ($method->getDeclaringClass()->getName() == $fullClassName) {
                        $methodName = $method->getName();
                        if (!isset($possibleMethods[$methodName])) {
                            $methodRawCode[$methodName] = join(
                                '',
                                array_slice(
                                    $linesCode,
                                    $method->getStartLine() - 1,
                                    $method->getEndLine() - $method->getStartLine() + 1
                                )
                            );
                        } else {
                            continue;
                        }
                        if ($methodName == 'initialize') {
                            $alreadyInitialized = true;
                        } else {
                            if ($methodName == 'validation') {
                                $alreadyValidations = true;
                            }
                        }
                    }
                }
            } catch (\ReflectionException $e) {
            }
        }

        $validations = array();
        foreach ($fields as $field) {
            if ($field->getType() === Column::TYPE_CHAR) {
                $domain = array();
                if (preg_match('/\((.*)\)/', $field->getType(), $matches)) {
                    foreach (explode(',', $matches[1]) as $item) {
                        $domain[] = $item;
                    }
                }
                if (count($domain)) {
                    $varItems = join(', ', $domain);
                    $validations[] = sprintf(
                        $templateValidateInclusion, $field->getName(), $varItems
                    );
                }
            }
            if ($field->getName() == 'email') {
                $validations[] = sprintf(
                    $templateValidateEmail, $field->getName()
                );
                $uses[] = sprintf($templateUse, '\Phalcon\Mvc\Model\Validator\Email');
            }
        }
        if (count($validations)) {
            $validations[] = $templateValidationFailed;
        }

        /**
         * Check if there have been any excluded fields
         */
        $exclude = array();
        if (isset($this->_options['excludeFields'])) {
            if (!empty($this->_options['excludeFields'])) {
                $keys = explode(',', $this->_options['excludeFields']);
                if (count($keys) > 0) {
                    foreach ($keys as $key) {
                        $exclude[trim($key)] = '';
                    }
                }
            }
        }

        $attributes = array();
        $setters = array();
        $getters = array();
        foreach ($fields as $field) {
            $type = $this->getPHPType($field->getType());
            if ($useSettersGetters) {

                if (!array_key_exists(strtolower($field->getName()), $exclude)) {
                    $attributes[] = sprintf(
                        $templateAttributes, $type, 'protected', $field->getName()
                    );
                    $setterName = Text::camelize($field->getName());
                    $setters[] = sprintf(
                        $templateSetter,
                        $field->getName(),
                        $type,
                        $field->getName(),
                        $setterName,
                        $field->getName(),
                        $field->getName(),
                        $field->getName()
                    );

                    if (isset($this->_typeMap[$type])) {
                        $getters[] = sprintf(
                            $templateGetterMap,
                            $field->getName(),
                            $type,
                            $setterName,
                            $field->getName(),
                            $this->_typeMap[$type],
                            $field->getName()
                        );
                    } else {
                        $getters[] = sprintf(
                            $templateGetter,
                            $field->getName(),
                            $type,
                            $setterName,
                            $field->getName()
                        );
                    }
                }
            } else {
                $attributes[] = sprintf(
                    $templateAttributes, $type, 'public', $field->getName()
                );
            }
        }

        if ($alreadyValidations == false) {
            if (count($validations) > 0) {
                $validationsCode = sprintf(
                    $templateValidations, join('', $validations)
                );
            } else {
                $validationsCode = '';
            }
        } else {
            $validationsCode = '';
        }

        if ($alreadyInitialized == false) {
            if (count($initialize) > 0) {
                $initCode = sprintf(
                    $templateInitialize,
                    rtrim(join('', $initialize))
                );
            } else {
                $initCode = '';
            }
        } else {
            $initCode = '';
        }

        $content = join('', $attributes);

        if ($useSettersGetters) {
            $content .= join('', $setters)
                . join('', $getters);
        }

        $content .= $validationsCode . $initCode;
        foreach ($methodRawCode as $methodCode) {
            $content .= $methodCode;
        }

        if ($genDocMethods) {
            $content .= sprintf($templateFind, $this->_options['name'], $this->_options['name']);
        }

        if (isset($this->_options['mapColumn'])) {
            $content .= $this->_genColumnMapCode($fields);
        }

        $str_use = implode(PHP_EOL, $uses) . PHP_EOL . PHP_EOL;
        $str_doc = '/**
 * Class ' . $this->_options['name'] . '
 * @package ' . $this->_options['namespace'] . '
 */';

        $base = explode('\\', $this->_options['baseClass']);
        $baseClass = end($base);

        $code = sprintf(
            $templateCode,
            Tools::getCopyright(),
            $namespace,
            $str_use,
            $str_doc,
            $this->_options['name'],
            $baseClass,
            $content
        );

        if(!@is_dir($this->_options['directory'])) {
            @mkdir($this->_options['directory']);
            @chmod($this->_options['directory'], 0777);
        }

        if (!@file_put_contents($modelPath, $code)) {
            throw new \Exception("Unable to write to '$modelPath'");
        }
        @chmod($modelPath, 0777);
    }

    /**
     * Builds a PHP syntax with all the options in the array
     * @param  array  $options
     * @return string PHP syntax
     */
    private function _buildRelationOptions($options)
    {
        if (empty($options)) {
            return 'NULL';
        }

        $values = array();
        foreach ($options as $name=>$val) {
            if (is_bool($val)) {
                $val = $val ? 'true':'false';
            } elseif (!is_numeric($val)) {
                $val = "'{$val}'";
            }

            $values[] = sprintf('\'%s\' => %s', $name, $val);
        }

        $syntax = 'array('. implode(',', $values). ')';

        return $syntax;
    }

    private function _genColumnMapCode($fields)
    {
        $template = '
    /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
        return array(
            %s
        );
    }
';
        $contents = array();
        foreach ($fields as $field) {
            $name = $field->getName();
            $contents[] = sprintf('\'%s\' => \'%s\'', $name, $name);
        }

        return sprintf($template, join(", \n            ", $contents));
    }
}