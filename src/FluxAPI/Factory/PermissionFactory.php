<?php

namespace FluxAPI\Factory;


class PermissionFactory
{
    protected $_api;

    protected $_permission = array();

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

    public function hasModelAccess($model_name, \FluxAPI\Model $model = NULL, $action = NULL)
    {
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