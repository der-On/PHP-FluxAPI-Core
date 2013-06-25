<?php
namespace FluxAPI\Cache;

abstract class CacheSource
{
    /**
     * Returns a unique hash string from the source
     *
     * @return string
     */
    public function toHash()
    {
        return md5(serialize($this));
    }
}