<?php
namespace FluxAPI;

/**
 * This is the entry point for all API calls. It contains magic methods to operate on all ressources and controllers.
 * @package FluxAPI
 *
 * @method Model create_Model_([array $data]) Creates and returns a new instance of _Model_. If $data is given the model will contain that initial data.
 * @method Model load_Model_(string|Query $query, [string $format]) Loads a _Model_ by id or with a given query. If $format is given the model will be returned in the given format.
 * @method Model load_Model_s([Query $query], [string $format]) Loads a list of _Model_s with a given query. If $format is given the models will be returned in the given format.
 * @method Model save_Model_(Model $model) Saves a _Model_
 * @method Model delete_Model_(string|Query $query) Deletes a single _Model_ by id or by a given query
 * @method Model delete_Model_s([Query $query]) Deletes a list of _Model_s by a given query
 */
class Api extends \Pimple
{
    const VERSION = '0.1-DEV';

    const DATA_FORMAT_ARRAY = 'array';
    const DATA_FORMAT_JSON = 'json';
    const DATA_FORMAT_XML = 'xml';
    const DATA_FORMAT_YAML = 'yaml';

    /**
     * @var array Internal lookup for plugins
     */
    private $_plugins = array(
        'Model' => array(),
        'Controller' => array(),
        'Storage' => array(),
        'Cache' => array(),
        'Permission' => array(),
    );

    /**
     * @var array Internal extensions
     */
    private $_extends = array(
        'Model' => array(),
    );

    /**
     * @var array Internal lookup for base plugins
     */
    private $_base_plugins = array();

    /**
     * @var array Internal lookup for magic methods
     */
    private $_methods = array();

    /**
     * @var \Silex\Application The silex app
     */
    public $app = NULL;

    /**
     * @var array FluxAPI configuration
     */
    public $config = array();

