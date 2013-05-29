<?php

namespace FluxAPI;

use Plugins\FluxAPI\FluxAPI;

abstract class Controller implements ControllerInterface
{
    protected $_api;

    protected $_context = array();

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }

    public static function getActions()
    {
        return array();
    }

    /**
     * Checks for the existance of an action in this controller.
     *
     * @param string $action
     * @return bool - true if action exists, else false
     */
    public static function hasAction($action)
    {
        $class = get_called_class();
        return (in_array($action, $class::getActions()));
    }

    /**
     * Sets a context variable
     *
     * @param $key
     * @param $value
     */
    public function setContext($key, $value)
    {
        $this->_context[$key] = $value;
    }

    /**
     * Retrieves a context variable.
     *
     * @param $key
     * @return null
     */
    public function getContext($key)
    {
        if (isset($this->_context[$key])) {
            return $this->_context[$key];
        }

        return NULL;
    }

    /**
     * Clears the context
     */
    public function clearContext()
    {
        $this->_context = array();
    }
}