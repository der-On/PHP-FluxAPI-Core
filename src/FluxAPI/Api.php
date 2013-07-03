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

    const EARLY_EVENT = -512;
    const LATE_EVENT = 512;

    const PERMISSION_ALLOW = 'allow';
    const PERMISSION_DENY = 'deny';

    const MODEL_LOAD = 'load';
    const MODEL_UPDATE = 'update';
    const MODEL_DELETE = 'delete';
    const MODEL_CREATE = 'create';
    const MODEL_SAVE = 'save';

    const LOG_INFO = 'info';
    const LOG_DEBUG = 'debug';
    const LOG_ERROR = 'error';
    const LOG_WARNING = 'warning';

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

        $api = $this;
        $this->app = $app;

        // overwrite default config with given config
        $this->config = array_replace_recursive(
            $this->getDefaultConfig(),
            $config
        );

        $this['logger'] = NULL;

        // use monolog logger by default
        if (isset($app['monolog'])) {
            $this['logger'] = $app['monolog'];
        }

        $this['caches'] = $this->share(function() use ($api) {
            return $api['cache_factory'];
        });

        $this['cache_factory'] = function() use ($api) {
            return new Factory\CacheFactory($api);
        };

        $this['models'] = $this->share(function() use ($api) {
           return $api['model_factory'];
        });

        $this['model_factory'] = function() use ($api) {
            return new Factory\ModelFactory($api);
        };

        $this['storages'] = $this->share(function() use ($api) {
           return $api['storage_factory'];
        });

        $this['storage_factory'] = function() use ($api) {
            return new Factory\StorageFactory($api);
        };

        $this['controllers'] = $this->share(function() use ($api) {
           return $api['controller_factory'];
        });

        $this['controller_factory'] = function() use ($api) {
           return new Factory\ControllerFactory($api);
        };

        $this['permissions'] = $this->share(function() use ($api) {
            return $api['permission_factory'];
        });

        $this['permission_factory'] = function() use ($api) {
            return new Factory\PermissionFactory($api);
        };

        $this['plugins'] = $this->share(function() use ($api) {
            return $api['plugin_factory'];
        });

        $this['plugin_factory'] = function() use ($api) {
            return new Factory\PluginFactory($api);
        };

        $this['methods'] = $this->share(function() use ($api) {
            return $api['method_factory'];
        });

        $this['method_factory'] = function() use ($api) {
            return new Factory\MethodFactory($api);
        };

        $this['exception_handler'] = $this->share(function () use ($api) {
            return new ExceptionHandler($api->app['debug']);
        });

        $this['dispatcher_class'] = 'Symfony\\Component\\EventDispatcher\\EventDispatcher';
        $this['dispatcher'] = $this->share(function () use ($api) {
            $dispatcher = new $api['dispatcher_class']();

            $dispatcher->addSubscriber(new \FluxAPI\EventListener\ModelListener($api));

            return $dispatcher;
        });

        // register Serializer
        $this->app->register(new \Silex\Provider\SerializerServiceProvider());

        $this['plugins']->registerPlugins();
        $this['plugins']->registerExtends();
    }

    /**
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this['logger'] = $logger;
    }

    /**
     * Log a message of certain type
     *
     * @param string $message
     * @param string $type one of Api::LOG_INFO, Api::LOG_DEBUG, Api::LOG_WARNING, Api::LOG_ERROR
     */
    public function log($message, $type = Api::LOG_INFO)
    {
        if ($this['logger']) {
            switch($type) {
                case Api::LOG_INFO:
                    $this['logger']->addInfo($message);
                    break;

                case Api::LOG_DEBUG:
                    $this['logger']->addDebug($message);
                    break;

                case Api::LOG_WARNING:
                    $this['logger']->addWarning($message);
                    break;

                case Api::LOG_ERROR:
                    $this['logger']->addError($message);
                    break;
            }
        }
    }

    public function getDefaultConfig()
    {
        return array(
            'plugins_path' => realpath(__DIR__ . '/../../../../../Plugins'),
            'extends_path' => realpath(__DIR__ . '/../../../../../extends'),
            'temp_path' => realpath(__DIR__ . '/../../../../../tmp'),
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
            ),
            'plugin.options' => array(
                'disabled' => array()
            ),
            'permission.options' => array(
                'default' => self::PERMISSION_ALLOW
            )
        );
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
        return $this['methods']->callMethod($method, $arguments);
    }

    /**
     * Registers all magic methods for a plugin
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

            case 'Controller':
                $this->registerControllerMethods($name);
                break;
        }
    }

    /**
     * Unregisters all magic methods of a plugin
     *
     * @param string $type the plugin type
     * @param string $name the plugin name
     */
    public function unregisterPluginMethods($type, $name)
    {
        switch (ucfirst($type)) {
            case 'Model':
                $this->unregisterModelMethods($name);
                break;

            case 'Controller':
                $this->unregisterControllerMethods($name);
                break;
        }
    }

    /**
     * Loads a list of models of same type
     *
     * @param string $model_name
     * @param null|Query $query
     * @param null|string $format
     * @return mixed
     */
    public function load($model_name, $query = NULL, $format = NULL)
    {
        $models =  $this['models']->load($model_name, $query, $format);
        return $models;
    }

    /**
     * Loads one model
     *
     * @param string $model_name
     * @param null|Query $query
     * @param null|string $format
     * @return mixed
     */
    public function loadFirst($model_name, $query = NULL, $format = NULL)
    {
        if (empty($query)) {
            $query = new Query();
        }

        if (is_string($query)) {
            $id = $query;
            $query = new Query();
            $query->filter('equal',array('id',$id));
        }

        if (!$query->hasFilter('limit')) {
            $query->filter('limit',array(0,1));
        }

        $model = $this['models']->loadFirst($model_name, $query, $format);

        return $model;
    }

    /**
     * Saves a single model instance
     *
     * @param string $model_name
     * @param Model $instance
     * @return mixed
     */
    public function save($model_name, $instance)
    {
        return $this['models']->save($model_name, $instance);
    }

    /**
     * Deletes all models of same type (matching a query)
     *
     * @param string $model_name
     * @param null|Query $query
     * @return mixed
     */
    public function delete($model_name, $query = NULL)
    {
        return $this['models']->delete($model_name, $query);
    }

    /**
     * Deletes one model (matching a query)
     *
     * @param string $model_name
     * @param Query $query
     */
    public function deleteFirst($model_name, $query)
    {
        if (is_object($query) && is_subclass_of($query, '\FluxAPI\\Model')) {
            $query = $query->id;
        }

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

        return $this['models']->delete($model_name, $query);
    }

    /**
     * Updates all models (matching a query)
     *
     * @param string $model_name
     * @param Query $query
     * @param array $data
     * @param string $format
     * @return mixed
     */
    public function update($model_name, $query, array $data = array(), $format = Api::DATA_FORMAT_ARRAY)
    {
        if (is_array($query)) {
            $ids = $query;

            $query = new Query();
            $query->filter('in',array('id',$ids));
        }
        return $this['models']->update($model_name, $query, $data, $format);
    }

    /**
     * Updates one model (matching a query)
     *
     * @param string $model_name
     * @param Query $query
     * @param array $data
     * @param string $format
     * @return null
     */
    public function updateFirst($model_name, $query, array $data = array(), $format = Api::DATA_FORMAT_ARRAY)
    {
        if (is_string($query)) {
            $id = $query;

            $query = new Query();
            $query->filter('equal',array('id',$id));
        }
        $query->filter('limit',array(0,1));

        $result = $this['models']->update($model_name, $query, $data, $format);

        if (count($result) > 0) {
            return $result[0];
        } else {
            return NULL;
        }
    }

    /**
     * Creates a model instance
     *
     * @param string $model_name
     * @param array $data
     * @param string $format
     * @return mixed
     */
    public function create($model_name, $data = array(), $format = Api::DATA_FORMAT_ARRAY)
    {
        return $this['models']->create($model_name, $data, $format);
    }

    /**
     * Extends an existing model type
     *
     * @param string $model_name
     * @param array $fields
     * @param string $format
     * @return mixed
     */
    public function extendModel($model_name, $fields = array(), $format = self::DATA_FORMAT_ARRAY)
    {
        return $this['plugins']->extendModel($model_name, $fields, $format);
    }

    /**
     * Reduces a model type
     *
     * @param string $model_name
     * @param null|array $fields
     * @param string $format
     * @return mixed
     */
    public function reduceModel($model_name, $fields = NULL, $format = self::DATA_FORMAT_ARRAY)
    {
        return $this['plugins']->reduceModel($model_name, $fields, $format);
    }

    /**
     * Counts the number of models (matching a query)
     *
     * @param string $model_name
     * @param Query $query
     * @return int
     */
    public function count($model_name, Query $query = NULL)
    {
        return $this['models']->count($model_name, $query);
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
        $this['methods']->registerMethod('load'.$model_name.'s', function($query = NULL, $format = NULL) use ($model_name, $self) {
            return $self->load($model_name, $query, $format);
        });

        // load single model instance
        $this['methods']->registerMethod('load'.$model_name, function($query = NULL, $format = NULL) use ($model_name, $self) {
            return $self->loadFirst($model_name, $query, $format);
        });

        // save a model instance
        $this['methods']->registerMethod('save'.$model_name, function($instance) use ($model_name, $self) {
            return $self->save($model_name, $instance);
        });

        // delete model multiple instances
        $this['methods']->registerMethod('delete'.$model_name.'s', function($query = NULL) use ($model_name, $self) {
            return $self->delete($model_name, $query);
        });

        // delete a single model instance
        $this['methods']->registerMethod('delete'.$model_name, function($query) use ($model_name, $self) {
            return $self->deleteFirst($model_name, $query);
        });

        // update multiple model instances
        $this['methods']->registerMethod('update'.$model_name.'s', function($query, array $data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            return $self->update($model_name, $query, $data, $format);
        });

        // update a single model instance
        $this['methods']->registerMethod('update'.$model_name, function($query, array $data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            return $self->updateFirst($model_name, $query, $data, $format);
        });

        // create a model instance
        $this['methods']->registerMethod('create'.$model_name, function($data = array(), $format = Api::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            return $self->create($model_name, $data, $format);
        });

        // extend an existing model
        $this['methods']->registerMethod('extend'.$model_name, function($fields = array(), $format = self::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            return $self->extendModel($model_name, $fields, $format);
        });

        // reduce an existing model
        $this['methods']->registerMethod('reduce'.$model_name, function($fields = NULL, $format = self::DATA_FORMAT_ARRAY) use ($model_name, $self) {
            return $self->reduceModel($model_name, $fields, $format);
        });

        // count models
        $this['methods']->registerMethod('count' . $model_name . 's', function(Query $query = NULL) use ($model_name, $self) {
           return $self->count($model_name, $query);
        });
    }

    /**
     * Unregisters all magic methods for a registered model
     *
     * @param string $model_name
     */
    public function unregisterModelMethods($model_name)
    {
        $this['methods']
            ->unregisterMethod('create'.$model_name)
            ->unregisterMethod('create'.$model_name.'s')
            ->unregisterMethod('load'.$model_name)
            ->unregisterMethod('load'.$model_name.'s')
            ->unregisterMethod('update'.$model_name)
            ->unregisterMethod('update'.$model_name.'s')
            ->unregisterMethod('delete'.$model_name)
            ->unregisterMethod('delete'.$model_name.'s')
            ->unregisterMethod('extend'.$model_name)
            ->unregisterMethod('reduce'.$model_name)
            ->unregisterMethod('count'.$model_name.'s')
            ;
    }

    public function registerControllerMethods($controller_name)
    {
        $self = $this;
        $actions = $this['controllers']->getActions($controller_name);

        // create methods in the form of ->controllerName_actionName()
        foreach($actions as $action => $options) {
            $this['methods']->registerMethod(
                lcfirst($controller_name) . '_' . lcfirst($action),
                function() use ($controller_name, $action, $self) {
                    return $self['controllers']->call($controller_name, $action, func_get_args());
                }
            );
        }
    }

    public function unregisterControllerMethods($controller_name)
    {
        $actions = $this['controllers']->getActions($controller_name);

        // remove methods in the form of ->controllerName_actionName()
        foreach($actions as $action => $options) {
            $this['methods']->unregisterMethod(
                lcfirst($controller_name) . '_' . lcfirst($action)
            );
        }
    }

    /**
     * Migrates the storage dabase(s) (for a given model)
     * @param [string $model_name]
     */
    public function migrate($model_name = NULL)
    {
        $storage = $this['storages']->getStorage($model_name);
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
