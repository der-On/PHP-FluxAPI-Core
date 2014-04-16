<?php

namespace FluxAPI\Event;

use Symfony\Component\EventDispatcher\Event;

class QueryEvent extends Event
{
    const BEFORE_EXECUTE = 'query.before_execute';

    protected $_query;

    public function __construct(\FluxAPI\Query $query)
    {
        $this->_query = $query;
    }

    public function getQuery()
    {
        return $this->_query;
    }
}