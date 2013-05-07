<?php

namespace FluxAPI\Factory;


class PermissionFactory
{
    protected $_api;

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }

    public function getDefaultAccess()
    {
        return ($this->_api->config['permission.options']['default'] == \FluxAPI\Api::PERMISSION_ALLOW) ? TRUE : FALSE;
    }

    public function hasModelAccess($model_name, \FluxAPI\Model $model = NULL, $action = NULL)
    {
        $plugins = $this->_api['plugins']->getPlugins('Permission');

        $default_access = $this->getDefaultAccess();

        foreach($plugins as $plugin) {
            $plugin_access = $plugin::hasModelAccess($model_name, $model, $action);

            // if access is denied by any plugin we stop here
            if (!$plugin_access) {
                return FALSE;
            }
        }

        // if no deny happened we return the default
        return $default_access;
    }

    public function hasControllerAccess($controller_name, $action = NULL)
    {
        $plugins = $this->_api['plugins']->getPlugins('Permission');

        $default_access = $this->getDefaultAccess();

        foreach($plugins as $plugin) {
            $plugin_access = $plugin::hasControllerAccess($controller_name, $action);

            // if access is denied by any plugin we stop here
            if (!$plugin_access) {
                return FALSE;
            }
        }

        // if no deny happened we return the default
        return $default_access;
    }
}