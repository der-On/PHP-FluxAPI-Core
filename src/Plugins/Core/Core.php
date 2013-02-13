<?php
namespace Plugins\Core;

class Core extends \FluxAPI\Plugin
{
    public static function register(\FluxAPI\Api $api)
    {
        parent::register($api);

        $rest = new Rest($api);
    }
}
