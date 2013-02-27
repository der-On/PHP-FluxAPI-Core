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

    public function __construct(\Silex\Application $app, array $config = array())
    {
        $GLOBALS['fluxApi'] = $this;

        $this->app = $app;

        // overwrite default config with given config
        $this->config = array_replace_recursive(
            array(
                'plugins_path' => realpath(__DIR__ . '/../../src/Plugins'),
                'base_route' => '/',
                'storage.plugin' => 'MySql',
                'storage.options' => array(
                    'host' => 'localhost',
                    'user' => 'username',
                    'password' => 'password',
                    'database' => 'database',
                    'table_prefix' => '',
                    'port' => 3306,
                    'socket' => NULL,
                )
            ),
            $config
        );

        $this->registerPlugins();
    }

    public static function getInstance(\Silex\Application $app = NULL, array $config = array())
    {
        if (!isset($GLOBALS['fluxApi']) && !empty($app)) {
            $GLOBALS['fluxApi'] = new FluxApi($app,$config);
        } else {
            return $GLOBALS['fluxApi'];
        }
    }

    public function __call($method, array $arguments)
    {
        if (isset($this->_methods[$method])) {
            $callback = $this->_methods[$method];

            return call_user_func_array($callback,$arguments);
        }

        return NULL;
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

    public function registerMethod($method, $callback)
    {
        if (!isset($this->_methods[$method]) && is_callable($callback)) {
            $this->_methods[$method] = $callback;
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

        $this->_methods['load'.$model.'s'] = function($query = NULL) use ($model, $self) {
            return $self->loadModels($model,$query);
        };

        $this->_methods['load'.$model] = function($query) use ($model, $self) {
            $models = $self->loadModels($model,$query);
            if (count($models)) {
                return $models[0];
            } else {
                return NULL;
            }
        };

        $this->_methods['save'.$model] = function($instance) use ($model, $self) {
            return $self->saveModel($model,$instance);
        };

        $this->_methods['delete'.$model.'s'] = function($instance = NULL, $query = NULL) use ($model, $self) {
            return $self->deleteModels($model, $instance, $query);
        };

        $this->_methods['delete'.$model] = function($query) use ($model, $self) {
            return $self->deleteModels($model, $query);
        };

        $this->_methods['update'.$model] = function($id, $data = array()) use ($model, $self) {
            return $self->updateModel($model,$id,$data);
        };

        $this->_methods['create'.$model] = function($data = array()) use ($model, $self) {
            return $self->createModel($model,$data);
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

    public function getStorage($model = NULL)
    {
        $storagePlugins = $this->getPlugins('Storage');

        // get default storage plugin
        $storageClass = $storagePlugins[$this->config['storage.plugin']];

        return new $storageClass($this,$this->config['storage.options']);
    }

    public function loadModels($model, Query $query = NULL)
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

    public function updateModel($model, $id, array $data = array())
    {
        $instances = $this->loadModel($model,$id);

        if (count($instances) > 0) {
            $instance = $instances[0];
            return $instance->update($data);
        }

        return FALSE;
    }

    public function createModel($model, array $data = array())
    {
        $models = $this->getPlugins('Model');

        if (isset($models[$model])) {
            $class_name = $models[$model];
            return $class_name::create($data);
        }

        return FALSE;
    }

    public function deleteModels($model, Model $instance = NULL, Query $query = NULL)
    {
        $models = $this->getPlugins('Model');

        if (isset($models[$model])) {
            $class_name = $models[$model];
            return $class_name::delete($instance, $query);
        }

        return FALSE;
    }

    public function migrate()
    {
        $storage = $this->getStorage();
        $storage->migrate();
    }
}
