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
     * @var array Internal shadow copy of the data
     */
    private $_shadow_data = array();

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
     * @var bool Internal flag that's false if the model already exists in storage
     */
    private $_new = true;

    /**
     * @var bool Internal flag that's true if the model got initially populated
     */
    private $_populated = false;

    /**
     * @var Api Api instance
     */
    protected $_api;

    /**
     * Query wich was used to retrieve this collection from the storage
     *
     * @var \FluxAPI\Query
     */
    protected $_query = NULL;

    /**
     * Holds validation errors if any
     *
     * @var \FluxAPI\Collection\ModelErrorCollection
     */
    protected $_errors = NULL;

    /**
     * Constructor
     *
     * @param [array $data] if set the model will contain that initial data
     */
    public function  __construct(\FluxAPI\Api $api, array $data = NULL)
    {
        $this->_api = $api;
        $this->_errors = new \FluxAPI\Collection\ModelErrorCollection();

        $this->defineFields();
        $this->addExtends();
        $this->setDefaults();

        $this->populate($data);
    }

    /**
     * @param Query $query
     */
    public function setQuery(\FluxAPI\Query $query = NULL)
    {
        $this->_query = $query;
    }

    /**
     * @return \FluxAPI\Query|null
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Add a validation error to this model
     *
     * @param Exception\ValidateException $error
     */
    public function addError(\FluxAPI\Exception\ValidateException $error)
    {
        $this->_errors->push($error);
    }

    /**
     * Returns the validation error collection of this model
     *
     * @return Collection\ModelErrorCollection
     */
    public function getErrors()
    {
        return $this->_errors;
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
     * Removes a field
     *
     * @chainable
     * @param string $name
     */
    public function removeField($name)
    {
        if ($this->hasField($name)) {
            unset($this->_fields[$name]);
        }

        return $this;
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
            'type' => Field::TYPE_BYTEARRAY,
            'primary' => TRUE,
            'default' => NULL,
            'length' => 16
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
        $extend = $this->_api['plugins']->getExtends('Model',$this->getModelName());

        if (!empty($extend) && isset($extend['fields'])) {
            foreach($extend['fields'] as $field) {
                $this->addField(new Field($field));
            }
        }
    }

    /**
     * Populates the model instance with the given data.
     *
     * @param [array $data]
     */
    public function populate(array $data = NULL)
    {
        if (!empty($data)) {
            foreach($data as $name =>  $value)
            {
                $this->_data[$name] = $value;

                if ($this->hasField($name) && $this->getField($name)->type == Field::TYPE_RELATION) {
                    $this->_loaded_relations[] = $name;
                }

                // create initial shadow copy
                if (!$this->_populated && !isset($this->_shadow_data[$name])) {
                    $this->_shadow_data[$name] = $value;
                }
            }

            if (!$this->_populated) {
                $this->_populated = true;
            }
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
        return $this->_new;
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
    public function get($name)
    {
        // lazy loading of relations
        if ($this->hasField($name) && $this->getField($name)->type == Field::TYPE_RELATION) {
            // relation has already been loaded so return it
            if (in_array($name, $this->_loaded_relations)) {
                return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
            } elseif(!$this->isNew()) { // relations needs to be loaded (but only on already saved models)
                $this->_data[$name] = $this->_api['storages']->getStorage($this->getModelName())->loadRelation($this, $name);
                $this->_loaded_relations[] = $name;

                return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
            } else {
                return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
            }
        } else {
            return isset($this->_data[$name]) ? $this->_data[$name] : NULL;
        }
    }

    /**
     * Returns a magic property (a fields value)
     *
     * @param string $name
     * @return null|mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Sets a magic property (a fields value)
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        if ($this->_data[$name] != $value) {
            $this->_modified = TRUE;
        }

        if ($this->hasField($name) && $this->getField($name)->type == Field::TYPE_RELATION && !in_array($name,$this->_loaded_relations)) {
            $this->_loaded_relations[] = $name;
        }

        $this->_data[$name] = $value;
    }

    /**
     * Sets a magic property (a fields value)
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name,$value)
    {
        $this->set($name, $value);
    }

    private function _addRelation($name, $model)
    {
        if (!in_array($name, $this->_loaded_relations)) {
            // force lazy loading
            $models = $this->$name;
        }

        $this->_data[$name]->push($model);
    }

    /**
     * Checks if a magic property (a fields value) isset
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        // return true for lazy loading relations
        if ($this->hasField($name) && $this->getField($name)->type == Field::TYPE_RELATION) {
            return TRUE;
        } else {
            return isset($this->_data[$name]);
        }
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
     * Provides magic getters and setters
     *
     * @param $name
     * @param $args
     * @return mixed|null
     */
    public function __call($name, $args)
    {
        if (strpos($name, 'get') === 0) {
            $prop = lcfirst(substr($name, 3));

            return $this->$prop;
        }
        elseif (strpos($name, 'set') === 0) {
            $prop = lcfirst(substr($name, 3));

            $this->$prop = $args[0];
        }
        elseif (strpos($name,'addTo') === 0) {
            $prop = lcfirst(substr($name, 5));

            $field = $this->getField($prop);

            if ($field && $field->type == Field::TYPE_RELATION && in_array($field->relationType, array(Field::HAS_MANY, Field::BELONGS_TO_MANY))) {
                $this->_addRelation($prop, $args[0]);
            }
        }
    }

    /**
     * Checks if a property was modified since the initial population of the model.
     *
     * @param string $name name of the property
     * @return bool true if property was modified, else false
     */
    public function isPropertyModified($name)
    {
        if ($this->isNew()) {
            return TRUE;
        }

        // property was set initially, so we have to check further
        if (isset($this->_shadow_data[$name])) {

            // property is a relation field
            if ($this->hasField($name) && $this->getField($name)->type == Field::TYPE_RELATION) {
                // MANY-relation
                if (is_array($this->_data[$name])) {
                    // count has changed, so definitely modified
                    if (count($this->_data[$name]) != count($this->_shadow_data[$name])) {
                        return TRUE;
                    }
                    // count is the same, so maybe some instances have changed
                    else {
                        foreach($this->_data[$name] as $i => $relation_instance) {
                            $id = (is_object($relation_instance)) ? $relation_instance->id : $relation_instance;
                            $shadow_id = (is_object($this->_shadow_data[$name][$i])) ? $this->_shadow_data[$name][$i]->id : $this->_shadow_data[$name][$i];

                            // as soon as one instance is different, return TRUE
                            if ($id != $shadow_id) {
                                return TRUE;
                            }
                        }
                    }
                }
                // ONE-relation
                else {
                    $id = (is_object($this->_data[$name])) ? $this->_data[$name]->id : $this->_data[$name];
                    $shadow_id = (is_object($this->_shadow_data[$name])) ? $this->_shadow_data[$name]->id : $this->_shadow_data[$name];

                    // compare IDs, if they do not match, relation was modified
                    return $id != $shadow_id;
                }
            }
            // property is a regular field so compare values
            else {
                return $this->_data[$name] != $this->_shadow_data[$name];
            }
        }
        // if property is not in the initial copy it is definitely modified
        else {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Set modified state of a property
     *
     * @param string $name
     * @param bool $modified
     */
    public function setPropertyModified($name, $modified)
    {
        if ($modified) {
            if (isset($this->_shadow_data[$name])) {
                unset($this->_shadow_data[$name]);
            }
        }
        else {
            $this->_shadow_data[$name] = $this->_data[$name];
        }
    }

    /**
     * Return a list of properties that have been modified since the initial population of the model.
     *
     * @return array list of property names
     */
    public function getModifiedProperties()
    {
        $modified = array();

        $fields = $this->getFields();

        foreach($fields as $field) {
            if ($this->isPropertyModified($field->name)) {
                $modified[] = $field->name;
            }
        }

        return $modified;
    }

    public function notNew()
    {
        $this->_new = false;
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
                switch($field->type) {
                    case Field::TYPE_DATE:
                        $array[$name] = (is_object($this->_data[$name])) ? \FluxAPI\Utils::dateToString($this->_data[$name]) : $this->_data[$name];
                        break;

                    case Field::TYPE_DATETIME:
                        $array[$name] = (is_object($this->_data[$name])) ? \FluxAPI\Utils::dateTimeToString($this->_data[$name]) : $this->_data[$name];
                        break;

                    default:
                        $array[$name] = $this->_data[$name];
                }
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
}
