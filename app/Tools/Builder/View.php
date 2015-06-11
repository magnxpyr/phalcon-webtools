<?php
/**
 * @copyright   2006 - 2015 Magnxpyr Network
 * @license     New BSD License; see LICENSE
 * @link        http://www.magnxpyr.com
 * @author      Stefan Chiriac <stefan@magnxpyr.com>
 */

namespace Tools\Builder;

use Phalcon\Text;
use Tools\Helpers\Tools;

/**
 * Class View
 * @package Tools\Builder
 */
class View extends Component
{
    /**
     * View Construct
     * @param $options
     * @throws \Exception
     */
    public function __construct($options)
    {
        if (empty($options['name'])) {
            throw new \Exception("Please specify the view name");
        }
        if (!isset($options['force'])) {
            $options['force'] = false;
        }
        if (!isset($options['directory'])) {
            $options['directory'] = Tools::getModulesPath() . $options['module'] . DIRECTORY_SEPARATOR . Tools::getViewsDir() . DIRECTORY_SEPARATOR;
        } else {
            $options['directory'] .= DIRECTORY_SEPARATOR;
        }
        if (!isset($options['action'])) {
            $options['action'] = 'index';
        }
        $this->_options = $options;
    }

    /**
     * Generate view
     */
    public function build()
    {
        $action = Text::uncamelize($this->_options['action']);

        $viewName = explode('-', str_replace('_', '-', Text::uncamelize($this->_options['name'])));
        if (count($viewName) > 1) {
            array_pop($viewName);
        }
        $viewName = implode('-', $viewName);
        $viewDir = $this->_options['directory'] . DIRECTORY_SEPARATOR . $viewName;
        $viewPath = $viewDir . DIRECTORY_SEPARATOR . $action . '.volt';

        $code = "<?php\n".Tools::getCopyright()."\n?>\n";
        $code = str_replace("\t", "    ", $code);

        if (!file_exists($viewPath) || $this->_options['force'] == true) {
            if(!is_dir($viewDir)) {
                mkdir($viewDir, 0777, true);
                chmod($viewDir, 0777);
            }
            if (!@file_put_contents($viewPath, $code)) {
                throw new \Exception("Unable to write to '$viewPath'");
            }
            chmod($viewPath, 0777);
        } else {
            throw new \Exception("The View '$action' already exists");
        }

        return $viewName;
    }
}