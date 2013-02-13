<?php
namespace Plugins\Core\Storage;

class MySql extends \FluxAPI\Storage
{
    public function __construct(\FluxApi\Api $api, $config = array())
    {
        parent::__construct($api,$config);
    }

}
