<?php

namespace FluxAPI\Factory;

class MethodFactory
{
    protected $_api;

    /**
     * @var array Internal lookup for magic methods
     */
    private $_methods = array();

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }

    /**
     * Registers a magic method
     *
     * @param string $method
     * @param mixed $callback
     * @return Api $this
     */
    public function registerMethod($method, $callback)
    {
        if (!isset($this->_methods[$method]) && is_callable($callback)) {
            $this->_methods[$method] = $callback;
        }

        return $this; // make it chainable
    }

    /**
     * Unregisters a magic method
     *
     * @param string $method
     * @return Api $this
     */
    public function unregisterMethod($method)
    {
        if (!isset($this->_methods[$method])) {
            unset($this->_methods[$method]);
        }

        return $this; // make it chainable
    }

    public function hasMethod($method)
    {
        return (isset($this->_methods[$method]) && is_callable($this->_methods[$method]));
    }

    public function getMethod($method)
    {
        if (isset($this->_methods[$method])) {
            return $this->_methods[$method];
        } else {
            return NULL;
        }
    }

    public function callMethod($method, array $arguments)
    {
        if ($this->hasMethod($method)) {
            $callback = $this->getMethod($method);
            return call_user_func_array($callback, $arguments);
        }
        else {
            throw new \ErrorException(sprintf('There is no "%s" method registered.', $method));
        }
        return NULL;
    }
}