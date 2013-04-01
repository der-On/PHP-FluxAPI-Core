<?php

namespace FluxAPI\Factory;


class StorageFactory extends \Pimple
{
    protected $_api;

    public function __construct(\FluxAPI\Api $api)
    {
        parent::__construct();

        $this->_api = $api;
    }

    /**
     * Returns an instance of the storage for a given model
     *
     * @param [string $model_name] if not set the default storage will be returned
     * @return Storage
     */
    public function get($model_name = NULL)
    {
        $storagePlugins = $this->_api['plugin_factory']->getPlugins('Storage');

        // get default storage plugin
        $storageClass = $storagePlugins[$this->_api->config['storage.plugin']];

        // keep instance of storage class for reuse
        if (!isset($this[$storageClass])) {
            $this[$storageClass] = new $storageClass($this->_api,$this->_api->config['storage.options']);
        }

        return $this[$storageClass];
    }
}