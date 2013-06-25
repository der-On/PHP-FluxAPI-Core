<?php
namespace FluxAPI\Cache;

class ControllerActionSource extends CacheSource
{
    public $controller_name = NULL;
    public $action = NULL;
    public $result = NULL;

    function __construct($controller_name, $action, $result = NULL)
    {
        $this->controller_name = $controller_name;
        $this->action = $action;
        $this->result = $result;
    }
}