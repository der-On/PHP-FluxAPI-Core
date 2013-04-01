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

        $this['plugins'] = $this->share(function() use ($api) {
            return $api['pplugin_factory'];
        });

        $this['plugin_factory'] = $this->share(function() use ($api) {
            return new Factory\PluginFactory($api);
        });

        $this['methods'] = $this->share(function() use ($api) {
            return $api['method_factory'];
        });

        $this['method_factory'] = $this->share(function() use ($api) {
            return new Factory\MethodFactory($api);
        });

        $this['dispatcher_class'] = 'Symfony\\Component\\EventDispatcher\\EventDispatcher';
        $this['dispatcher'] = $this->share(function () use ($api) {
            $dispatcher = new $api['dispatcher_class']();

            $dispatcher->addSubscriber(new \FluxAPI\EventListener\ModelListener($api));

            return $dispatcher;
        });

        // register Serializer
        $this->app->register(new \Silex\Provider\SerializerServiceProvider());

        $this['plugin_factory']->registerPlugins();
        $this['plugin_factory']->registerExtends();
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
        return $this['method_factory']->callMethod($method, $arguments);
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
        $this['method_factory']->registerMethod('load'.$model_name.'s', function($query = NULL, $format = NULL) use ($model_name, $self) {
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
        });

        // load single model instance
        $this['method_factory']->registerMethod('load'.$model_name, function($query, $format = NULL) use ($model_name, $self) {

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
        });

        // save a model instance
        $this['method_factory']->registerMethod('save'.$model_name, function($instance) use ($model_name, $self) {
            return $self['model_factory']->save($model_name,$instance);
        });

        // delete model multiple instances
        $this['method_factory']->registerMethod('delete'.$model_name.'s', function($query = NULL) use ($model_name, $self) {
            return $self['model_factory']->delete($model_name, $query);
        });

        // delete a single model instance
        $this['method_factory']->registerMethod('delete'.$model_name, function($query) use ($model_name, $self) {
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
        });

        // update multiple model instances
        $this['method_factory']->registerMethod('update'.$model_name.'s', function($query, array $data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            if (is_array($query)) {
                $ids = $query;

                $query = new Query();
                $query->filter('in',array('id',$ids));
            }
            return $self['model_factory']->update($model_name, $query, $data, $format);
        });

        // update a single model instance
        $this['method_factory']->registerMethod('update'.$model_name, function($id, array $data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            $query = new Query();
            $query->filter('equal',array('id',$id));
            $query->filter('limit',array(0,1));

            return $self['model_factory']->update($model_name, $query, $data, $format);
        });

        // create a model instance
        $this['method_factory']->registerMethod('create'.$model_name, function($data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            return $self['model_factory']->create($model_name, $data, $format);
        });

        // extend an existing model
        $this['method_factory']->registerMethod('extend'.$model_name, function($fields = array(), $format = self::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            return $self['plugin_factory']->extendModel($model_name, $fields, $format);
        });
    }

    public function extendModel($model_name, array $fields, $format = self::DATA_FORMAT_ARRAY)
    {
        return $this['plugin_factory']->extendModel($model_name, $fields, $format);
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
