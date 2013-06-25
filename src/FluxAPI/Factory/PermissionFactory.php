<?php

namespace FluxAPI\Factory;


class PermissionFactory
{
    protected $_api;

    protected $_permission = array();

    protected $_access_overrides = array('*' => NULL);

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }

    public function getPermissionClass($permission_name)
    {
        return $this->_api['plugins']->getPluginClass('Permission', $permission_name);
    }

    public function getPermission($permission_name)
    {
        $permission = $this->getPermissionClass($permission_name);

        if ($permission) {
            if (!isset($this->_permissions[$permission_name])) {
                $this->_permissions[$permission_name] = new $permission($this->_api);
            }

            return $this->_permissions[$permission_name];
        }

        return NULL;
    }

    public function getDefaultAccess()
    {
        return ($this->_api->config['permission.options']['default'] == \FluxAPI\Api::PERMISSION_ALLOW) ? TRUE : FALSE;
    }

    /**
     * Overrides the access control.
     *
     * @param bool $access
     * @param [string $type]
     * @param [string $name]
     * @þaram [string $action]
     */
    public function setAccessOverride($access, $type = NULL, $name = NULL, $action = NULL)
    {
        $access = ($access) ? TRUE : FALSE;

        if ($type) {
            if ($action) {
                if (!isset($this->_access_overrides[$type])) {
                    $this->_access_overrides[$type] = array();
                }

                if(!isset($this->_access_overrides[$type][$name])) {
                    $this->_access_overrides[$type][$name] = array();
                }

                $this->_access_overrides[$type][$name][$action] = $access;
            }
            elseif ($name && isset($this->_access_overrides[$type])) {
                if (!isset($this->_access_overrides[$type])) {
                    $this->_access_overrides[$type] = array();
                }

                $this->_access_overrides[$type][$name] = $access;
            }
            elseif($type) {
                $this->_access_overrides[$type] = $access;
            }
        } else {
            $this->_access_overrides['*'] = $access;
        }
    }

    /**
     * Unsets the access override.
     *
     * @param [string $type]
     * @param [string $name]
     * @þaram [string $action]
     */
    public function unsetAccessOverride($type = NULL, $name = NULL, $action = NULL)
    {
        if ($type) {
            if ($action && isset($this->_access_overrides[$type]) && isset($this->_access_overrides[$type][$name])) {
                $this->_access_overrides[$type][$name][$action] = NULL;
            }
            elseif ($name && isset($this->_access_overrides[$type])) {
                $this->_access_overrides[$type][$name] = NULL;
            }
            elseif($type) {
                $this->_access_overrides[$type] = NULL;
            }
        } else {
            $this->_access_overrides['*'] = NULL;
        }
    }

    /**
     * @param string $model_name
     * @param \FluxAPI\Model $model
     * @param string $action
     * @return null|bool
     */
    public function getCachedModelAccess($model_name , \FluxAPI\Model $model = NULL, $action = NULL)
    {
        $source = new \FluxAPI\Cache\ModelPermissionSource($model_name, $model, $action);

        return $this->_api['caches']->getCached(\FluxAPI\Cache::TYPE_PERMISSION, $source);
    }

    /**
     * @param string $controller_name
     * @param string $action
     * @return null|bool
     */
    public function getCachedControllerAccess($controller_name , $action = NULL)
    {
        $source = new \FluxAPI\Cache\ControllerPermissionSource($controller_name, $action);

        return $this->_api['caches']->getCached(\FluxAPI\Cache::TYPE_PERMISSION, $source);
    }

    /**
     * Returns the access override for a given scope
     *
     * @param [string $type]
     * @param [string $name]
     * @þaram [string $action]
     * @return mixed - null or bool
     */
    public function getAccessOverride($type = NULL, $name = NULL, $action = NULL)
    {
        if ($type) {
            if ($action && isset($this->_access_overrides[$type]) && isset($this->_access_overrides[$type][$name]) && isset($this->_access_overrides[$type][$name][$action]) && $this->_access_overrides[$type][$name][$action] != NULL) {
                return $this->_access_overrides[$type][$name][$action];
            }
            elseif ($name && isset($this->_access_overrides[$type]) && isset($this->_access_overrides[$type][$name]) && $this->_access_overrides[$type][$name] != NULL) {
                return $this->_access_overrides[$type][$name];
            }
            elseif($type && isset($this->_access_overrides[$type]) && $this->_access_overrides[$type] != NULL) {
                return $this->_access_overrides[$type];
            } else {
                $this->_access_overrides['*'];
            }
        } else {
            return $this->_access_overrides['*'];
        }
    }

    public function hasModelAccess($model_name, \FluxAPI\Model $model = NULL, $action = NULL)
    {
        $default_access = $this->getDefaultAccess();

        // access override
        $access_override = $this->getAccessOverride('Model', $model_name, $action);
        if ($access_override != NULL) {
            return $access_override;
        }

        $permissions = $this->_api['plugins']->getPlugins('Permission');

        foreach($permissions as $permission_name => $permission) {
            $access = $this->getCachedModelAccess($model_name, $model, $action);

            if ($access === NULL) {
                $access = $this->getPermission($permission_name)->hasModelAccess($model_name, $model, $action);
            }

            // if access is other then default return it
            if ($access != $default_access) {
                return $access;
            }
        }

        // if no un default access happened we return the default
        return $default_access;
    }

    public function hasControllerAccess($controller_name, $action = NULL)
    {
        $default_access = $this->getDefaultAccess();

        // access override
        $access_override = $this->getAccessOverride('Controller', $controller_name, $action);
        if ($access_override != NULL) {
            return $access_override;
        }

        $permissions = $this->_api['plugins']->getPlugins('Permission');

        foreach($permissions as $permission_name => $permission) {
            $access = $this->getCachedControllerAccess($controller_name, $action);

            if ($access === NULL) {
                $access = $this->getPermission($permission_name)->hasControllerAccess($controller_name, $action);
            }

            // if access is other then default return it
            if ($access != $default_access) {
                return $access;
            }
        }

        // if no un default access happened we return the default
        return $default_access;
    }
}