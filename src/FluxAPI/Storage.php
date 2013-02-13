<?php
namespace FluxAPI;

abstract class Storage
{
    private $_api = NULL;

    public function __construct(Api $api)
    {
        $this->_api = $api;
    }

    public function save($models)
    {

    }

    public function load($model, $query)
    {

    }
}
