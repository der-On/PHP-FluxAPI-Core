<?php

namespace FluxAPI\Event;

use Symfony\Component\EventDispatcher\Event;

class ModelEvent extends Event
{
    const CREATE = 'model.create';
    const BEFORE_CREATE = 'model.before_create';
    const LOAD = 'model.load';
    const BEFORE_LOAD = 'model.before_load';
    const UPDATE = 'model.update';
    const BEFORE_UPDATE = 'model.before_update';
    const SAVE = 'model.save';
    const BEFORE_SAVE = 'model.before_save';
    const DELETE = 'model.delete';
    const BEFORE_DELETE = 'model.before_delete';

    protected $_model_name;
    protected $_model;
    protected $_query;

    public function __construct($model_name, \FluxAPI\Query $query = NULL, \FluxAPI\Model &$model = NULL)
    {
        $this->_model_name = $model_name;
        $this->_query = $query;
        $this->_model = $model;
    }

    public function getModelName()
    {
        return $this->_model_name;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function getQuery()
    {
        return $this->_query;
    }
}