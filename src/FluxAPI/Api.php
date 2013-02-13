<?php
namespace FluxAPI;

class Api
{
    private $_core = NULL;

    public function __construct(Core $core)
    {
        $this->_core = $core;
    }
}
