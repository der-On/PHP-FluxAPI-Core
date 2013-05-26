<?php

namespace FluxAPI\Factory;


class PermissionFactory
{
    protected $_api;

    protected $_permission = array();

    protected $_access_override = NULL;

    protected $_access_override_once = FALSE;

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
     * @param [bool $once] - If set to true the access override will be unset after next access
     */
    public function setAccessOverride($access, $once = FALSE)
    {
        $this->_access_override = ($access) ? TRUE : FALSE;
        $this->_access_override_once = $once;
    }

    /**
     * Unsets the access override.
     */
    public function unsetAccessOverride()
    {
        $this->_access_override = NULL;
    }

    public function hasModelAccess($model_name, \FluxAPI\Model $model = NULL, $action = NULL)
    {
        // access override
        if ($this->_access_override != NULL) {
            $access = $this->_access_override;

            if ($this->_access_override_once) {
                $this->_access_override = NULL;
                $this->_access_override_once = FALSE;
            }
            return $access;
        }

        $permissions = $this->_api['plugins']->getPlugins('Permission');

        $default_access = $this->getDefaultAccess();

        foreach($permissions as $permission_name => $permission) {
            $access = $this->getPermission($permission_name)->hasModelAccess($model_name, $model, $action);

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
        // access override
        if ($this->_access_override != NULL) {
            $access = $this->_access_override;

            if ($this->_access_override_once) {
                $this->_access_override = NULL;
                $this->_access_override_once = FALSE;
            }
            return $access;
        }

        $permissions = $this->_api['plugins']->getPlugins('Permission');

        $default_access = $this->getDefaultAccess();

        foreach($permissions as $permission_name => $permission) {
            $access = $this->getPermission($permission_name)->hasControllerAccess($controller_name, $action);

            // if access is other then default return it
            if ($access != $default_access) {
                return $access;
            }
        }

        // if no un default access happened we return the default
        return $default_access;
    }
}