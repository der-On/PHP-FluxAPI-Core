<?php
namespace FluxAPI\Factory;

use \FluxAPI\Cache;
use \FluxAPI\Cache\CachOptions;
use \FluxAPI\Cache\CacheSource;

class CacheFactory extends \Pimple
{
    protected $_api;

    protected $_caches = NULL;

    protected $_disabled = array();

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
        $this['plugins'] = $api['plugins'];
    }

    public function getCache($cache_name)
    {
        $cache = $this->getCacheClass($cache_name);

        if ($cache) {
            if (!isset($this->_caches[$cache_name])) {
                $this->_caches[$cache_name] = new $cache($this->_api);
            }

            return $this->_caches[$cache_name];
        }

        return NULL;
    }

    public function getCacheClass($cache_name)
    {
        return $this['plugins']->getPluginClass('Cache', $cache_name);
    }

    /**
     * Returns a cached resource
     *
     * @param string $type Resource type
     * @param CacheSource $source a source object used to create a unique relation to a possibly cached resource
     * @param CacheOptions $options
     * @return mixed NULL if the resource type is not in cache
     */
    public function getCached($type, CacheSource $source, CacheOptions $options = NULL)
    {
        if ($this->isDisabled($type)) {
            return null;
        }

        $cache_names = array_keys($this['plugins']->getPlugins('Cache'));

        foreach($cache_names as $cache_name) {
            $cache = $this->getCache($cache_name);

            $resource = $cache->getCached($type, $source, $options);

            // as soon as any cache plugin returns some resource we pass it
            if ($resource !== NULL) {
                return $resource;
            }
        }

        return NULL;
    }

    /**
     * Stores a resource in the cache
     *
     * @param string $type Resource type
     * @param CacheSource $source a source object used to create a unique relation to a possibly cached resource
     * @param $resource the resource to store in cache
     * @param CacheOptions $options
     */
    public function store($type, CacheSource $source, $resource, CacheOptions $options = NULL)
    {
        if ($this->isDisabled($type)) {
            return;
        }

        $cache_names = array_keys($this['plugins']->getPlugins('Cache'));

        foreach($cache_names as $cache_name) {
            $cache = $this->getCache($cache_name);

            $cache->store($type, $source, $resource, $options);
        }
    }

    /**
     * Removes resources related to a source form the cache
     *
     * @param string $type
     * @param CacheSource $source
     * @param CacheOptions $options
     */
    public function remove($type, CacheSource $source, CacheOptions $options = NULL)
    {
        if ($this->isDisabled($type)) {
            return;
        }

        $cache_names = array_keys($this['plugins']->getPlugins('Cache'));

        foreach($cache_names as $cache_name) {
            $cache = $this->getCache($cache_name);

            $cache->remove($type, $source);
        }
    }

    /**
     * Clears the entire cache of a type
     *
     * @param string $type
     */
    public function clear($type)
    {
        if ($this->isDisabled($type)) {
            return;
        }

        $cache_names = array_keys($this['plugins']->getPlugins('Cache'));

        foreach($cache_names as $cache_name) {
            $cache = $this->getCache($cache_name);

            $cache->clear($type);
        }
    }

    /**
     * Clears all caches
     */
    public function clearAll()
    {
        $types = array(Cache::TYPE_PERMISSION, Cache::TYPE_MODEL, Cache::TYPE_CONTROLLER_ACTION, Cache::TYPE_QUERY, Cache::TYPE_RESPONSE);

        foreach($types as $type) {
            $this->clear($type);
        }
    }

    /**
     * @param string $type cache type to disable
     */
    public function disable($type)
    {
        if (!isset($this->_disabled[$type])) {
            $this->_disabled[$type] = true;
        }
    }

    /**
     * @param string $type cache type to enable
     */
    public function enable($type)
    {
        if (isset($this->_disabled[$type])) {
            $this->_disabled[$type] = false;
        }
    }

    public function isDisabled($type)
    {
        return (isset($this->_disabled[$type]) && $this->_disabled[$type]);
    }
}