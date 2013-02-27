<?php
namespace FluxAPI;

use \Doctrine\DBAL\Query\QueryBuilder;

abstract class Storage
{
    protected $_api = NULL;
    protected $_filters = array();
    public $config = array();

    public function __construct(Api $api, array $config = array())
    {
        $this->config = array_merge($this->config,$config);
        $this->_api = $api;

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

    public function getFilters()
    {
        return $this->_filters;
    }

    public function save($model, array $models)
    {
        $query = new Query();
        $query->setType(Query::TYPE_UPDATE);
        $query->setModel($model);
        return $this->executeQuery($query);
    }

    public function load($model, Query $query = NULL)
    {
        if (empty($query)) {
            $query = new Query();
        }
        $query->setType(Query::TYPE_SELECT);
        $query->setModel($model);
        return $this->executeQuery($query);
    }

    public function update($model, Query $query = NULL, array $fields)
    {
        if (empty($query)) {
            $query = new Query();
        }
        $query->setType(Query::TYPE_UPDATE);
        $query->setModel($model);
        return $this->executeQuery($query);
    }

    public function delete($model, Query $query = NULL)
    {
        if (empty($query)) {
            $query = new Query();
        }
        $query->setType(Query::TYPE_DELETE);
        $query->setModel($model);
        return $this->executeQuery($query);
    }

    public function executeQuery(Query $query)
    {
        $query->setStorage($this);

        if (!$this->isConnected()) {
            $this->connect();
        }

        return NULL;
    }

    public function isConnected()
    {
        return FALSE;
    }

    public function connect()
    {

    }

    public function getConnection()
    {
        return NULL;
    }
}
