<?php
namespace FluxAPI;

interface PermissionInterface
{
    public function hasModelAccess($model_name, \FluxAPI\Model $model = NULL, $action = NULL);

    public function hasControllerAccess($controller_name, $action = NULL);
}