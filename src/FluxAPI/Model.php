<?php
namespace FluxAPI;

abstract class Model
{
    private $_data = array();

    private $_fields = array();

    public function  __construct($data = array())
    {
        $this->defineFields();
        $this->_setDefaults();

        $this->populate($data);
    }

    public function addField(Field $field)
    {
        $this->_fields[$field->name] = $field;

        return $this; // make it chainable
    }

    public function getField($name)
    {
        if (isset($this->_fields[$name])) {
            return $this->_fields[$name];
        } else {
            return NULL;
        }
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function defineFields()
    {
        $this->addField(new Field(array(
            'name' => 'id',
            'type' => Field::TYPE_INTEGER,
            'primary' => TRUE,
            'default' => NULL,
            'autoIncrement' => TRUE,
        )));
    }

    private function _setDefaults()
    {
        foreach($this->_fields as $name => $field) {
            $this->_data[$name] = $field->default;
        }
    }

    public function populate(Array $data = array())
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
        return $this->toString();
    }

    public function toArray()
    {
        return $this->_data;
    }

    public function toString()
    {
        return json_encode($this->toArray(),4);
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function toXml()
    {
        return NULL;
    }
}