    /**
     * Constructor
     *
     * @param \Silex\Application $app
     * @param array $config
     */
    public function __construct(\Silex\Application $app, array $config = array())
    {
        parent::__construct();

        $GLOBALS['fluxApi'] = $this;
        $api = $this;
        $this->app = $app;

        // overwrite default config with given config
        $this->config = array_replace_recursive(
            array(
                'plugins_path' => realpath(__DIR__ . '/../../src/Plugins'),
                'extends_path' => realpath(__DIR__ . '/../../extends'),
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

        $this['logger'] = NULL;

        $this['models'] = $this->share(function() use ($api) {
           return $api['model_factory'];
        });

        $this['model_factory'] = $this->share(function() use ($api) {
            return new Factory\ModelFactory($api);
        });

        $this['storages'] = $this->share(function() use ($api) {
           return $api['storage_factory'];
        });

        $this['storage_factory'] = $this->share(function() use ($api) {
            return new Factory\StorageFactory($api);
        });

        $this['controllers'] = $this->share(function() use ($api) {
           return $api['controller_factory'];
        });

        $this['controller_factory'] = $this->share(function() use ($api) {
           return new Factory\ControllerFactory($api);
        });

        $this['exception_handler'] = $this->share(function () use ($api) {
            return new ExceptionHandler($api->app['debug']);
        });

        $this['permissions'] = $this->share(function() use ($api) {
            return $api['permission_factory'];
        });

        $this['permission_factory'] = $this->share(function() use ($api) {
            return new Factory\PermissionFactory($api);
        });

        $this['dispatcher_class'] = 'Symfony\\Component\\EventDispatcher\\EventDispatcher';
        $this['dispatcher'] = $this->share(function () use ($api) {
            $dispatcher = new $api['dispatcher_class']();

            $dispatcher->addSubscriber(new \FluxAPI\EventListener\ModelListener($api));

            return $dispatcher;
        });

        // register Serializer
        $this->app->register(new \Silex\Provider\SerializerServiceProvider());

        $this->registerPlugins();
        $this->registerExtends();
    }

    /**
     * Returns the singleton of the Api
     *
     * If no Api instance exists yet it will create one and return it.
     *
     * @param \Silex\Application $app
     * @param array $config
     * @return mixed
     */
    public static function getInstance(\Silex\Application $app = NULL, array $config = array())
    {
        if (!isset($GLOBALS['fluxApi']) && !empty($app)) {
            $GLOBALS['fluxApi'] = new FluxApi($app,$config);
        } else {
            return $GLOBALS['fluxApi'];
        }
    }

    /**
     * Calls the magic methods
     *
     * @param $method
     * @param array $arguments
     * @return mixed|null
     */
    public function __call($method, array $arguments)
    {
        if (isset($this->_methods[$method])) {
            $callback = $this->_methods[$method];

            return call_user_func_array($callback,$arguments);
        }

        return NULL;
    }

    /**
     * Registers all available plugins found within the Plugins/ directory
     */
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

    /**
     * Registers all extensions found in the extends directory
     */
    public function registerExtends()
    {
        foreach(array_keys($this->_extends) as $type) {
            $extends_dir = $this->config['extends_path'].'/'.$type;

            if (file_exists($extends_dir)) {

                $files = scandir($extends_dir);

                foreach($files as $file) {
                    if (!in_array($file,array('.','..')) && substr($file,-strlen('.json')) == '.json') {
                        $name = substr($file,0,-strlen('.json'));
                        $this->registerExtend($type, $name);
                    }
                }
            }
        }
    }

    /**
     * Registers a single extension
     *
     * Will add dynamic models to the model plugins to make them available
     *
     * TODO: currently it will read in the json file but maybe it's better to only read those files on demand?
     *
     * @param $type
     * @param $name
     */
    public function registerExtend($type, $name)
    {
        $extends_dir = $this->config['extends_path'].'/'.$type;
        $file = $name.'.json';

        switch($type) {
            case 'Model':
                $this->_extends[$type][$name] = json_decode(file_get_contents($extends_dir.'/'.$file),TRUE);

                // if this is a dynamic model we have to register it as a model plugin
                if (!isset($this->_plugins[$type][$name])) {
                    $this->_plugins[$type][$name] = 'FluxAPI\DynamicModel';
                }

                $this->registerPluginMethods($type,$name);
                break;
        }
    }

    /**
     * Registers a magic method
     *
     * @param string $method
     * @param function $callback
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
     * Registers all magic methods of all registered plugins
     *
     * @param string $type the plugin type
     * @param string $name the plugin name
     */
    public function registerPluginMethods($type, $name)
    {
        switch (ucfirst($type)) {
            case 'Model':
                $this->registerModelMethods($name);
                break;
        }
    }

    /**
     * Registers all magic methods for a registered model
     *
     * @param string $model_name
     */
    public function registerModelMethods($model_name)
    {
        $self = $this;

        $model_name = ucfirst($model_name);

        // load multiple model instances
        $this->_methods['load'.$model_name.'s'] = function($query = NULL, $format = NULL) use ($model_name, $self) {
            $models =  $self['model_factory']->load($model_name,$query);

            if (in_array($format,array(Api::DATA_FORMAT_ARRAY,Api::DATA_FORMAT_JSON,Api::DATA_FORMAT_YAML))) {
                foreach($models as $i => $model_name) {
                    $models[$i] = $models[$i]->toArray();
                }
            }

            // TODO: converting models to certain formats should be dynamic/extendable and within the model factory?
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
                    $xml = '<?xml version="1.0"?>'."\n"."<".$model_name."s>\n";

                    foreach($models as $_model) {
                        $xml .= trim(str_replace('<?xml version="1.0"?>','',$_model->toXml()))."\n";
                    }
                    $xml .= "</".$model_name."s>";
                    return $xml;
                    break;

                default:
                    return $models;
            }
        };

        // load single model instance
        $this->_methods['load'.$model_name] = function($query, $format = NULL) use ($model_name, $self) {

            if (is_string($query)) {
                $id = $query;
                $query = new Query();
                $query->filter('equal',array('id',$id));
            }

            if (!$query->hasFilter('limit')) {
                $query->filter('limit',array(0,1));
            }

            $models = $self['model_factory']->load($model_name,$query);

            // TODO: converting models to certain formats should be dynamic/extendable and within the model factory?
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

        // save a model instance
        $this->_methods['save'.$model_name] = function($instance) use ($model_name, $self) {
            return $self['model_factory']->save($model_name,$instance);
        };

        // delete model multiple instances
        $this->_methods['delete'.$model_name.'s'] = function($query = NULL) use ($model_name, $self) {
            return $self['model_factory']->delete($model_name, $query);
        };

        // delete a single model instance
        $this->_methods['delete'.$model_name] = function($query) use ($model_name, $self) {
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


            return $self['model_factory']->delete($model_name, $query);
        };

        // update multiple model instances
        $this->_methods['update'.$model_name.'s'] = function($query, array $data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            if (is_array($query)) {
                $ids = $query;

                $query = new Query();
                $query->filter('in',array('id',$ids));
            }
            return $self['model_factory']->update($model_name, $query, $data, $format);
        };

        // update a single model instance
        $this->_methods['update'.$model_name] = function($id, array $data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            $query = new Query();
            $query->filter('equal',array('id',$id));
            $query->filter('limit',array(0,1));

            return $self['model_factory']->update($model_name, $query, $data, $format);
        };

        // create a model instance
        $this->_methods['create'.$model_name] = function($data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            return $self['model_factory']->create($model_name, $data, $format);
        };
    }

    /**
     * Returns all registered plugins (of a given type)
     * @param string $type if set, only plugins of that type will be returned
     * @return array
     */
    public function getPlugins($type = NULL)
    {
        if (empty($type)) {
            return $this->_plugins;
        } elseif (isset($this->_plugins[$type])) {
            return $this->_plugins[$type];
        } else {
            return array();
        }
    }

    /**
     * Returns fully class name of a plugin by a given type and name
     *
     * @param string $type
     * @param string $name
     * @return null|array
     */
    public function getPluginClass($type, $name)
    {
        if (!empty($type) && isset($this->_plugins[$type]) && isset($this->_plugins[$type][$name])) {
            return $this->_plugins[$type][$name];
        } else {
            return NULL;
        }
    }

    /**
     * Extends a model with new fields. If the model does not exists, it will be created.
     *
     * @param string $model_name
     * @param array $fields Field definitions. Either containing real Field instances or key => value pairs
     * @param [string $format] the format of the $fields. Default is Api::DATA_FORMAT_ARRAY.
     */
    public function extendModel($model_name, array $fields, $format = self::DATA_FORMAT_ARRAY)
    {
        $extend_dir = $this->config['extends_path'].'/Model';
        $file = $extend_dir.'/'.$model_name.'.json';
        $version = 1;

        if (!file_exists($extend_dir)) {

            // create models directory if not existing
            if (!mkdir($extend_dir,0755,TRUE)) {
                // TODO: throw an exception
            }
        }

        // create model file if not existing
        if (!file_exists($file)) {
            touch($file);
        }

        if (file_exists($file)) {

            $config = json_decode(file_get_contents($file),TRUE);

            if (!empty($config)) {
                $version = intval($config['version']) + 1;
            }

            $_fields = array();

            foreach($fields as $name => $field) {
                if (is_object($field)) {
                    $_fields[] = $field->toArray();
                } else if(is_array($field)) {
                    $_fields[] = $field;
                }
            }

            $config = array(
                'name' => $model_name,
                'updated' => date('c'),
                'version' => $version,
                'fields' => $_fields,
            );

            file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));

            $this->registerExtend('Model',$model_name);

            $this->migrate($model_name);

        } else {
            // TODO: throw an exception
        }
    }

    /**
     * @param string $type
     * @param [string $name]
     * @return array
     */
    public function getExtends($type, $name = null)
    {
        if (empty($name)) {
            if (isset($this->_extends[$type])) {
                return $this->_extends[$type];
            }
        } else {
            if (isset($this->_extends[$type]) && isset($this->_extends[$type][$name])) {
                return $this->_extends[$type][$name];
            }
        }
        return NULL;
    }

    /**
     * Migrates the storage dabase(s) (for a given model)
     * @param [string $model_name]
     */
    public function migrate($model_name = NULL)
    {
        $storage = $this['storage_factory']->get($model_name);
        $storage->migrate($model_name);
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string   $eventName The event to listen on
     * @param callable $callback  The listener
     * @param integer  $priority  The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to 0)
     */
    public function on($eventName, $callback, $priority = 0)
    {
        $this['dispatcher']->addListener($eventName, $callback, $priority);
    }
}
