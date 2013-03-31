<?php

namespace FluxAPI\Factory;


class PermissionFactory
{
    protected $_api;

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }
}