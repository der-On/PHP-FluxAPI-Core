<?php
namespace FluxAPI\Cache;

class ModelPermissionSource extends CacheSource
{
    public $model_name = NULL;
    public $model = NULL;
    public $action = NULL;

    public function __construct($model_name , \FluxAPI\Model $model = NULL, $action = NULL)
    {
        $this->model_name = $model_name;
        $this->model = $model;
        $this->action = $action;
    }
}