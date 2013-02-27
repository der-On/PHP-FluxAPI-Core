<?php
namespace FluxAPI;

use \Doctrine\DBAL\Query\QueryBuilder;

abstract class Storage
{
    private $_api = NULL;
    private $_filters = NULL;
    public $config = array();

    public function __construct(Api $api, array $config = array())
    {
        $this->config = array_merge($this->config,$config);
        $this->_api = $api;

        $this->_filters = new Pimple();

        $this->addFilters();
    }

    public function addFilters()
    {

    }

    public function addFilter($name,$callback)
    {
        if (!$this->hasFilter($name)) {
            $this->_filters[$name] = $callback;
        }
    }

    public function hasFilter($name)
    {
        return (isset($this->_filters[$name]) && !empty($this->_filters[$name]));
    }

    public function getFilter($name)
    {
        if ($this->hasFilter($name)) {
            return $this->_filters[$name];
        } else {
            return NULL;
        }
    }

    public function save($model, array $models)
    {
        $query = new Query();
        $query->setType(Query::TYPE_INSERT); // TODO: depending on if the models already exists it needs to be TYPE_UPDATE or TYPE_INSERT
        $query->setModel($model);
        return $this->executeQuery($query);
    }

    public function load($model, Query $query)
    {
        $query->setType(Query::TYPE_SELECT);
        $query->setModel($model);
        return $this->executeQuery($query);
    }

    public function update($model, Query $query, array $fields)
    {
        $query->setType(Query::TYPE_UPDATE);
        $query->setModel($model);
        return $this->executeQuery($query);
    }

    public function executeQuery(Query $query)
    {
        $query->setStorage($this);

        return NULL;
    }
}
