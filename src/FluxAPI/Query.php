<?php
namespace FluxAPI;

class Query
{
    private $_filters = array();
    private $_model = NULL;
    private $_storage = NULL;
    private $_type = NULL;

    const TYPE_UPDATE = 'update';
    const TYPE_INSERT = 'insert';
    const TYPE_DELETE = 'delete';
    const TYPE_SELECT = 'select';

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

    public function getFilters()
    {
        return $this->_filters;
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
        if (in_array($type, array(self::TYPE_DELETE, self::TYPE_INSERT, self::TYPE_UPDATE, self::TYPE_SELECT))) {
            $this->_type = $type;
        }
    }

    public function getType()
    {
        return $this->_type;
    }
}
