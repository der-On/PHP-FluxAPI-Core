<?php
namespace FluxAPI;

class Rest
{
    private $_core = NULL;

    public function __construct(Core $core)
    {
        $this->_core = $core;

        $this->registerRoutes();
    }

    public function registerRoutes()
    {

    }
}
