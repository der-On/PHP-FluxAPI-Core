<?php
namespace FluxAPI;

class Field
{
    public $name = NULL;
    public $value = NULL;
    public $type = NULL;
    public $length = NULL;
    public $default = NULL;

    public function __construct($config = array())
    {
        foreach($config as $name => $value) {
            if (property_exists($this,$name)) {
                $this->$name = $value;
            }
        }
    }
}
