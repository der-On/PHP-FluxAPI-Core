<?php
namespace FluxAPI;

use \Doctrine\DBAL\Query\QueryBuilder;
use \FluxAPI\Field;

/**
 * Data storage plugin
 *
 * All your storage plugins must inherit from this
 *
 * @package FluxAPI
 */
abstract class Storage implements StorageInterface
{
    /**
     * @var Api Internal Api instance
     */
    protected $_api = NULL;

    /**
     * @var array Internal list of registered filters
     */
    protected $_filters = array();

    /**
     * @var array Configuration
     */
    public $config = array();

    /**
     * Constructor
     *
     * @param Api $api
     * @param array $config
     */
    public function __construct(Api $api, array $config = array())
    {
        $this->config = array_replace_recursive($this->config,$config);
        $this->_api = $api;

        $this->addFilters();
    }

    /**
     * Returns the collection name from a given model instance
     *
     * @param string $model_name
     * @return string
     */
    public static function getCollectionName($model_name)
    {
        return strtolower($model_name);
    }

    /**
     * Returns a new model ID.
     *
     * @return string
     */
    public function getNewId()
    {
        return UUID::v4();
    }


    /**
     * Adds/registers all filters available in this storage
     *
     * Use this method in child classes to add aditional filters while calling parent::addFilters().
     *
     * Default filters are:
     *
     *  - select
     *  - equal or =
     *  - not or !=
     *  - gt or >
     *  - gte or >=
     *  - lt or <
     *  - lte or <=
     *  - range
     *  - order
     *  - limit
     *  - count
     *  - like
     *  - in
     *
     * Your storage plugin should at least implement those filters.
     */
    public function addFilters()
    {
        $this->addFilter('select','filterSelect')
            ->addFilter('equal','filterEqual')
            ->addFilter('=','filterEqual')
            ->addFilter('not','filterNotEqual')
            ->addFilter('!=','filterNotEqual')
            ->addFilter('gt','filterGreaterThen')
            ->addFilter('>','filterGreaterThen')
            ->addFilter('gte','filterGreaterThenOrEqual')
            ->addFilter('>=','filterGreaterThenOrEqual')
            ->addFilter('lt','filterLessThen')
            ->addFilter('<','filterLessThen')
            ->addFilter('lte','filterLessThenOrEqual')
            ->addFilter('<=','filterLessThenOrEqual')
            ->addFilter('range','filterRange')
            ->addFilter('order','filterOrder')
            ->addFilter('limit','filterLimit')
            ->addFilter('count','filterCount')
            ->addFilter('like','filterLike')
            ->addFilter('in','filterIn')
            ->addFilter('distinct', 'filterDistinct')
            ;
    }

    /**
     * Adds/registers a new filter
     *
     * @chainable
     * @param string $name
     * @param string $callback
     * @return Storage $this
     */
    public function addFilter($name,$callback)
    {
        if (!$this->hasFilter($name)) {
            $this->_filters[$name] = $callback;
        }
        return $this; // make it chainable
    }

    /**
     * Checks if a filter exists
     *
     * @param string $name
     * @return bool
     */
    public function hasFilter($name)
    {
        return (isset($this->_filters[$name]) && !empty($this->_filters[$name]));
    }

    /**
     * Returns a filters callback
     *
     * @param string $name
     * @return string|null callback string or null if not found
     */
    public function getFilter($name)
    {
        if ($this->hasFilter($name)) {
            return $this->_filters[$name];
        } else {
            return NULL;
        }
    }

    /**
     * Returns all existing filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    /**
     * Executes the callback of a given filter
     *
     * @param string $callback
     * @param array $params parameters passed to the $callback function
     * @return mixed
     */
    public function executeFilter($callback,array $params = array())
    {
        return call_user_func_array(array($this,$callback),$params);
    }

