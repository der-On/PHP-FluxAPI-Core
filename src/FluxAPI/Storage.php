<?php
namespace FluxAPI;

abstract class Storage
{
    private $_api = NULL;
    public $config = array();

    public function __construct(Api $api, $config = array())
    {
        $this->config = array_merge($this->config,$config);

        $this->_api = $api;
    }

    public function save($models)
    {

    }

    public function load($model, $query)
    {

    }
}
