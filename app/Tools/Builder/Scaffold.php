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

namespace Tools\Builder;

use Phalcon\Text;
use Tools\Builder\Model as ModelBuilder;
use Phalcon\DI\FactoryDefault;
use Phalcon\Db\Column;
use Tools\Helpers\Tools;

/**
 * Class Scaffold
 * @package Tools\Builder
 */
class Scaffold extends Component
{
    /**
     * Scaffold Construct
     * @param $options
     * @throws \Exception
     */
    public function __construct($options)
    {
        if (empty($options['tableName'])) {
            throw new \Exception("Please, specify the table name");
        }
        if (empty($options['name'])) {
            $options['name'] = Text::camelize($options['tableName']);
        }
        if (empty($options['controllersDir'])) {
            $options['controllersDir'] = Tools::getModulesPath() . $options['module'] .DIRECTORY_SEPARATOR. Tools::getControllersDir().DIRECTORY_SEPARATOR;
        } else {
            $options['controllersDir'] .= DIRECTORY_SEPARATOR;
        }
        if (empty($options['controllersNamespace']) || $options['namespace'] != 'None') {
            if(empty($options['module']))
                $options['controllersNamespace'] = Tools::getBaseNamespace() . Tools::getControllersDir();
            else
                $options['controllersNamespace'] = Tools::getBaseNamespace() . $options['module'] . '\\' . Tools::getControllersDir();
        }
        if (empty($options['modelsDir'])) {
            $options['modelsDir'] = Tools::getModulesPath() . $options['module'] .DIRECTORY_SEPARATOR. Tools::getModelsDir().DIRECTORY_SEPARATOR;
        } else {
            $options['modelsDir'] .= DIRECTORY_SEPARATOR;
        }
        if (empty($options['modelsNamespace']) || $options['namespace'] != 'None') {
            if(empty($options['module']))
                $options['modelsNamespace'] = Tools::getBaseNamespace() . Tools::getModelsDir();
            else
                $options['modelsNamespace'] = Tools::getBaseNamespace() . $options['module'] . '\\' . Tools::getModelsDir();
        }
        if (empty($options['viewsDir'])) {
            $options['viewsDir'] = Tools::getModulesPath() . $options['module'] .DIRECTORY_SEPARATOR. Tools::getViewsDir().DIRECTORY_SEPARATOR;
        } else {
            $options['viewsDir'] .= DIRECTORY_SEPARATOR;
        }
        if (empty($options['baseClass'])) {
            $options['baseClass'] = 'Phalcon\Mvc\Model';
        }
        if (!isset($options['force'])) {
            $options['force'] = false;
        }
        $this->_options = $options;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function build()
    {
        $options = $this->_options;

        if(Tools::getDb()) {
            $config = Tools::getDb();
        }

        if (!isset($config->adapter)) {
            throw new \Exception("Adapter was not found in the config. Please specify a config variable [database][adapter]");
        }

        $adapter = ucfirst($config->adapter);

        $this->isSupportedAdapter($adapter);

        $di = new FactoryDefault();

        $di->set('db', function () use ($adapter, $config) {

            if (isset($config->adapter)) {
                $adapter = $config->adapter;
            } else {
                $adapter = 'Mysql';
            }

            if (is_object($config)) {
                $configArray = $config->toArray();
            } else {
                $configArray = $config;
            }

            $adapterName = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;
            unset($configArray['adapter']);

            return new $adapterName($configArray);
        });

        $options['manager'] = $di->getShared('modelsManager');

        $options['className'] = $options['name'];
        $options['fileName'] = str_replace('_', '-', Text::uncamelize($options['className']));

        $modelsNamespace = $options['modelsNamespace'];
        if (isset($modelsNamespace) && substr($modelsNamespace, -1) !== '\\') {
            $modelsNamespace .= "\\";
        }

        $modelName = $options['name'];
        $modelClass = $modelsNamespace . $modelName;
        if(!@dir($options['modelsDir'])) {
            if(!@mkdir($options['modelsDir'])) {
                throw new \Exception('Could not create directory on '. $options['modelsDir']);
            }
            @chmod($options['modelsDir'], 0777);
        }
        $modelPath = $options['modelsDir']. DIRECTORY_SEPARATOR .$modelName.'.php';
        if (!file_exists($modelPath)) {
            $modelBuilder = new ModelBuilder(array(
                'module' => $options['module'],
                'name' => null,
                'tableName' => $options['tableName'],
                'schema' => $options['schema'],
                'baseClass' => null,
                'namespace' => $options['modelsNamespace'],
                'foreignKeys' => true,
                'defineRelations' => true,
                'genSettersGetters' => $options['genSettersGetters'],
                'directory' => $options['modelsDir'],
                'force' => $options['force']
            ));

            $modelBuilder->build();
        }

        if (!class_exists($modelClass)) {
            require_once $modelPath;
        }

        $entity = new $modelClass();

        $metaData = $di['modelsMetadata'];

        $attributes = $metaData->getAttributes($entity);
        $dataTypes = $metaData->getDataTypes($entity);
        $identityField = $metaData->getIdentityField($entity);
        $primaryKeys = $metaData->getPrimaryKeyAttributes($entity);

        $setParams = array();
        $selectDefinition = array();

        $relationField = '';

        $options['name'] 				 = Text::uncamelize($options['name']);
        $options['plural'] 				 = $this->_getPossiblePlural($options['name']);
        $options['singular']			 = $this->_getPossibleSingular($options['name']);
        $options['modelClass']           = $options['modelsNamespace'] . '\\' . $this->_options['name'];
        $options['entity']				 = $entity;
        $options['setParams'] 			 = $setParams;
        $options['attributes'] 			 = $attributes;
        $options['dataTypes'] 			 = $dataTypes;
        $options['primaryKeys']          = $primaryKeys;
        $options['identityField']		 = $identityField;
        $options['relationField'] 		 = $relationField;
        $options['selectDefinition']	 = $selectDefinition;
        $options['autocompleteFields'] 	 = array();
        $options['belongsToDefinitions'] = array();

        //Build Controller
        $this->_makeController($options);

        if (isset($options['templateEngine']) && $options['templateEngine'] == 'volt') {
            //View layouts
            //    $this->_makeLayoutsVolt($options);

            //View index.phtml
            $this->_makeViewIndexVolt(null, $options);

            //View search.phtml
            $this->_makeViewSearchVolt(null, $options);

            //View new.phtml
            $this->_makeViewNewVolt(null, $options);

            //View edit.phtml
            $this->_makeViewEditVolt(null, $options);
        } else {
            //View layouts
            //     $this->_makeLayouts(null, $options);

            //View index.phtml
            $this->_makeViewIndex(null, $options);

            //View search.phtml
            $this->_makeViewSearch(null, $options);

            //View new.phtml
            $this->_makeViewNew(null, $options);

            //View edit.phtml
            $this->_makeViewEdit(null, $options);
        }

        return true;
    }

    private function _findDetailField($entity)
    {
        $posible = array('name');
        $attributes = $entity::getAttributes();
        foreach ($attributes as $attribute) {
            if (in_array($attribute, $posible)) {
                return $attribute;
            }
        }

        return $attributes[0];
    }

    /**
     * @param $fieldName
     *
     * @return string
     */
    private function _getPossibleLabel($fieldName)
    {
        $fieldName = preg_replace('/_id$/', '', $fieldName);
        $fieldName = preg_replace('/_at$/', '', $fieldName);
        $fieldName = preg_replace('/_in$/', '', $fieldName);
        $fieldName = str_replace('_', ' of ', $fieldName);

        return ucwords($fieldName);
    }

    /**
     * @param $className
     *
     * @return string
     */
    private function _getPossibleSingular($className)
    {
        if (substr($className, strlen($className) - 1, 1) == 's') {
            return substr($className, 0, strlen($className) - 1);
        } else {
            return $className;
        }
    }

    /**
     * @param $className
     *
     * @return mixed
     */
    private function _getPossiblePlural($className)
    {
        if (substr($className, strlen($className) - 1, 1) == 's') {
            return $className;
        }

        return $className;
    }

    /**
     * @param $type
     *
     * @return string
     * @throws \Exception
     */
    private function _resolveType($type)
    {
        switch ($type) {
            case Column::TYPE_INTEGER:
                return 'integer';
                break;
            case Column::TYPE_DECIMAL:
                return 'decimal';
                break;
            case Column::TYPE_FLOAT:
                return 'float';
                break;
            case Column::TYPE_DATE:
                return 'date';
                break;
            case Column::TYPE_VARCHAR:
                return 'varchar';
                break;
            case Column::TYPE_DATETIME:
                return 'datetime';
                break;
            case Column::TYPE_CHAR:
                return 'char';
                break;
            case Column::TYPE_TEXT:
                return 'text';
                break;
            default:
                throw new \Exception('Data type could not be resolved');
        }
    }

    /**
     * @param $var
     * @param $fields
     * @param $useGetSetters
     * @param $identityField
     *
     * @return string
     */
    private function _captureFilterInput($var, $fields, $useGetSetters, $identityField)
    {
        $code = '';
        foreach ($fields as $field => $dataType) {

            if ($field == $identityField) {
                continue;
            }

            if (strpos($dataType, 'int') !== false) {
                $fieldCode = '$this->request->getPost("'.$field.'", "int")';
            } else {
                if ($field == 'email') {
                    $fieldCode = '$this->request->getPost("'.$field.'", "email")';
                } else {
                    $fieldCode = '$this->request->getPost("'.$field.'", "string")';
                }
            }

            $code .= '$'.$var.'->';
            if ($useGetSetters) {
                $code .= 'set' . Text::camelize($field) . '(' . $fieldCode . ')';
            } else {
                $code .= $field . ' = ' . $fieldCode;
            }

            $code .= ';' . PHP_EOL . "\t\t";
        }

        return $code;
    }

    /**
     * @param $var
     * @param $fields
     * @param $useGetSetters
     *
     * @return string
     */
    private function _assignTagDefaults($var, $fields, $useGetSetters)
    {
        $code = '';
        foreach ($fields as $field => $dataType) {

            if ($useGetSetters) {
                $accessor = 'get' . Text::camelize($field) . '()';
            } else {
                $accessor = $field;
            }

            $code .= '$this->tag->setDefault("' . $field . '", $' . $var . '->' . $accessor . ');' . PHP_EOL . "\t\t\t";
        }

        return $code;
    }

    /**
     * @param $attribute
     * @param $dataType
     * @param $relationField
     * @param $selectDefinition
     *
     * @return string
     */
    private function _makeField($attribute, $dataType, $relationField, $selectDefinition)
    {
        $code = "\n\t\t\t" . '<div class="form-group">' . PHP_EOL .
            "\t\t\t\t" . '<label for="' . $attribute . '" class="control-label col-sm-4">' . $this->_getPossibleLabel($attribute) . '</label>' . PHP_EOL .
            "\t\t\t\t" . '<div class="input-group">';

        if (isset($relationField[$attribute])) {
            $code .= PHP_EOL . "\t\t\t\t" . '<?php echo $this->tag->select(array("' . $attribute . '", $' . $selectDefinition[$attribute]['varName'] .
                ', "using" => "' . $selectDefinition[$attribute]['primaryKey'] . ',' . $selectDefinition[$attribute]['detail'] . '", "useDummy" => true)) ?>';
        } else {

            switch ($dataType) {
                case Column::TYPE_CHAR:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '<?php echo $this->tag->textField(array("' . $attribute . '")) ?>';
                    break;
                case Column::TYPE_DECIMAL:
                case Column::TYPE_INTEGER:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '<?php echo $this->tag->textField(array("' . $attribute . '", "size" => 30, "type" => "number", "class" => "form-control")) ?>';
                    break;
                case Column::TYPE_DATE:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '<?php echo $this->tag->textField(array("' . $attribute . '", "size" => 30, "type" => "date", "class" => "form-control")) ?>';
                    break;
                case Column::TYPE_TEXT:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '<?php echo $this->tag->textField(array("' . $attribute . '", "size" => 30, "type" => "date", "class" => "form-control")) ?>';
                    break;
                default:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '<?php echo $this->tag->textField(array("' . $attribute . '", "size" => 30, "class" => "form-control")) ?>';
                    break;
            }
        }

        $code .= PHP_EOL . "\t\t\t\t" . '</div>';
        $code .= PHP_EOL . "\t\t\t" . '</div>' . PHP_EOL;

        return $code;
    }

    /**
     * @param $attribute
     * @param $dataType
     * @param $relationField
     * @param $selectDefinition
     *
     * @return string
     */
    private function _makeFieldVolt($attribute, $dataType, $relationField, $selectDefinition)
    {
        $code = "\n\t\t\t" . '<div class="form-group">' . PHP_EOL .
            "\t\t\t\t" . '<label for="' . $attribute . '" class="control-label col-sm-4">' . $this->_getPossibleLabel($attribute) . '</label>' . PHP_EOL .
            "\t\t\t\t" . '<div class="input-group">';

        if (isset($relationField[$attribute])) {
            $code .= PHP_EOL . "\t\t\t\t\t" . '{{ select("' . $attribute . '", ' . $selectDefinition[$attribute]['varName'] .
                ', "using" :[ "' . $selectDefinition[$attribute]['primaryKey'] . ',' . $selectDefinition[$attribute]['detail'] . '", "useDummy" => true]) }}';
        } else {

            switch ($dataType) {
                case Column::TYPE_CHAR:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '{{ text_field("' . $attribute . '", "class": "form-control") }}';
                    break;
                case Column::TYPE_DECIMAL:
                case Column::TYPE_INTEGER:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '{{ text_field("' . $attribute . '", "size" : 30, "type": "numeric", "class": "form-control") }}';
                    break;
                case Column::TYPE_DATE:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '{{ text_field("' . $attribute . '", "size" : 30, "type": "date", "class": "form-control") }}';
                    break;
                case Column::TYPE_TEXT:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '{{ text_field("' . $attribute . '", "size" : 30, "type": "date", "class": "form-control") }}';
                    break;
                default:
                    $code .= PHP_EOL . "\t\t\t\t\t" . '{{ text_field("' . $attribute . '", "size" : 30, "class": "form-control") }}';
                    break;
            }
        }

        $code .= PHP_EOL . "\t\t\t\t" . '</div>';
        $code .= PHP_EOL . "\t\t\t" . '</div>' . PHP_EOL;

        return $code;
    }

    /**
     * Build fields for different actions
     *
     * @param  string $path
     * @param  array  $options
     * @param  string $action
     * @return string $code
     */
    private function _makeFields($path, $options, $action)
    {
        $entity	= $options['entity'];
        $relationField = $options['relationField'];
        $autocompleteFields	= $options['autocompleteFields'];
        $selectDefinition = $options['selectDefinition'];
        $identityField = $options['identityField'];

        $code = '';
        foreach ($options['dataTypes'] as $attribute => $dataType) {

            if (($action == 'new' || $action == 'edit' ) && $attribute == $identityField) {
                continue;
            }

            $code .= $this->_makeField($attribute, $dataType, $relationField, $selectDefinition);
        }

        return $code;
    }

    /**
     * @param $path
     * @param $options
     * @param $action
     *
     * @return string
     */
    private function _makeFieldsVolt($path, $options, $action)
    {
        $entity	= $options['entity'];
        $relationField = $options['relationField'];
        $autocompleteFields	= $options['autocompleteFields'];
        $selectDefinition = $options['selectDefinition'];
        $identityField = $options['identityField'];

        $code = '';
        foreach ($options['dataTypes'] as $attribute => $dataType) {

            if (($action == 'new' || $action == 'edit' ) && $attribute == $identityField) {
                continue;
            }

            $code .= $this->_makeFieldVolt($attribute, $dataType, $relationField, $selectDefinition);
        }

        return $code;
    }

    /**
     * Generate controller using scaffold
     *
     * @param array  $options
     */
    private function _makeController($options)
    {
        $controllerPath = $options['controllersDir'] . $options['className'] . 'Controller.php';

        if(!is_dir($options['controllersDir'])) {
            if(!mkdir($options['controllersDir']))
                return;
            @chmod($options['controllersDir'], 0777);
        }
        if (file_exists($controllerPath)) {
            if (!$options['force']) {
                return;
            }
        }

        $path = $options['templatePath'] . '/scaffold/no-forms/Controller.php';

        $code = file_get_contents($path);

        $code = str_replace('$modelClass$', $options['modelClass'], $code);

        if(Tools::getCopyright() == null) {
            $code = str_replace('$copyright$', '', $code);
        } else {
            $code = str_replace('$copyright$', PHP_EOL . Tools::getCopyright() . PHP_EOL, $code);
        }

        if (isset($options['controllersNamespace']) === true) {
            $code = str_replace('$namespace$', 'namespace '.$options['controllersNamespace'].';', $code);
        } else {
            $code = str_replace('$namespace$', ' ', $code);
        }

        $code = str_replace('$singularVar$', '$' . $options['singular'], $code);
        $code = str_replace('$singular$', $options['singular'], $code);

        $code = str_replace('$pluralVar$', '$' . $options['plural'], $code);
        $code = str_replace('$plural$', $options['plural'], $code);

        $code = str_replace('$className$', $options['className'], $code);

        $code = str_replace('$package$', $options['controllersNamespace'], $code);

        $code = str_replace('$controllerClass$', Tools::getBaseController()[0], $code);

        $explodeController = explode('\\', Tools::getBaseController()[0]);
        $code = str_replace('$controllerName$', array_pop($explodeController), $code);

        $code = str_replace('$assignInputFromRequestCreate$', $this->_captureFilterInput($options['singular'], $options['dataTypes'], $options['genSettersGetters'], $options['identityField']), $code);
        $code = str_replace('$assignInputFromRequestUpdate$', $this->_captureFilterInput($options['singular'], $options['dataTypes'], $options['genSettersGetters'], $options['identityField']), $code);

        $code = str_replace('$assignTagDefaults$', $this->_assignTagDefaults($options['singular'], $options['dataTypes'], $options['genSettersGetters']), $code);

        $code = str_replace('$pkVar$', '$' . $options['attributes'][0], $code);
        $code = str_replace('$pkFind$', Text::camelize($options['attributes'][0]), $code);
        $code = str_replace('$pk$', $options['attributes'][0], $code);

        $code = str_replace("\t", "    ", $code);
        file_put_contents($controllerPath, $code);
        @chmod($controllerPath, 0777);
    }

    /**
     * Make layouts of model using scaffold
     *
     * @param string $path
     * @param array  $options
     */
    private function _makeLayouts($path, $options)
    {
        //Make Layouts dir
        $dirPathLayouts	= $options['viewsDir'] . '/layouts';

        //If dir doesn't exist we make it
        if (is_dir($dirPathLayouts) == false) {
            mkdir($dirPathLayouts);
        }

        $fileName = $options['fileName'];
        $viewPath = $dirPathLayouts . DIRECTORY_SEPARATOR . $fileName . '.phtml';
        if (!file_exists($viewPath) || $options['force']) {

            //View model layout
            $code = '';
            if (isset($options['theme'])) {
                $code.='<?php $this->tag->stylesheetLink("themes/lightness/style") ?>'.PHP_EOL;
                $code.='<?php $this->tag->stylesheetLink("themes/base") ?>'.PHP_EOL;
            }

            if (isset($options['theme'])) {
                $code .= '<div class="ui-layout" align="center">' . PHP_EOL;
            } else {
                $code .= '<div align="center">' . PHP_EOL;
            }
            $code .= "\t" . '<?php echo $this->getContent(); ?>' . PHP_EOL . '</div>';

            $code = str_replace("\t", "    ", $code);
            file_put_contents($viewPath, $code);

        }
    }

    /**
     * @param $options
     */
    private function _makeLayoutsVolt($options)
    {
        //Make Layouts dir
        $dirPathLayouts	= $options['viewsDir'] . DIRECTORY_SEPARATOR . 'layouts';

        //If not exists dir; we make it
        if (is_dir($dirPathLayouts) == false) {
            mkdir($dirPathLayouts);
        }

        $fileName = Text::uncamelize($options['fileName']);
        $viewPath = $dirPathLayouts . DIRECTORY_SEPARATOR . $fileName . '.volt';
        if (!file_exists($viewPath || $options['force'])) {

            //View model layout
            $code = '';
            if (isset($options['theme'])) {
                $code.='{{ stylesheet_link("themes/lightness/style") }}'.PHP_EOL;
                $code.='{{ stylesheet_link("themes/base") }}'.PHP_EOL;
            }

            if (isset($options['theme'])) {
                $code .= '<div class="ui-layout" align="center">' . PHP_EOL;
            } else {
                $code .= '<div align="center">' . PHP_EOL;
            }
            $code .= "\t" . '{{ content() }}' . PHP_EOL . '</div>';

            $code = str_replace("\t", "    ", $code);
            file_put_contents($viewPath, $code);
            @chmod($viewPath, 0777);
        }
    }

    /**
     * @param $path
     * @param $options
     * @param $type
     *
     * @throws \Exception
     */
    private function makeView($path, $options, $type) {

        $dirPath = $options['viewsDir'] . $options['fileName'];
        if(!is_dir($options['viewsDir'])) {
            if(!mkdir($dirPath, 0777, true))
                return;
            @chmod($dirPath, 0777);
        }
        if (!is_dir($dirPath)) {
            if(!mkdir($dirPath, 0777, true))
                return;
            @chmod($dirPath, 0777);
        }

        $viewPath = $dirPath . DIRECTORY_SEPARATOR .$type. '.phtml';
        if (file_exists($viewPath)) {
            if (!$options['force']) {
                return;
            }
        }

        $templatePath = $options['templatePath'] . '/scaffold/no-forms/views/' .$type. '.phtml';
        if (!file_exists($templatePath)) {
            throw new \Exception("Template '" . $templatePath . "' does not exist");
        }

        $code = file_get_contents($templatePath);

        $code = str_replace('$plural$', $options['plural'], $code);
        $code = str_replace('$captureFields$', self::_makeFields($path, $options, $type), $code);

        $code = str_replace("\t", "    ", $code);
        file_put_contents($viewPath, $code);
        @chmod($viewPath, 0777);
    }

    /**
     * @param $path
     * @param $options
     * @param $type
     *
     * @throws \Exception
     */
    private function makeViewVolt($path, $options, $type)
    {
        $dirPath = $options['viewsDir'] . $options['fileName'];
        if(!is_dir($options['viewsDir'])) {
            if(!mkdir($dirPath, 0777, true))
                return;
            @chmod($dirPath, 0777);
        }
        if (!is_dir($dirPath)) {
            if(!mkdir($dirPath, 0777, true))
                return;
            @chmod($dirPath, 0777);
        }

        $viewPath = $dirPath . DIRECTORY_SEPARATOR .$type. '.volt';
        if (file_exists($viewPath)) {
            if (!$options['force']) {
                return;
            }
        }

        $templatePath = $options['templatePath'] . '/scaffold/no-forms/views/' .$type. '.volt';
        if (!file_exists($templatePath)) {
            throw new \Exception("Template '" . $templatePath . "' does not exist");
        }

        $code = file_get_contents($templatePath);

        $code = str_replace('$plural$', $options['plural'], $code);
        $code = str_replace('$captureFields$', self::_makeFieldsVolt($path, $options, $type), $code);

        $code = str_replace("\t", "    ", $code);
        file_put_contents($viewPath, $code);
        @chmod($viewPath, 0777);
    }

    /**
     * Creates main view
     *
     * @param string $path
     * @param array  $options
     */
    private function _makeViewIndex($path, $options)
    {
        $this->makeView($path, $options, 'index');
    }

    /**
     * @param $path
     * @param $options
     */
    private function _makeViewIndexVolt($path, $options)
    {
        $this->makeViewVolt($path, $options, 'index');
    }

    /**
     * Creates the view to create a new item
     *
     * @param string $path
     * @param array  $options
     */
    private function _makeViewNew($path, $options)
    {
        $this->makeView($path, $options, 'new');
    }

    /**
     * @param $path
     * @param $options
     */
    private function _makeViewNewVolt($path, $options)
    {
        $this->makeViewVolt($path, $options, 'new');
    }

    /**
     * Creates the view to edit an item
     *
     * @param string $path
     * @param array  $options
     */
    private function _makeViewEdit($path, $options)
    {
        $this->makeView($path, $options, 'edit');
    }

    private function _makeViewEditVolt($path, $options)
    {
        $this->makeViewVolt($path, $options, 'edit');
    }

    /**
     * Creates the view to display search results
     *
     * @param $path
     * @param $options
     *
     * @throws \Exception
     */
    private function _makeViewSearch($path, $options)
    {
        $dirPath = $options['viewsDir'] . $options['fileName'];
        if(!is_dir($options['viewsDir'])) {
            if(!mkdir($dirPath, 0777, true))
                return;
            @chmod($dirPath, 0777);
        }
        if (!is_dir($dirPath)) {
            if(!mkdir($dirPath, 0777, true))
                return;
            @chmod($dirPath, 0777);
        }

        $viewPath = $dirPath . DIRECTORY_SEPARATOR . 'search.phtml';
        if (file_exists($viewPath)) {
            if (!$options['force']) {
                return;
            }
        }

        $templatePath = $options['templatePath'] . '/scaffold/no-forms/views/search.phtml';
        if (!file_exists($templatePath)) {
            throw new \Exception("Template '" . $templatePath . "' does not exist");
        }

        $headerCode = '';
        foreach ($options['attributes'] as $attribute) {
            $headerCode .= "\t\t\t\t" . '<th>' . $this->_getPossibleLabel($attribute) . '</th>' . PHP_EOL;
        }

        $rowCode = '';
        $options['allReferences'] = array_merge($options['autocompleteFields'], $options['selectDefinition']);
        foreach ($options['dataTypes'] as $fieldName => $dataType) {
            $rowCode .= "\t\t\t\t" . '<td><?php echo ';
            if (!isset($options['allReferences'][$fieldName])) {
                if ($options['genSettersGetters']) {
                    $rowCode .= '$' . $options['singular'] . '->get' . Text::camelize($fieldName) . '()';
                } else {
                    $rowCode .= '$' . $options['singular'] . '->' . $fieldName;
                }
            } else {
                $detailField = ucfirst($options['allReferences'][$fieldName]['detail']);
                $rowCode .= '$' . $options['singular'] . '->get' . $options['allReferences'][$fieldName]['tableName'] . '()->get' . $detailField . '()';
            }
            $rowCode .= ' ?></td>' . PHP_EOL;
        }

        if ($options['genSettersGetters']) {
            $idField = 'get' . Text::camelize($options['attributes'][0]) . '()';
        } else {
            $idField =  $options['attributes'][0];
        }

        $code = file_get_contents($templatePath);

        $code = str_replace('$plural$', $options['plural'], $code);
        $code = str_replace('$headerColumns$', $headerCode, $code);
        $code = str_replace('$rowColumns$', $rowCode, $code);
        $code = str_replace('$singularVar$', '$' . $options['singular'], $code);
        $code = str_replace('$pk$', $idField, $code);

        $code = str_replace("\t", "    ", $code);
        file_put_contents($viewPath, $code);
        @chmod($viewPath, 0777);
    }

    /**
     * @param $path
     * @param $options
     *
     * @throws \Exception
     */
    private function _makeViewSearchVolt($path, $options)
    {
        $dirPath = $options['viewsDir'] . $options['fileName'];
        if(!is_dir($options['viewsDir'])) {
            if(!mkdir($dirPath, 0777, true))
                return;
            @chmod($dirPath, 0777);
        }
        if (!is_dir($dirPath)) {
            if(!mkdir($dirPath, 0777, true))
                return;
            @chmod($dirPath, 0777);
        }

        $viewPath = $dirPath . DIRECTORY_SEPARATOR . 'search.volt';
        if (file_exists($viewPath)) {
            if (!$options['force']) {
                return;
            }
        }

        $templatePath = $options['templatePath'] . '/scaffold/no-forms/views/search.volt';
        if (!file_exists($templatePath)) {
            throw new \Exception("Template '" . $templatePath . "' does not exist");
        }

        $headerCode = '';
        foreach ($options['attributes'] as $attribute) {
            $headerCode .= "\t\t\t\t" . '<th>' . $this->_getPossibleLabel($attribute) . '</th>' . PHP_EOL;
        }

        $rowCode = '';
        $options['allReferences'] = array_merge($options['autocompleteFields'], $options['selectDefinition']);
        foreach ($options['dataTypes'] as $fieldName => $dataType) {
            $rowCode .= "\t\t\t\t\t\t" . '<td>{{ ';
            if (!isset($options['allReferences'][$fieldName])) {
                if ($options['genSettersGetters']) {
                    $rowCode .= $options['singular'] . '.get' . Text::camelize($fieldName) . '()';
                } else {
                    $rowCode .= $options['singular'] . '.' . $fieldName;
                }
            } else {
                $detailField = ucfirst($options['allReferences'][$fieldName]['detail']);
                $rowCode .= $options['singular'] . '.get' . $options['allReferences'][$fieldName]['tableName'] . '().get' . $detailField . '()';
            }
            $rowCode .= ' }}</td>' . PHP_EOL;
        }

        if ($options['genSettersGetters']) {
            $idField = 'get' . Text::camelize($options['attributes'][0]) . '()';
        } else {
            $idField = $options['attributes'][0];
        }

        $code = file_get_contents($templatePath);

        $code = str_replace('$plural$', $options['plural'], $code);
        $code = str_replace('$headerColumns$', $headerCode, $code);
        $code = str_replace('$rowColumns$', $rowCode, $code);
        $code = str_replace('$singularVar$', $options['singular'], $code);
        $code = str_replace('$pk$', $idField, $code);

        $code = str_replace("\t", "    ", $code);
        file_put_contents($viewPath, $code);
        @chmod($viewPath, 0777);
    }
}