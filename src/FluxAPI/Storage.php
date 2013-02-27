<?php
namespace FluxAPI;

abstract class Storage
{
    private $_api = NULL;
    public $config = array();

    public function __construct(Api $api, array $config = array())
    {
        $this->config = array_merge($this->config,$config);

        $this->_api = $api;
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
