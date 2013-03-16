<?php
namespace FluxAPI;

abstract class Model
{
    private $_data = array();

    private $_loaded_relations = array();

    private $_fields = array();
    private $_modified = false;

    protected $_api;

    public function  __construct($data = array())
    {
        $this->_api = \FluxApi\Api::getInstance();
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
        if ($this->hasField($name)) {
            return $this->_fields[$name];
        } else {
            return NULL;
        }
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function getRelationFields()
    {
        $fields = array();

        foreach($this->_fields as $field) {
            if ($field->type == Field::TYPE_RELATION) {
                $fields[$field->name] = $field;
            }
        }
        return $fields;
    }

    public function hasField($name)
    {
        return isset($this->_fields[$name]);
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

    public static function getModelName()
    {
        $parts = explode('\\',self::getClassName());
        return $parts[count($parts)-1];
    }

    public function isNew()
    {
        return (empty($this->id));
    }

    public function isModified()
    {
        return $this->_modified;
    }

    public function __get($name)
    {
        // lazy loading of relations
        if ($this->hasField($name) && $this->getField($name)->type == Field::TYPE_RELATION) {

            // relation has already been loaded so return it
            if (in_array($name, $this->_loaded_relations)) {
                return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
            } else { // relations needs to be loaded
                $this->_data[$name] = $this->_api->getStorage($this->getModelName())->loadRelation($this,$name);
                $this->_loaded_relations[] = $name;

                return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
            }
        } else {
            return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
        }
    }

    public function __set($name,$value)
    {
        if ($this->_data[$name] != $value) {
            $this->_modified = TRUE;
        }

        $this->_data[$name] = $value;

        if ($this->hasField($name) && $this->getField($name)->type == Field::TYPE_RELATION && !in_array($name,$this->_loaded_relations)) {
            $this->_loaded_relations[] = $name;
        }
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
        $array = array();
        foreach($this->_fields as $name => $field) {
            if ($field->type != Field::TYPE_RELATION) {
                $array[$name] = $this->_data[$name];
            }
        }
        return $array;
    }

    public function toString()
    {
        return json_encode($this->toArray(),4);
    }

    public function toJson()
    {
        $array = $this->toArray();
        return $this->_api->app['serializer']->serialize($array,'json');
    }

    public function toXml()
    {
        $array = $this->toArray();
        $this->_api->app['serializer.encoders'][1]->setRootNodeName($this->getModelName());
        return $this->_api->app['serializer']->serialize($array,'xml');
    }

    public function toYaml()
    {
        $array = $this->toArray();

        $dumper = new \Symfony\Component\Yaml\Dumper();

        return $dumper->dump($array,2);
    }

    public static function fromArray(array $data = array())
    {
        $className = self::getClassName();
        return new $className($data);
    }

    public static function fromObject($object)
    {
        $data = array();

        if (is_object($object)) {
            foreach(get_object_vars($object) as $name => $value) {
                $data[$name] = $value;
            }
        }
        return self::fromArray($data);
    }

    public static function fromJson($json)
    {
        $data = array();

        if (!empty($json)) {
            $data = json_decode($json,TRUE);

            if (!empty($data)) {
                return self::fromArray($data);
            }
        }

        return NULL;
    }

    public static function fromXml($xml)
    {
        $data = array();

        if (!empty($xml)) {
            $api = \FluxAPI\Api::getInstance();

            $parser = new \Symfony\Component\Serializer\Encoder\XmlEncoder(self::getModelName());
            $data = $parser->decode($xml,'xml');

            return self::fromArray($data);
        }

        return NULL;
    }

    public static function fromYaml($yaml)
    {
        $data = array();

        if (!empty($yaml)) {
            $parser = new \Symfony\Component\Yaml\Parser();
            $data = $parser->parse($yaml);
        }

        return self::fromArray($data);
    }
}