    /**
     * Returns the total count of a model (or the model by a query)
     *
     * @param string $model_name
     * @param [Query $query]
     * @return int
     */
    public function count($model_name, Query $query = NULL)
    {
        if (empty($query)) {
            $query = new Query();
        }

        $query->setModelName($model_name);
        $query->setType(Query::TYPE_COUNT);

        $result = $this->executeQuery($query);
        return $result;
    }

    /**
     * Checks if a given model instance already exists in the storage
     *
     * @param string $model_name
     * @param Model $instance
     * @return bool
     */
    public function exists($model_name, Model $instance)
    {
        if (isset($instance->id) && !empty($instance->id)) {
            $query = new Query();
            $query->setType(Query::TYPE_COUNT);
            $query->setModelName($model_name);
            $query->filter('equal',array('id',$instance->id));

            $result = $this->executeQuery($query);
            return $result > 0;
        }

        return FALSE;
    }

    /**
     * Saves/updates a given model instance to the storage
     *
     * @param string $model_name
     * @param Model $instance
     * @param array $relations_to_save if set, a list of relation field names to save
     * @return bool
     */
    public function save($model_name, Model $instance, array $relations_to_save = NULL)
    {
        // if the model is new we have to set it's ID
        if ($instance->isNew()) {
            $instance->id = $this->getNewId();
        }

        $query = new Query();
        $query->setType(Query::TYPE_INSERT);

        if ($this->exists($model_name, $instance)) {
            $query->setType(Query::TYPE_UPDATE)
                  ->filter('equal', array('id', $instance->id));
        }

        $query->setModelName($model_name);

        // only save modified properties
        $modified_properties = $instance->getModifiedProperties();

        // if nothing was modified we do not need to save the model at all
        if (count($modified_properties) == 0) {
            return TRUE;
        }

        $data = $instance->toArray();

        foreach($data as $name => $value) {
            if (!in_array($name, $modified_properties)) {
                unset($data[$name]);
            }
        }

        // do not execute query with no data to store
        if (count($data) > 0) {
            $query->setData($data);
            $success = $this->executeQuery($query);
        }
        else {
            $success = TRUE;
        }

        // save relations
        $relation_fields = $instance->getRelationFields(); // collect all fields representing a relation to another model

        foreach($relation_fields as $relation_field) {
            // skip relations that should not be saved
            if ($relations_to_save !== NULL && !in_array($relation_field->name, $relations_to_save)) {
                continue;
            }

            // do not save unmodified relations
            if (!in_array($relation_field->name, $modified_properties)) {
                continue;
            }

            $relation_instances = new \FluxAPI\Collection\ModelCollection();
            $added_relation_ids = array();

            $field_name = $relation_field->name;

            if (isset($instance->$field_name)) { // check if the instance has one or multiple related models
                if (\FluxAPI\Collection\ModelCollection::isInstance($instance->$field_name)) {
                    $relation_instances = $instance->$field_name;
                } else {
                    $relation_instances->push($instance->$field_name);
                }
            }

            // HAS-ONE relation has to be removed before an update to prevent duplicate entries of same relation in the database
            if($relation_field->relationType == Field::HAS_ONE) {
                $this->removeAllRelations($instance, $relation_field);
            }

            // after all related models have been collected we need to store the relation
            foreach($relation_instances as $relation_instance) {
                // relation is stored as simple ID and no object so we have to load it
                if ($relation_instance !== FALSE && !empty($relation_instance) && (is_string($relation_instance) || is_numeric($relation_instance))) {
                    $relation_instance = $this->_api->loadFirst($relation_field->relationModel, $relation_instance);
                }

                if ($relation_instance !== FALSE && !empty($relation_instance)) {
                    if ($relation_instance->isNew()) { // if the related model instance is new, it needs to be saved first
                        $this->_api->save($relation_field->relationModel, $relation_instance);
                    }

                    $added_relation_ids[] = $relation_instance->id;

                    $this->addRelation($instance, $relation_instance, $relation_field); // now we can store the relation to this model
                }
            }

            // remove has-many relations that have been there before but keep the currently added
            if (in_array($relation_field->relationType, array(Field::HAS_MANY))) {
                $this->removeAllRelations($instance, $relation_field, $added_relation_ids);
            }
        }

        $instance->notNew();

        return $success;
    }

