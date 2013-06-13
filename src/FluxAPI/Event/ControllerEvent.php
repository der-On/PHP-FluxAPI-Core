<?php
namespace FluxAPI\Event;

use Symfony\Component\EventDispatcher\Event;

class ControllerEvent  extends Event
{
    const CALL = 'controller.call';
    const BEFORE_CALL = 'controller.before_call';

    protected $_controller_name;
    protected $_controller;
    protected $_action;
    protected $_params;

    public function __construct($controller_name, \FluxAPI\ControllerInterface $controller = NULL, $action = NULL, $params = NULL)
    {
        $this->_controller_name = $controller_name;
        $this->_controller = $controller;
        $this->_action = $action;
        $this->_params = $params;
    }

    public function getControllerName()
    {
        return $this->_controller_name;
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function getParams()
    {
        return $this->_params;
    }
}