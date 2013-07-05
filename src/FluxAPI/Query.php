<?php
namespace FluxAPI;

/**
 * A Storage Query
 * @package FluxAPI
 */
class Query
{
    /**
     * @var array Internal list of added filters
     */
    private $_filters = array();

    /**
     * @var string Model this query is related to
     */
    private $_modelName = NULL;

    /**
     * @var Storage the storage using this query
     */
    private $_storage = NULL;

    /**
     * @var string The query type
     */
    private $_type = NULL;

    /**
     * @var array Internal values for a INSERT or UPDATE query
     */
    private $_data = array();

    const TYPE_UPDATE = 'update';
    const TYPE_INSERT = 'insert';
    const TYPE_DELETE = 'delete';
    const TYPE_SELECT = 'select';
    const TYPE_COUNT = 'count';

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Adds a new query filter
     *
     * @chainable
     * @param string $name
     * @param array $params
     * @return Query $this
     */
    public function filter($name, array $params = array())
    {
        $this->_filters[] = array($name,$params);

        return $this;
    }

    /**
     * Executes the query and returns the query results (if any)
     *
     * @return mixed
     */
    public function execute()
    {
        return $this->_storage->executeQuery($this);
    }

    /**
     * Returns a list of query filters
     *
     * @param [string $name] if set it will return all filters of same name
     * @return array
     */
    public function getFilters($name = NULL)
    {
        if (empty($name)) {
            return $this->_filters;
        } else {
            $filters = array();

            foreach($this->_filters as $filter) {
                if ($filter[0] == $name) {
                    $filters[] = &$filter;
                }
            }

            return $filters;
        }
    }

    /**
     * Checks if the query has filter(s) of a given name
     *
     * @param string $name
     * @return bool
     */
    public function hasFilter($name)
    {
        if (!empty($name)) {
            foreach($this->_filters as $filter) {
                if ($filter[0] == $name) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    /**
     * Sets the storage this query belongs to
     *
     * @param Storage $storage
     */
    public function setStorage(Storage $storage)
    {
        $this->_storage = $storage;

        return $this;
    }

    /**
     * Returns the storage this query belongs to
     *
     * @return Storage
     */
    public function getStorage()
    {
        return $this->_storage;
    }

    /**
     * Sets the model this query is related with
     *
     * @param string $model
     */
    public function setModelName($model)
    {
        $this->_modelName = $model;

        return $this;
    }

    /**
     * Returns the model this query is related with
     *
     * @return string
     */
    public function getModelName()
    {
        return $this->_modelName;
    }

    /**
     * Sets the query type
     *
     * @param string $type Possible values are: Query::TYPE_INSERT, Query::TYPE_UPDATE, Query::TYPE_DELETE, Query::TYPE_SELECT, Query::TYPE_COUNT
     */
    public function setType($type)
    {
        if (in_array($type, array(self::TYPE_DELETE, self::TYPE_INSERT, self::TYPE_UPDATE, self::TYPE_SELECT, self::TYPE_COUNT))) {
            $this->_type = $type;
        }

        return $this;
    }

    /**
     * Returns the query type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Sets the values for insert and update queries
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->_data = array_replace($this->_data,$data);

        return $this;
    }

    /**
     * Sets only a specifique field in the query data.
     *
     * @param $name
     * @param $value
     */
    public function setDataField($name, $value)
    {
        $this->_data[$name] = $value;
    }

    /**
     * Checks if this query has values
     *
     * @param [string $field] if set it will check if a value for a given field name is present
     * @return bool
     */
    public function hasData($field = NULL)
    {
        if (empty($field)) {
            return count($this->_data) > 0;
        } else {
            return isset($this->_data[$field]);
        }
        return FALSE;
    }

    /**
     * Returns the values of this query
     *
     * @param [string $field] if set only the value of a given field name will be returned
     * @return array|mixed|null
     */
    public function getData($field = NULL)
    {
        if (empty($field)) {
            return $this->_data;
        } elseif ($this->hasData($field)) {
            return $this->_data[$field];
        }

        return NULL;
    }

    public function toArray()
    {
        return array(
            'type' => $this->getType(),
            'modelName' => $this->getModelName(),
            'filters' => $this->getFilters(),
            'data' => $this->getData()
        );
    }

    public function toString()
    {
        $arr = $this->toArray();
        unset($arr['data']);
        return json_encode($arr);
    }

    public function __toString()
    {
        return $this->toString();
    }
}
