<?php
namespace FluxAPI;

/**
 * A Model definition plugin
 *
 * All your model plugins must inherit form this class.
 *
 * @package FluxAPI
 */
abstract class Model
{
    /**
     * @var array Internal data
     */
    private $_data = array();

    /**
     * @var array Internal lookup of already (lazy) loaded relation fields
     */
    private $_loaded_relations = array();

    /**
     * @var array Internal list of field definitions
     */
    private $_fields = array();

    /**
     * @var bool Internal flag that's true if the model instance was modified since it was loaded
     */
    private $_modified = false;

    /**
     * @var Api Api instance
     */
    protected $_api;

    /**
     * Constructor
     *
     * @param [array $data] if set the model will contain that initial data
     */
    public function  __construct($data = array())
    {
        $this->_api = \FluxApi\Api::getInstance();
        $this->defineFields();
        $this->addExtends();
        $this->setDefaults();

        $this->populate($data);
    }

    /**
     * Adds a new field definition to the model
     *
     * @chainable
     * @param Field $field
     * @return Model $this
     */
    public function addField(Field $field)
    {
        $this->_fields[$field->name] = $field;

        return $this; // make it chainable
    }

    /**
     * Returns a field definition by it's name
     *
     * @param string $name
     * @return null|Field
     */
    public function getField($name)
    {
        if ($this->hasField($name)) {
            return $this->_fields[$name];
        } else {
            return NULL;
        }
    }

    /**
     * Returns all field definitions of this model
     *
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * Returns all relation fields of this model
     *
     * @return array
     */
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

    /**
     * Checks if a field exists in the model
     *
     * @param string $name
     * @return bool true if field exists, else false
     */
    public function hasField($name)
    {
        return isset($this->_fields[$name]);
    }

    /**
     * Defines field definitions of this model. Extend this method in child classes while calling parent::defineFields()
     */
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

    /**
     * Sets default field values. Called automatically in the constructor.
     */
    public function setDefaults()
    {
        foreach($this->_fields as $name => $field) {
            if (!isset($this->_data[$name])) $this->_data[$name] = $field->default;
        }
    }

    /**
     * Adds dynamic extends (currently only fields)
     */
    public function addExtends()
    {
        $extends = $this->_api->getExtends('Model',$this->getModelName());

        if (!empty($extends)) {
            foreach($extends['fields'] as $field) {
                $this->addField(new Field($field));
            }
        }
    }

    /**
     * Populates the model instance with the given data.
     *
     * @param [array $data]
     */
    public function populate(Array $data = array())
    {
        foreach($data as $name =>  $value)
        {
            $this->_data[$name] = $value;
        }
    }

    /**
     * Returns the full class name of the model
     *
     * @return string
     */
    public static function getClassName()
    {
        return get_called_class();
    }

    /**
     * Returns the model name which is basically the last part of the full class name.
     *
     * @return string
     */
    public function getModelName()
    {
        $parts = explode('\\',self::getClassName());
        return $parts[count($parts)-1];
    }

    /**
     * Checks if the model instance is new or already existing in the storage
     * @return bool true if the model instance is new, else false
     */
    public function isNew()
    {
        return (empty($this->id));
    }

    /**
     * Checks if the model instance was modified since it was loaded
     * @return bool
     */
    public function isModified()
    {
        return $this->_modified;
    }

    /**
     * Returns a magic property (a fields value)
     *
     * @param string $name
     * @return null|mixed
     */
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

    /**
     * Sets a magic property (a fields value)
     *
     * @param string $name
     * @param mixed $value
     */
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

    /**
     * Checks if a magic property (a fields value) isset
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    /**
     * Unsets a magic property (a fields value)
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->_data[$name]);
    }

    /**
     * Magic method to return a string represantation of the model
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Returns an array represantation of the model
     *
     * @return array
     */
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

    /**
     * Returns a string represantation of the model
     *
     * @return string
     */
    public function toString()
    {
        return json_encode($this->toArray(),JSON_PRETTY_PRINT);
    }

    /**
     * Returns a JSON string represantation of the model
     *
     * @return string
     */
    public function toJson()
    {
        $array = $this->toArray();
        return $this->_api->app['serializer']->serialize($array,'json');
    }

    /**
     * Returns a XML string represantation of the model
     *
     * @return string
     */
    public function toXml()
    {
        $array = $this->toArray();
        $this->_api->app['serializer.encoders'][1]->setRootNodeName($this->getModelName());
        return $this->_api->app['serializer']->serialize($array,'xml');
    }

    /**
     * Returns a YAML string represantation of the model
     *
     * @return string
     */
    public function toYaml()
    {
        $array = $this->toArray();

        $dumper = new \Symfony\Component\Yaml\Dumper();

        return $dumper->dump($array,2);
    }
}
