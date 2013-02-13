<?php
namespace FluxAPI;

class Api
{
    private $_plugins = array(
        'Model' => array(),
        'Controller' => array(),
        'Storage' => array(),
        'Cache' => array(),
        'Permission' => array(),
    );
    private $_base_plugins = array();

    private $_methods = array();

    public $app = NULL;

    public $config = array();

    public function __construct(\Silex\Application $app, $config = array())
    {
        $this->app = $app;

        // overwrite default config with given config
        $this->config = array_merge(
            array(
                'plugins_path' => realpath(__DIR__ . '/../../src/Plugins'),
                'base_route' => '/',
            ),
            $config
        );

        $this->registerPlugins();
    }

    public function registerPlugins()
    {
        $plugins = scandir($this->config['plugins_path']);

        $allowed_plugin_types = array_keys($this->_plugins);

        foreach($plugins  as $plugin) {
            $plugin_base_path = $this->config['plugins_path'].'/'.$plugin;

            if (is_dir($plugin_base_path)) {

                $plugin_dirs = scandir($plugin_base_path);

                foreach($plugin_dirs as $plugin_type) {
                    $plugin_dir_path = $plugin_base_path.'/'.$plugin_type;

                    // directories
                    if (is_dir($plugin_dir_path) && in_array($plugin_type,$allowed_plugin_types)) {

                        $plugin_files = scandir($plugin_dir_path);

                        foreach($plugin_files as $plugin_file) {
                            $plugin_file_path = $plugin_dir_path.'/'.$plugin_file;

                            if (is_file($plugin_file_path) && substr($plugin_file,-strlen('.php')) == '.php') {

                                $plugin_name = ucfirst(basename($plugin_file,'.php'));
                                $plugin_class_name = 'Plugins\\'.ucfirst($plugin).'\\'.ucfirst($plugin_type).'\\'.$plugin_name;

                                $this->_plugins[$plugin_type][$plugin_name] = $plugin_class_name;

                                $this->registerPluginMethods($plugin_type,$plugin_name);
                            }
                        }
                    } // files
                    elseif (is_file($plugin_dir_path) && substr($plugin_type,-strlen('.php')) == '.php') {
                        $plugin_name = ucfirst(basename($plugin_type,'.php'));

                        if ($plugin_name == ucfirst($plugin)) {
                            $plugin_class_name = 'Plugins\\'.ucfirst($plugin).'\\'.$plugin_name;

                            $this->_base_plugins[$plugin] = $plugin_class_name;
                        }
                    }
                }
            }
        }

        foreach($this->_base_plugins as $plugin => $plugin_class_name) {
            $plugin_class_name::register($this);
        }
    }

    public function registerPluginMethods($type, $name)
    {
        switch (ucfirst($type)) {
            case 'Model':
                $this->registerModelMethods($name);
                break;
        }
    }

    public function registerModelMethods($model)
    {
        $self = $this;

        $model = ucfirst($model);

        $this->_methods['load'.$model] = function($query) use ($model, $self) {
            return $self->loadModel($model,$query);
        };

        $this->_methods['save'.$model] = function($instance) use ($model, $self) {
            return $self->saveModel($model,$instance);
        };

        $this->_methods['delete'.$model] = function($query) use ($model, $self) {
            return $self->deleteModel($model, $query);
        };

        $this->_methods['update'.$model] = function($id, $data = array()) use ($model, $self) {
            return $self->updateModel($model,$id,$data);
        };
    }

    public function getPlugins($namespace = NULL)
    {
        if (empty($namespace)) {
            return $this->_plugins;
        } elseif (isset($this->_plugins[$namespace])) {
            return $this->_plugins[$namespace];
        } else {
            return array();
        }
    }

    public function loadModel($model,$query)
    {
        $models = $this->getPlugins('Model');

        if (isset($models[$model])) {
            $class_name = $models[$model];
            return $class_name::load($query);
        }

        return array();
    }

    public function saveModel($model, $instances)
    {
        $models = $this->getPlugins('Model');

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
        $models = $this->getPlugins('Model');

        if (isset($models[$model])) {
            $class_name = $models[$model];
            return $class_name::delete($query);
        }

        return FALSE;
    }

    public function __call($method,$arguments)
    {
        if (isset($this->_methods[$method])) {
            $callback = $this->_methods[$method];

            return call_user_func_array($callback,$arguments);
        }

        return NULL;
    }
}
