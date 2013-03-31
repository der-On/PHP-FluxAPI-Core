<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ondrej
 * Date: 31.03.13
 * Time: 21:10
 * To change this template use File | Settings | File Templates.
 */

namespace FluxAPI;


class StorageFactory extends \Pimple
{
    protected $_api;

    public function __construct(Api $api)
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
        $storagePlugins = $this->_api->getPlugins('Storage');

        // get default storage plugin
        $storageClass = $storagePlugins[$this->_api->config['storage.plugin']];

        // keep instance of storage class for reuse
        if (!isset($this[$storageClass])) {
            $this[$storageClass] = new $storageClass($this->_api,$this->_api->config['storage.options']);
        }

        return $this[$storageClass];
    }
}