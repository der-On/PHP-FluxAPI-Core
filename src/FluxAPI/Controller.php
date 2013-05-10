<?php

namespace FluxAPI;

use Plugins\FluxAPI\FluxAPI;

abstract class Controller implements ControllerInterface
{
    protected $_api;

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }

    /**
     * Checks for the existance of an action in this controller.
     *
     * @param string $action
     * @return bool - true if action exists, else false
     */
    public static function hasAction($action)
    {
        return (in_array($action, self::getActions()));
    }

}