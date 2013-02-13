<?php
namespace FluxAPI;

abstract class Model
{
    private $_data = array();

    private $_fields = array();

    private $_belongs_to_one = array();
    private $_belongs_to_many = array();

    private $_has_one = array();
    private $_has_many = array();

    public function  __construct($data = array())
    {
        $this->_defineFields();
        $this->_setDefaults();

        $this->populate($data);
    }

    private function _defineFields()
    {
        $this->_fields['id'] = new Field(array(
            'type' => 'integer',
            'primary' => TRUE,
            'default' => NULL
        ));
    }

    private function _setDefaults()
    {
        foreach($this->_fields as $name => $field) {
            $this->_data[$name] = $field->default;
        }
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

    public static function load($query)
    {
        $class_name = self::getClassName();

        return array(
            new $class_name(array(
                'id' => 1
            ))
        );
    }

    public function save()
    {
        return TRUE;
    }

    public function update($data = array())
    {
        $this->populate($data);
    }

    public static function delete($query)
    {
        return TRUE;
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
