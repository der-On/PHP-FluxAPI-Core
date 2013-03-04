<?php
namespace FluxAPI;

class Query
{
    private $_filters = array();
    private $_model = NULL;
    private $_storage = NULL;
    private $_type = NULL;
    private $_data = array();

    const TYPE_UPDATE = 'update';
    const TYPE_INSERT = 'insert';
    const TYPE_DELETE = 'delete';
    const TYPE_SELECT = 'select';
    const TYPE_COUNT = 'count';

    public function __construct()
    {

    }

    public function filter($name, array $params)
    {
        $this->_filters[] = array($name,$params);
        return $this;
    }

    public function execute()
    {
        return $this->_storage->executeQuery($this);
    }

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

    public function setStorage(Storage $storage)
    {
        $this->_storage = $storage;
    }

    public function getStorage()
    {
        return $this->_storage;
    }

    public function setModel($model)
    {
        $this->_model = $model;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function setType($type)
    {
        if (in_array($type, array(self::TYPE_DELETE, self::TYPE_INSERT, self::TYPE_UPDATE, self::TYPE_SELECT, self::TYPE_COUNT))) {
            $this->_type = $type;
        }
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setData(array $data = array())
    {
        $this->_data = array_replace($this->_data,$data);
    }

    public function hasData($field = NULL)
    {
        if (empty($field)) {
            return count($this->_data) > 0;
        } else {
            return isset($this->_data[$field]);
        }
        return FALSE;
    }

    public function getData($field = NULL)
    {
        if (empty($field)) {
            return $this->_data;
        } elseif ($this->hasData($field)) {
            return $this->_data[$field];
        }

        return NULL;
    }
}
