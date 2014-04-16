<?php

namespace FluxAPI\Event;

use Symfony\Component\EventDispatcher\Event;

class QueryEvent extends Event
{
    const BEFORE_EXECUTE = 'query.before_execute';
    const AFTER_EXECUTE = 'query.after_execute';

    protected $_query;
    protected $_models;

    public function __construct(\FluxAPI\Query $query, \FluxAPI\Collection\ModelCollection $models = null)
    {
        $this->_query = $query;
        $this->_models = $models;
    }

    public function getQuery()
    {
        return $this->_query;
    }

    public function getModels()
    {
        return $this->_models;
    }
}