<?php
namespace FluxAPI;

use \FluxAPI\Cache\CacheOptions;
use \FluxAPI\Cache\CacheSource;

interface CacheInterface
{
    /**
     * Returns a cached resource
     *
     * @param string $type Resource type
     * @param CacheSource $source a source object used to create a unique relation to a possibly cached resource
     * @param CacheOptions $options
     * @return mixed NULL if the resource type is not in cache
     */
    public function getCached($type, CacheSource $source, CacheOptions $options = NULL);

    /**
     * Stores a resource in the cache
     *
     * @param string $type Resource type
     * @param CacheSource $source a source object used to create a unique relation to a possibly cached resource
     * @param $resource the resource to store in cache
     * @param CacheOptions $options
     */
    public function store($type, CacheSource $source, $resource, CacheOptions $options = NULL);

    /**
     * Removes a resource related to a source form the cache
     *
     * @param string $type
     * @param CacheSource $source
     * @param CacheOptions $options
     */
    public function remove($type, CacheSource $source, CacheOptions $options = NULL);

    /**
     * Clears the entire cache
     *
     * @param string $type
     */
    public function clear($type);
}