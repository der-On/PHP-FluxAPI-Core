<?php
namespace FluxAPI;

abstract class Options
{
    public function __construct(array $options = array())
    {
        $class_name = get_called_class();

        // autopopulate properties
        foreach($options as $key => $value) {
            if (property_exists($class_name, $key)) {
                $this->$key = $value;
            }
        }
    }
}