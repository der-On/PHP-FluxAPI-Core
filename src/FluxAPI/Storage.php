<?php
namespace FluxAPI;

use \Doctrine\DBAL\Query\QueryBuilder;

abstract class Storage
{
    protected $_api = NULL;
    protected $_filters = array();
    public $config = array();

    public function __construct(Api $api, array $config = array())
    {
        $this->config = array_replace_recursive($this->config,$config);
        $this->_api = $api;

        $this->addFilters();
    }

    public static function getCollectionName($model)
    {
        return strtolower($model::getModelName());
    }

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

    public function addFilter($name,$callback)
    {
        if (!$this->hasFilter($name)) {
            $this->_filters[$name] = $callback;
        }
        return $this; // make it chainable
    }

    public function hasFilter($name)
    {
        return (isset($this->_filters[$name]) && !empty($this->_filters[$name]));
    }

    public function getFilter($name)
    {
        if ($this->hasFilter($name)) {
            return $this->_filters[$name];
        } else {
            return NULL;
        }
    }

    public function getFilters()
    {
        return $this->_filters;
    }

    public function executeFilter($callback,array $params = array())
    {
        return call_user_func_array(array($this,$callback),$params);
    }

    public function filterSelect()
    {

    }

    public function filterEqual()
    {

    }

    public function filterNotEqual()
    {

    }

    public function filterGreaterThen()
    {

    }

    public function filterGreaterThenOrEqual()
    {

    }

    public function filterLessThen()
    {

    }

    public function filterLessThenOrEqual()
    {

    }

    public function filterRange()
    {

    }

    public function filterOrder()
    {

    }

    public function filterLimit()
    {

    }

    public function filterCount()
    {

    }

    public function filterLike()
    {

    }

    public function filterIn()
    {

    }

    public function count($model, Query $query = NULL)
    {
        if (empty($query)) {
            $query = new Query();
        }

        $query->setType(Query::TYPE_COUNT);

        $result = $this->executeQuery($query);
        return $result;
    }

    public function exists($model, Model $instance)
    {
        if (isset($instance->id) && !empty($instance->id)) {
            $query = new Query();
            $query->setType(Query::TYPE_COUNT);
            $query->setModel($model);
            $query->filter('equal',array('id',$instance->id));

            $result = $this->executeQuery($query);
            return $result > 0;
        }

        return FALSE;
    }

    public function save($model, Model $instance)
    {
        // save relations
        $relation_fields = $instance->getRelationFields();

        foreach($relation_fields as $relation_field) {
            $relation_instances = array();

            $field_name = $relation_field->name;
            if (isset($instance->$field_name)) {
                if (in_array($relation_field->relationType,array(Field::BELONGS_TO_ONE,Field::HAS_ONE))) {
                    $relation_instances[] = $instance->$field_name;
                } else {
                    $relation_instances = $instance->$field_name;
                }
            }

            foreach($relation_instances as $relation_instance) {
                if ($relation_instance->isNew() || $relation_instance->isModified()) {
                    $this->save($relation_instance->getModelName(),$relation_instance);

                    $this->addRelation($instance,$relation_instance);
                }
            }
        }

        $query = new Query();
        $query->setType(Query::TYPE_INSERT);

        if ($this->exists($model, $instance)) {
            $query->setType(Query::TYPE_UPDATE);
        }

        $query->setModel($model);
        $query->setData($instance->toArray());
        return $this->executeQuery($query);
    }

    public function load($model, Query $query = NULL)
    {
        if (empty($query)) {
            $query = new Query();
        }
        $query->setType(Query::TYPE_SELECT);
        $query->setModel($model);
        return $this->executeQuery($query);
    }

    public function loadRelation(Model $model, $name)
    {
        return NULL;
    }

    public function addRelation(Model $model, Model $relation)
    {

    }

    public function removeRelation(Model $model, Model $relation)
    {

    }

    public function update($model, Query $query = NULL, array $data = array())
    {
        if (empty($query)) {
            $query = new Query();
        }
        $query->setType(Query::TYPE_UPDATE);
        $query->setModel($model);
        $query->setData($data);
        return $this->executeQuery($query);
    }

    public function delete($model, Query $query = NULL)
    {
        if (empty($query)) {
            $query = new Query();
        }
        $query->setType(Query::TYPE_DELETE);
        $query->setModel($model);

        return $this->executeQuery($query);
    }

    public function executeQuery(Query $query)
    {
        $query->setStorage($this);

        if (!$this->isConnected()) {
            $this->connect();
        }

        return NULL;
    }

    public function isConnected()
    {
        return FALSE;
    }

    public function connect()
    {

    }

    public function getConnection()
    {
        return NULL;
    }

    public function migrate($model = NULL)
    {

    }
}
