<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ondrej
 * Date: 31.03.13
 * Time: 20:58
 * To change this template use File | Settings | File Templates.
 */

namespace FluxAPI;


class ControllerFactory
{
    protected $_api;

    public function __construct(Api $api)
    {
        $this->_api = $api;
    }
}