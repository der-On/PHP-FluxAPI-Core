<?php
namespace FluxAPI;

class Api
{
    const DATA_FORMAT_ARRAY = 'array';
    const DATA_FORMAT_JSON = 'json';
    const DATA_FORMAT_XML = 'xml';
    const DATA_FORMAT_YAML = 'yaml';

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
                    'debug_sql' => FALSE,
                )
            ),
            $config
        );

        // register Serializer
        $this->app->register(new \Silex\Provider\SerializerServiceProvider());

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

        return $this; // make it chainable
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

        $this->_methods['load'.$model.'s'] = function($query = NULL, $format = NULL) use ($model, $self) {
            $models =  $self->loadModels($model,$query);

            if (in_array($format,array(Api::DATA_FORMAT_ARRAY,Api::DATA_FORMAT_JSON,Api::DATA_FORMAT_YAML))) {
                foreach($models as $i => $model) {
                    $models[$i] = $models[$i]->toArray();
                }
            }

            switch($format) {
                case Api::DATA_FORMAT_ARRAY:
                    return $models;
                    break;

                case Api::DATA_FORMAT_JSON:
                    return json_encode($models);
                    break;

                case Api::DATA_FORMAT_YAML:
                    $dumper = new \Symfony\Component\Yaml\Dumper();
                    return $dumper->dump($models,2);
                    break;

                case Api::DATA_FORMAT_XML:
                    $xml = '<?xml version="1.0"?>'."\n"."<".$model."s>\n";

                    foreach($models as $_model) {
                        $xml .= trim(str_replace('<?xml version="1.0"?>','',$_model->toXml()))."\n";
                    }
                    $xml .= "</".$model."s>";
                    return $xml;
                    break;

                default:
                    return $models;
            }
        };

        $this->_methods['load'.$model] = function($query, $format = NULL) use ($model, $self) {

            if (is_string($query)) {
                $id = $query;
                $query = new Query();
                $query->filter('equal',array('id',$id));
            }

            if (!$query->hasFilter('limit')) {
                $query->filter('limit',array(0,1));
            }

            $models = $self->loadModels($model,$query);
            if (count($models)) {
                switch($format) {
                    case Api::DATA_FORMAT_XML:
                        return $models[0]->toXml();
                        break;

                    case Api::DATA_FORMAT_JSON:
                        return $models[0]->toJson();
                        break;

                    case Api::DATA_FORMAT_YAML:
                        return $models[0]->toYaml();
                        break;

                    case Api::DATA_FORMAT_ARRAY:
                        return $models[0]->toArray();
                        break;

                    default:
                        return $models[0];
                }
            } else {
                return NULL;
            }
        };

        $this->_methods['save'.$model] = function($instance) use ($model, $self) {
            return $self->saveModel($model,$instance);
        };

        $this->_methods['delete'.$model.'s'] = function($query = NULL) use ($model, $self) {
            return $self->deleteModels($model, $query);
        };

        $this->_methods['delete'.$model] = function($query) use ($model, $self) {
            if (is_string($query)) {
                $id = $query;
                $query = new Query();

                $query->filter('equal',array('id',$id));
            }

            $limit_filters = $query->getFilters('limit');
            if (count($limit_filters) == 0) {
                $query->filter('limit',array(0,1));
            } else {
                foreach($limit_filters as &$filter) {
                    $filter[1][1] = 1;
                }
            }


            return $self->deleteModels($model, $query);
        };

        $this->_methods['update'.$model.'s'] = function($query, array $data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model, $self) {
            if (is_array($query)) {
                $ids = $query;

                $query = new Query();
                $query->filter('in',array('id',$ids));
            }
            return $self->updateModels($model, $query, $data, $format);
        };

        $this->_methods['update'.$model] = function($id, array $data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model, $self) {
            $query = new Query();
            $query->filter('equal',array('id',$id));
            $query->filter('limit',array(0,1));

            return $self->updateModels($model, $query, $data, $format);
        };

        $this->_methods['create'.$model] = function($data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model, $self) {
            return $self->createModel($model, $data, $format);
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

    public function getPluginClass($namespace, $name)
    {
        if (!empty($namespace) && isset($this->_plugins[$namespace]) && isset($this->_plugins[$namespace][$name])) {
            return $this->_plugins[$namespace][$name];
        } else {
            return NULL;
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
            return $this->getStorage($model)->load($model,$query);
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

            $storage = $this->getStorage($model);

            if (is_array($instances)) {
                foreach($instances as $instance) {
                    $storage->save($model,$instance);
                }
                return TRUE;
            } else {
                return $storage->save($model,$instances);
            }
        }

        return FALSE;
    }

    public function updateModels($model, Query $query, array $data = array())
    {

        $storage = $this->getStorage($model);

        return $storage->update($model, $query, $data);
    }

    public function createModel($model, array $data = array(), $format = Api::DATA_FORMAT_ARRAY)
    {
        $models = $this->getPlugins('Model');

        if (isset($models[$model])) {
            $class_name = $models[$model];

            switch($format) {
                case self::DATA_FORMAT_ARRAY:
                    return $class_name::fromArray($data);
                    break;

                case self::DATA_FORMAT_JSON:
                    return $class_name::fromJson($data);
                    break;

                case self::DATA_FORMAT_XML:
                    return $class_name::fromXml($data);
                    break;

                case self::DATA_FORMAT_YAML:
                    return $class_name::fromYaml($data);
                    break;

                default:
                    return $class_name::fromArray($data);
            }
        }

        return FALSE;
    }

    public function deleteModels($model, Query $query = NULL)
    {
        $models = $this->getPlugins('Model');

        if (isset($models[$model])) {
            $storage = $this->getStorage($model);
            return $storage->delete($model, $query);
        }

        return FALSE;
    }

    public function migrate($model = NULL)
    {
        $storage = $this->getStorage($model);
        $storage->migrate($model);
    }
}
