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
     * Returns last inserted ID of a given model type
     *
     * Override this in your storage plugin.
     *
     * @param string $model_name
     * @return mixed
     */
    public function getLastId($model_name);

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
     * Migrates the storage structure to all or a single model definition
     *
     * Override this in your storage plugin.
     *
     * @param [string $model]
     */
    public function migrate($model_name = NULL);
}