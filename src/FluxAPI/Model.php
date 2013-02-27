<?php
namespace FluxAPI;

abstract class Model
{
    private $_data = array();

    private $_fields = array();

    protected $_storage = NULL;

    private $_belongs_to_one = array();
    private $_belongs_to_many = array();

    private $_has_one = array();
    private $_has_many = array();

    public function  __construct($data = array())
    {
        $this->_storage = self::getStorage();
        $this->defineFields();
        $this->_setDefaults();

        $this->populate($data);
    }

    public static function getCollectionName()
    {
        $class_name = self::getClassName();
        $parts = explode('\\',$class_name);
        return strtolower($parts[count($parts)-1]);
    }

    public function addField(Field $field)
    {
        $this->_fields[$field->name] = $field;
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
            'default' => NULL
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

    public static function getStorage()
    {
        $class_name = self::getClassName();

        $storage = Api::getInstance()->getStorage($class_name);
        return $storage;
    }

    public static function load(Query $query = NULL)
    {
        $class_name = self::getClassName();

        return self::getStorage()->load($class_name, $query);
    }

    public function save()
    {
        $class_name = self::getClassName();
        return $this->_storage->save($class_name, $this);
    }

    public function update($data = array())
    {
        $this->populate($data);
        $this->save();
    }

    public static function delete(Query $query = NULL)
    {
        $class_name = self::getClassName();
        return self::getStorage()->delete($class_name, $query);
    }

    public function __get(String $name)
    {
        return $this->_data[$name];
    }

    public function __set($name,$value)
    {
        $this->_data[$name] = $value;
    }

    public function __isset(String $name)
    {
        return isset($this->_data[$name]);
    }

    public function __unset(String $name)
    {
        unset($this->_data[$name]);
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString()
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
