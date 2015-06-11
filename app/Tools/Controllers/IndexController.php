<?php
/**
 * @copyright   2006 - 2015 Magnxpyr Network
 * @license     New BSD License; see LICENSE
 * @link        http://www.magnxpyr.com
 * @author      Stefan Chiriac <stefan@magnxpyr.com>
 */

namespace Tools\Controllers;

/**
 * Class IndexController
 * @package Tools\Controllers
 */
class IndexController extends ControllerBase
{
    public function indexAction()
    {
        $this->dispatcher->forward(array(
            'controller' => 'modules',
            'action' => 'list'
        ));
    }
}
