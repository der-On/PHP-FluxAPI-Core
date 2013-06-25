<?php
namespace FluxAPI\Cache;

class ControllerPermissionSource extends CacheSource
{
    public $controller_name = NULL;
    public $action = NULL;

    public function __construct($controller_name, $action = NULL)
    {
        $this->controller_name = $controller_name;
        $this->action = $action;
    }
}