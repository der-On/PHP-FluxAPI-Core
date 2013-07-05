<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ondrej
 * Date: 09.04.13
 * Time: 13:42
 * To change this template use File | Settings | File Templates.
 */

namespace FluxAPI;


interface StorageInterface
{
    /**
     * Returns the collection name from a given model instance
     *
     * @param string $model_name
     * @return string
     */
    public static function getCollectionName($model_name);

    /**
     * 'select' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterSelect(&$qb, array $params);

    /**
     * 'equal' or '=' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterEqual(&$qb, array $params);

    /**
     * 'not' or '!=' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterNotEqual(&$qb, array $params);

    /**
     * 'gt' or '>' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterGreaterThen(&$qb, array $params);

    /**
     * 'gte' or '>=' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterGreaterThenOrEqual(&$qb, array $params);

    /**
     * 'lt' or '<' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterLessThen(&$qb, array $params);

    /**
     * 'lte' or '<=' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterLessThenOrEqual(&$qb, array $params);

    /**
     * 'range' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterRange(&$qb, array $params);

    /**
     * 'order' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterOrder(&$qb, array $params);

    /**
     * 'limit' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterLimit(&$qb, array $params);

    /**
     * 'count' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterCount(&$qb, array $params);

    /**
     * 'like' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterLike(&$qb, array $params);

    /**
     * 'in' filter implementation.
     *
     * Override this in your storage plugin.
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterIn(&$qb, array $params);

    /**
     * 'distinct' filter implementation
     *
     * @param $qb some kind of query builder object
     * @param array $params
     */
    public function filterDistinct(&$qb, array $params);

    /**
     * Sets the internal OR flag for the next filter
     */
    public function filterOr();

    /**
     * Returns true if the internal OR flag is set.
     *
     * @return bool
     */
    public function isFilterOr();

    /**
     * Loads a single or a list of related models of a model instance.
     *
     * Override this in your storage plugin.
     *
     * @param Model $model
     * @param string $name name of the relation field in the model instance
     * @return Model|array
     */
    public function loadRelation(Model $model, $name);

    /**
     * Makes a model to model relation persistante in the storage
     *
     * Override this in your storage plugin.
     *
     * @param Model $model
     * @param Model $relation
     * @param Field $field the relation field in the $model
     */
    public function addRelation(\FluxAPI\Model $model, \FluxAPI\Model $relation, \FluxAPI\Field $field);

    /**
     * Removes a model to model relation from the storage.
     *
     * @param Model $model
     * @param Model $relation
     * @param Field $field the relation field in the $model
     */
    public function removeRelation(\FluxAPI\Model $model, \FluxAPI\Model $relation, \FluxAPI\Field $field);

    /**
     * Removes all model to model relations from the storage
     *
     * Override this in your storage plugin.
     *
     * @param Model $model
     * @param Field $field the relation field in the $model
     * @param [array $exclude_ids] if set related models with the given IDs are not removed
     */
    public function removeAllRelations(\FluxAPI\Model $model, \FluxAPI\Field $field, array $exclude_ids = array());

    /**
     * @param string $model_name
     * @param \FluxAPI\Query $query
     * @return null|array
     */
    public function getCachedModels($model_name, \FluxAPI\Query $query = NULL);

    /**
     * @param string $model_name
     * @param Query $query
     * @param Collection\ModelCollection $models
     */
    public function cacheModels($model_name, \FluxAPI\Query $query = NULL, \FluxAPI\Collection\ModelCollection $models);

    /**
     * @param string $model_name
     * @param Query $query
     * @param Model $model
     */
    public function cacheModel($model_name, \FluxAPI\Query $query = NULL, \FluxAPI\Model $model);

    /**
     * @param string $model_name
     * @param Query $query
     * @param Collection\ModelCollection $models
     */
    public function removeCachedModels($model_name, \FluxAPI\Query $query = NULL, \FluxAPI\Collection\ModelCollection $models = NULL);

    /**
     * @param string $model_name
     * @param Query $query
     * @param Model $model
     */
    public function removeCachedModel($model_name, \FluxAPI\Query $query = NULL, \FluxAPI\Model $model);

    /**
     * Implement this in your Storage adapter
     *
     * @param Model $model
     * @param string $name relation field name
     */
    public function removeCachedRelationModels(\FluxAPI\Model $model, $name);

    /**
     * Checks if the storage plugin is already connected to the storage host (e.g. Database)
     *
     * Override this in your storage plugin.
     *
     * @return bool
     */
    public function isConnected();

    /**
     * Connects the storage plugin to the storage host (e.g. Database)
     *
     * Override this in your storage plugin.
     */
    public function connect();

    /**
     * Returns a handler to the storage host (e.g. Database)
     *
     * Override this in your storage plugin.
     *
     * @return mixed
     */
    public function getConnection();

    /**
     * Returns a new model id.
     *
     * @return string
     */
    public function getNewId();

    /**
     * Migrates the storage structure to all or a single model definition
     *
     * Override this in your storage plugin.
     *
     * @param [string $model]
     */
    public function migrate($model_name = null);

    /**
     * Adds/registers a new filter
     *
     * @chainable
     * @param string $name
     * @param string $callback
     * @return Storage $this
     */
    public function addFilter($name,$callback);

    /**
     * Checks if a filter exists
     *
     * @param string $name
     * @return bool
     */
    public function hasFilter($name);

    /**
     * Returns a filters callback
     *
     * @param string $name
     * @return string|null callback string or null if not found
     */
    public function getFilter($name);

    /**
     * Returns all existing filters
     *
     * @return array
     */
    public function getFilters();

    /**
     * Executes the callback of a given filter
     *
     * @param string $callback
     * @param array $params parameters passed to the $callback function
     * @return mixed
     */
    public function executeFilter($callback,array $params = array());

    /**
     * Returns the total count of a model (or the model by a query)
     *
     * @param string $model_name
     * @param [Query $query]
     * @return int
     */
    public function count($model_name, Query $query = null);

    /**
     * Checks if a given model instance already exists in the storage
     *
     * @param string $model_name
     * @param Model $instance
     * @return bool
     */
    public function exists($model_name, Model $instance);

    /**
     * Saves/updates a given model instance to the storage
     *
     * @param string $model_name
     * @param Model $model
     * @return bool
     */
    public function save($model_name, Model $model);

    /**
     * Loads instances of a model from the storage
     *
     * @param string $model_name
     * @param [Query $query] if not set all model instances will be loaded
     * @return array
     */
    public function load($model_name, Query $query = null);

    /**
     * Updates a list of models in the storage with given data
     *
     * @param string $model_name
     * @param [Query $query] if null all models will be updated
     * @param array $data
     * @return bool
     */
    public function update($model_name, Query $query = null, array $data = array());

    /**
     * Deletes a list of models from the storage
     *
     * @param string $model_name
     * @param [Query $query] if null all models will be deleted
     * @return bool
     */
    public function delete($model_name, Query $query = null);

    /**
     * Executes a query
     *
     * Override this in your storage plugin.
     *
     * @param Query $query
     * @return mixed
     */
    public function executeQuery(Query $query);

    /**
     * Converts the given data to a serialized string
     *
     * @param mixed $data
     * @param Field $field
     */
    public function serialize($data, \FluxAPI\Field $field);

    /**
     * Converts a serialized string to a real datatype
     *
     * @param string $str
     * @param Field $field
     */
    public function unserialize($str, \FluxAPI\Field $field);
}