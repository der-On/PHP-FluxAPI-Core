<?php

namespace FluxAPI;


abstract class Permission
{
    protected $_api;

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }

    public function hasModelAccess($model_name, \FluxAPI\Model $model = NULL, $action = NULL)
    {
        return TRUE;
    }

    public function hasControllerAccess($controller_name, $action = NULL)
    {
        return TRUE;
    }
}