    /**
     * Loads instances of a model from the storage
     *
     * @param string $model_name
     * @param [Query $query] if not set all model instances will be loaded
     * @return array
     */
    public function load($model_name, Query $query = NULL)
    {
        if (empty($query)) {
            $query = new Query();
        }
        $query->setType(Query::TYPE_SELECT);
        $query->setModelName($model_name);

        $instances = $this->executeQuery($query);

        foreach($instances as $instance) {
            $instance->notNew();
        }

        return $instances;
    }

    /**
     * Updates a list of models in the storage with given data
     *
     * @param string $model_name
     * @param [Query $query] if null all models will be updated
     * @param array $data
     * @return bool
     */
    public function update($model_name, Query $query = NULL, array $data = array())
    {
        if (empty($query)) {
            $query = new Query();
        }

        $models = $this->load($model_name, $query);

        // only save relations if there are relation fields in the given data
        $relations_to_save = array();
        $data_keys = array_keys($data);

        if (count($models) > 0) {
            $relation_fields = $models[0]->getRelationFields();

            foreach($relation_fields as $relation_field) {
                if (in_array($relation_field->name, $data_keys)) {
                    $relations_to_save[] = $relation_field->name;
                }
            }
        }

        foreach($models as $model) {
            $model->populate($data);
            $this->save($model_name, $model, $relations_to_save);
        }

        return $models;
    }

    /**
     * Deletes a list of models from the storage
     *
     * @param string $model_name
     * @param [Query $query] if null all models will be deleted
     * @return bool
     */
    public function delete($model_name, Query $query = NULL)
    {
        if (empty($query)) {
            $query = new Query();
        }
        $query->setType(Query::TYPE_DELETE);
        $query->setModelName($model_name);

        // TODO: clear relations table too

        return $this->executeQuery($query);
    }

    /**
     * Executes a query
     *
     * Override this in your storage plugin.
     *
     * @param Query $query
     * @return mixed
     */
    public function executeQuery(Query $query)
    {
        $query->setStorage($this);

        if (!$this->isConnected()) {
            $this->connect();
        }

        return NULL;
    }

    /**
     * Converts the given data to a serialized string
     *
     * @param mixed $data
     * @param Field $field
     */
    public function serialize($data, \FluxAPI\Field $field)
    {
        if (in_array($field->type,array(Field::TYPE_ARRAY, Field::TYPE_OBJECT))) {
            return serialize($data);
        } else {
            switch($field->type) {
                case Field::TYPE_DATE:
                    return (is_object($data)) ? \FluxAPI\Utils::dateToString($data) : $data; break;

                case Field::TYPE_DATETIME:
                    return (is_object($data)) ? \FluxAPI\Utils::dateTimeToString($data) : $data; break;

                case Field::TYPE_BOOLEAN:
                    return ($data)?1:0; break;

                default:
                    return (string) $data;
            }
        }

        return NULL;
    }

    /**
     * Converts a serialized string to a real datatype
     *
     * @param string $str
     * @param Field $field
     */
    public function unserialize($str, \FluxAPI\Field $field)
    {
        if (in_array($field->type,array(Field::TYPE_ARRAY, Field::TYPE_OBJECT))) {
            return unserialize($str);
        } else {
            switch($field->type) {
                case Field::TYPE_INTEGER:
                    return intval($str); break;

                case Field::TYPE_TIMESTAMP:
                    return intval($str); break;

                case Field::TYPE_FLOAT:
                    return floatval($str); break;

                case Field::TYPE_DATE:
                    return \FluxAPI\Utils::dateTimeFromString($str); break;

                case Field::TYPE_DATETIME:
                    return \FluxAPI\Utils::dateTimeFromString($str); break;

                case Field::TYPE_BOOLEAN:
                    return ($str != '0' || $str != 0 || !empty($str))?TRUE:FALSE; break;

                default:
                    return $str;
            }
        }
    }
}
