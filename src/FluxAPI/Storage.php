<?php
namespace FluxAPI;

use \Doctrine\DBAL\Query\QueryBuilder;

/**
 * Data storage plugin
 *
 * All your storage plugins must inherit from this
 *
 * @package FluxAPI
 */
abstract class Storage
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
            ->addFilter('in','filterIn');
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
     * 'select' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterSelect(&$qb, array $params)
    {

    }

    /**
     * 'equal' or '=' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterEqual(&$qb, array $params)
    {

    }

    /**
     * 'not' or '!=' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterNotEqual(&$qb, array $params)
    {

    }

    /**
     * 'gt' or '>' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterGreaterThen(&$qb, array $params)
    {

    }

    /**
     * 'gte' or '>=' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterGreaterThenOrEqual(&$qb, array $params)
    {

    }

    /**
     * 'lt' or '<' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterLessThen(&$qb, array $params)
    {

    }

    /**
     * 'lte' or '<=' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterLessThenOrEqual(&$qb, array $params)
    {

    }

    /**
     * 'range' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterRange(&$qb, array $params)
    {

    }

    /**
     * 'order' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterOrder(&$qb, array $params)
    {

    }

    /**
     * 'limit' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterLimit(&$qb, array $params)
    {

    }

    /**
     * 'count' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterCount(&$qb, array $params)
    {

    }

    /**
     * 'like' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterLike(&$qb, array $params)
    {

    }

    /**
     * 'in' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterIn(&$qb, array $params)
    {

    }

    /**
     * Returns last inserted ID of a given model type
     *
     * Override this in your storage plugin.
     *
     * @param string $model_name
     * @return mixed
     */
    public function getLastId($model_name)
    {
        return NULL;
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
     * @return bool
     */
    public function save($model_name, Model $instance)
    {
        $query = new Query();
        $query->setType(Query::TYPE_INSERT);

        if ($this->exists($model_name, $instance)) {
            $query->setType(Query::TYPE_UPDATE);
        }

        $query->setModelName($model_name);
        $query->setData($instance->toArray());

        $success = $this->executeQuery($query);

        // if the model was new we have to set it's ID
        if ($instance->isNew()) {
            $instance->id = $this->getLastId($model_name);
        }

        // save relations

        $relation_fields = $instance->getRelationFields(); // collect all field representing a relation to another model

        foreach($relation_fields as $relation_field) {
            $relation_instances = array();
            $added_relation_ids = array();

            $field_name = $relation_field->name;

            if (isset($instance->$field_name)) { // check if the instance has one or multiple related models

                if (in_array($relation_field->relationType, array(Field::BELONGS_TO_ONE,Field::HAS_ONE))) {
                    $relation_instances[] = $instance->$field_name;
                } else {
                    $relation_instances = $instance->$field_name;
                }
            }

            // after all related models have been collected we need to store the relation
            foreach($relation_instances as $i => $relation_instance) {
                if (!empty($relation_instance)) {
                    if ($relation_instance->isNew() || $relation_instance->isModified()) { // if the related model instance is new, it needs to be saved first
                        $this->save($relation_instance->getModelName(),$relation_instance);
                    }

                    $added_relation_ids[] = $relation_instance->id;

                    $this->addRelation($instance, $relation_instance, $relation_field); // now we can store the relation to this model
                }
            }

            // remove relations that have been there before and have not been added now
            $this->removeAllRelations($instance,$relation_field,$added_relation_ids);
        }

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
        return $this->executeQuery($query);
    }

    /**
     * Loads a single or a list of related models of a model instance.
     *
     * Override this in your storage plugin.
     *
     * @param Model $model
     * @param string $name name of the relation field in the model instance
     * @return Model|array
     */
    public function loadRelation(Model $model, $name)
    {
        return NULL;
    }

    /**
     * Makes a model to model relation persistante in the storage
     *
     * Override this in your storage plugin.
     *
     * @param Model $model
     * @param Model $relation
     * @param Field $field the relation field in the $model
     */
    public function addRelation(\FluxAPI\Model $model, \FluxAPI\Model $relation, \FluxAPI\Field $field)
    {

    }

    /**
     * Removes a model to model relation from the storage.
     *
     * @param Model $model
     * @param Model $relation
     * @param Field $field the relation field in the $model
     */
    public function removeRelation(\FluxAPI\Model $model, \FluxAPI\Model $relation, \FluxAPI\Field $field)
    {

    }

    /**
     * Removes all model to model relations from the storage
     *
     * Override this in your storage plugin.
     *
     * @param Model $model
     * @param Field $field the relation field in the $model
     * @param [array $exclude_ids] if set related models with the given IDs are not removed
     */
    public function removeAllRelations(\FluxAPI\Model $model, \FluxAPI\Field $field, array $exclude_ids = array())
    {

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
        $query->setType(Query::TYPE_UPDATE);
        $query->setModelName($model_name);
        $query->setData($data);
        return $this->executeQuery($query);
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
     * Checks if the storage plugin is already connected to the storage host (e.g. Database)
     *
     * Override this in your storage plugin.
     *
     * @return bool
     */
    public function isConnected()
    {
        return FALSE;
    }

    /**
     * Connects the storage plugin to the storage host (e.g. Database)
     *
     * Override this in your storage plugin.
     */
    public function connect()
    {

    }

    /**
     * Returns a handler to the storage host (e.g. Database)
     *
     * Override this in your storage plugin.
     *
     * @return mixed
     */
    public function getConnection()
    {
        return NULL;
    }

    /**
     * Migrates the storage structure to all or a single model definition
     *
     * Override this in your storage plugin.
     *
     * @param [string $model]
     */
    public function migrate($model_name = NULL)
    {

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
                    return (string) $data; break;

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
                    return new DateTime($str); break;

                default:
                    return $str;
            }
        }
    }
}
