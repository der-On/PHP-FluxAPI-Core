<?php

namespace FluxAPI\Factory;

use \FluxAPI\Event\ControllerEvent;
use FluxAPI\Exception\AccessDeniedException;

class ControllerFactory extends \Pimple
{
    protected $_api;

    protected $_controllers = array();

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
        $this['permissions'] = $api['permissions'];
        $this['plugins'] = $api['plugins'];
        $this['caches'] = $api['caches'];
        $this['dispatcher'] = $api['dispatcher'];
    }

    public function getControllerClass($controller_name)
    {
        if (!$this['permissions']->hasControllerAccess($controller_name)) {
            throw new AccessDeniedException(sprintf('You are not allowed to use the "%s" Controller.', $controller_name));
            return NULL;
        }

        return $this['plugins']->getPluginClass('Controller', $controller_name);
    }

    public function getController($controller_name)
    {
        if (!$this['permissions']->hasControllerAccess($controller_name)) {
            throw new AccessDeniedException(sprintf('You are not allowed to use the "%s" Controller.', $controller_name));
            return NULL;
        }

        $controller = $this->getControllerClass($controller_name);

        if ($controller) {
            if (!isset($this->_controllers[$controller_name])) {
                $this->_controllers[$controller_name] = new $controller($this->_api);
            }

            return $this->_controllers[$controller_name];
        }

        return NULL;
    }

    public function getCachedCall($controller_name, $action)
    {
        $source = new \FluxAPI\Cache\ControllerActionSource($controller_name, $action);
        return $this['caches']->getCached(\FluxAPI\Cache::TYPE_CONTROLLER_ACTION, $source);
    }

    public function cacheCall($controller_name, $action, $result = NULL)
    {
        $source = new \FluxAPI\Cache\ControllerActionSource($controller_name, $action, $result);
        $this['caches']->store(\FluxAPI\Cache::TYPE_CONTROLLER_ACTION, $source, $result);
    }

    /**
     * Calls the action of a controller if both are registered.
     *
     * @param string $controller_name - name of the controller
     * @param string $action - name of the action
     * @param [array $params] - Parameters passed to the controller action
     * @param [array $context] - Context passed to the controller for the action call
     * @return mixed
     */
    public function call($controller_name, $action, array $params = NULL, array $context = NULL)
    {
        // abort if access is denied
        if (!$this['permissions']->hasControllerAccess($controller_name, $action)) {
            throw new AccessDeniedException(sprintf('You are not allowed to call %s->%s.', $controller_name, $action));
            return;
        }

        $controller = $this->getControllerClass($controller_name);

        if ($controller) {
            if ($controller::hasAction($action)) {
                $instance = $this->getController($controller_name);

                if ($params === NULL) {
                    $params = array();
                }

                // if params are an assoc array, convert them to an indexed array
                if ((bool) count(array_filter(array_keys($params), 'is_string'))) {
                    $params = $this->_getIndexedParams($params, $controller, $action);
                }

                // clear context before the call
                $instance->clearContext();

                // and set it eventually
                if ($context !== NULL) {
                    foreach($context as $key => $value) {
                        $instance->setContext($key, $value);
                    }
                }

                $this['dispatcher']->dispatch(ControllerEvent::BEFORE_CALL, new ControllerEvent($controller_name, $instance, $action, $params));

                $return = $this->getCachedCall($controller_name, $action);

                if ($return === NULL) {
                    $return = call_user_func_array(array($instance, $action), $params);
                }

                $this['dispatcher']->dispatch(ControllerEvent::CALL, new ControllerEvent($controller_name, $instance, $action, $params));

                $this->cacheCall($controller_name, $action, $return);

                return $return;
            }
        } else {
            throw new \RuntimeException(sprintf('Controller "%s" is not registered.', $controller_name));
            return;
        }
    }

    protected function _getNormalizedActions($actions)
    {
        // normalize actions to assoc arrays
        foreach($actions as $key => $value) {
            if (is_numeric($key)) {
                $actions[$value] = array();
                unset($actions[$key]);
            }
        }

        return $actions;
    }

    /**
     * Returns all actions for all controllers or a single controller.
     *
     * @param [string $controller_name]
     * @return array - if $controller_name is given a list of actions, if no $controller_name is given a list of controllers as keys and actions as values.
     */
    public function getActions($controller_name = NULL)
    {
        if (empty($controller_name)) {
            $controllers = $this['plugins']->getPlugins('Controller');

            $actions = array();

            foreach($controllers as $name => $controller) {
                $actions[$name] = $this->_getNormalizedActions($controller::getActions());
            }

            return $actions;
        } else {
            $controller = $this['plugins']->getPluginClass('Controller', $controller_name);

            if ($controller) {
                return $this->_getNormalizedActions($controller::getActions());
            }
        }

        return array();
    }

    protected function _getIndexedParams(array $params, $controller, $action)
    {
        $_params = array();

        $reflection = new \ReflectionMethod($controller, $action);
        $names = $reflection->getParameters();

        foreach($names as $param) {
            if (isset($params[$param->name])) {
                $_params[] = $params[$param->name];
            }
        }

        return $_params;
    }

}