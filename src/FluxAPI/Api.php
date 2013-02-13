<?php
namespace FluxAPI;

class Api
{
    private $_core = NULL;

    private $_methodPrefixes = array(
        'load' =>       'loadModel',
        'save' =>       'saveModel',
        'delete' =>     'deleteModel',
        'update' =>     'updateModel',
    );

    public function __construct(Core $core)
    {
        $this->_core = $core;
    }

    public function loadModel($model,$query)
    {
        $models = $this->_core->getPlugins('Model');

        if (isset($models[$model])) {
            $class_name = $models[$model];
            return $class_name::load($query);
        }

        return array();
    }

    public function saveModel($model, $instances)
    {
        $models = $this->_core->getPlugins('Model');

        if (isset($models[$model])) {
            if (empty($instances)) {
                return FALSE;
            }

            if (is_array($instances)) {
                foreach($instances as $instance) {
                    $instance->save();
                }
                return TRUE;
            } else {
                return $instances->save();
            }
        }

        return FALSE;
    }

    public function updateModel($model, $id, $data = array())
    {
        $instances = $this->loadModel($model,$id);

        if (count($instances) > 0) {
            $instance = $instances[0];
            return $instance->update($data);
        }

        return FALSE;
    }

    public function deleteModel($model, $query)
    {
        $models = $this->_core->getPlugins('Model');

        if (isset($models[$model])) {
            $class_name = $models[$model];
            return $class_name::delete($query);
        }

        return FALSE;
    }

    public function __call($name,$arguments)
    {
        foreach($this->_methodPrefixes as $prefix => $method) {

            if (strpos($name,$prefix) === 0) {
                $plugin_name = substr($name,strlen($prefix));

                $args = array($plugin_name);
                $args = array_merge($args,$arguments);

                return call_user_func_array(array($this,$method),$args);
            }
        }

        return NULL;
    }
}
