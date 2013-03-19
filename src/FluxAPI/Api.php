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
class Api
{
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
        $GLOBALS['fluxApi'] = $this;

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

    public function registerExtend($type, $name)
    {
        $extends_dir = $this->config['extends_path'].'/'.$type;
        $file = $name.'.json';

        switch($type) {
            case 'Model':
                $this->_extends[$type][$name] = json_decode(file_get_contents($extends_dir.'/'.$file),TRUE);
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
     * Registers all magic methods for all registered models
     *
     * @param string $model
     */
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
     * Returns an instance of the storage for a given model
     *
     * @param [string $model] if not set the default storage will be returned
     * @return Storage
     */
    public function getStorage($model = NULL)
    {
        $storagePlugins = $this->getPlugins('Storage');

        // get default storage plugin
        $storageClass = $storagePlugins[$this->config['storage.plugin']];

        return new $storageClass($this,$this->config['storage.options']);
    }

    /**
     * Loads and returns a list of Model instances
     *
     * @param string $model
     * @param [Query $query] if not set all instances of the model are loaded
     * @return array|null
     */
    public function loadModels($model, Query $query = NULL)
    {
        $models = $this->getPlugins('Model');

        if (isset($models[$model])) {
            return $this->getStorage($model)->load($model,$query);
        }

        return array();
    }

    /**
     * Saves a list of or a single model instance
     *
     * @param string $model
     * @param array|Model $instances
     * @return bool
     */
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

    /**
     * Updates models with certain data
     *
     * @param string $model
     * @param Query $query
     * @param array $data
     * @return bool
     */
    public function updateModels($model, Query $query, array $data)
    {

        $storage = $this->getStorage($model);

        return $storage->update($model, $query, $data);
    }

    /**
     * Creates a new instance of a model
     *
     * @param string $model
     * @param [array $data] if set the model will contain that initial data
     * @param [string $format] the format of the given $data
     * @return null|Model
     */
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

        return NULL;
    }

    /**
     * Deletes models by a query
     *
     * @param string $model
     * @param [Query $query] if not set all instances of the model will be deleted
     * @return bool
     */
    public function deleteModels($model, Query $query = NULL)
    {
        $models = $this->getPlugins('Model');

        if (isset($models[$model])) {
            $storage = $this->getStorage($model);
            return $storage->delete($model, $query);
        }

        return FALSE;
    }

    /**
     * Extends a model with new fields. If the model does not exists, it will be created.
     *
     * @param string $model
     * @param array $fields Field definitions. Either containing real Field instances or key => value pairs
     * @param [string $format] the format of the $fields. Default is Api::DATA_FORMAT_ARRAY.
     */
    public function extendModel($model, array $fields, $format = self::DATA_FORMAT_ARRAY)
    {
        $extend_dir = $this->config['extends_path'].'/Model';
        $file = $extend_dir.'/'.$model.'.json';
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
                'name' => $model,
                'updated' => date('c'),
                'version' => $version,
                'fields' => $_fields,
            );

            file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));

            // add model to model plugins
            $instance = new DynamicModel();
            $instance->setModelName($model);

            $this->registerExtend('Model',$model);

            $this->migrate($model);

        } else {
            // TODO: throw an exception
        }
    }

    public function getExtends($type, $name)
    {
        if (isset($this->_extends[$type]) && isset($this->_extends[$type][$name])) {
            return $this->_extends[$type][$name];
        } else {
            return NULL;
        }
    }

    /**
     * Migrates the storage dabase(s) (for a given model)
     * @param [string $model]
     */
    public function migrate($model = NULL)
    {
        $storage = $this->getStorage($model);
        $storage->migrate($model);
    }
}
