<?php
namespace FluxAPI;

abstract class Model
{
    private $_data = array(
        'id' => NULL
    );

    public function  __construct($data = array())
    {
        $this->populate($data);
    }

    public function populate($data = array())
    {
        foreach($data as $name =>  $value)
        {
            $this->_data[$name] = $value;
        }
    }

    public static function getClassName()
    {
        return get_called_class();
    }

    public static function load($id)
    {
        $class_name = self::getClassName();

        return new $class_name(array(
            'id' => $id
        ));
    }

    public function save()
    {

    }

    public function update($data = array())
    {
        $this->populate($data);
    }

    public static function delete($id)
    {

    }

    public function __get($name)
    {
        return $this->_data[$name];
    }

    public function __set($name,$value)
    {
        $this->_data[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    public function __toString()
    {
        return json_encode($this->_data,4);
    }

    public function toJson()
    {
        return json_encode($this->_data);
    }

    public function toXml()
    {
        return NULL;
    }
}
