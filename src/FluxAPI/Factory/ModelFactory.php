<?php

namespace FluxAPI\Factory;

use \FluxAPI\Event\ModelEvent;

class ModelFactory extends \Pimple
{
    protected $_api;

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }

    /**
     * Creates a new instance of a model
     *
     * @param string $model_name
     * @param [mixed $data] if set the model will contain that initial data
     * @param [string $format] the format of the given $data - if not set the data will be treated as an array
     * @param [bool $isNew] true if the model is new, false if it is created due to a load call.
     * @return null|Model
     */
    public function create($model_name, $data = NULL, $format = NULL, $isNew = TRUE)
    {
        $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_CREATE, new ModelEvent($model_name));

        $models = $this->_api['plugins']->getPlugins('Model');
        $extend = $this->_api['plugins']->getExtends('Model',$model_name);

        if (isset($models[$model_name])) {

            $model_class = $models[$model_name];
            $data = $this->_modelDataFromFormat($model_name, $data, $format);

            // do not populate new model with an id
            if ($isNew && !empty($data) && is_array($data) && isset($data['id'])) {
                unset($data['id']);
            }

            $instance = new $model_class($this->_api, $data);

            if (!empty($extend) && $instance->getModelName() != $model_name) {
                $instance->setModelName($model_name);
                $instance->addExtends();
                $instance->setDefaults();
                $instance->populate($data);
            }

            $this->_api['dispatcher']->dispatch(ModelEvent::CREATE, new ModelEvent($model_name, NULL, $instance));
            return $instance;
        }

        return NULL;
    }

    protected function _modelDataFromFormat($model_name, $data, $format)
    {
        $formats = $this->_api['plugins']->getPlugins('Format');

        if (!empty($format) && is_string($format) && in_array(ucfirst($format),array_keys($formats))) {
            $format_class = $formats[ucfirst($format)];

            $format_class::setApi($this->_api);
            $data = $format_class::decodeForModel($model_name, $data);
        }

        return $data;
    }

    protected function _modelsToFormat($model_name, array $models, $format)
    {
        $formats = $this->_api['plugins']->getPlugins('Format');

        if (!empty($format) && is_string($format) && in_array(ucfirst($format),array_keys($formats))) {
            $format_class = $formats[ucfirst($format)];

            $format_class::setApi($this->_api);
            $models = $format_class::encodeFromModels($model_name, $models);
        }
        return $models;
    }

    protected function _modelToFormat($model_name, \FluxAPI\Model $model, $format)
    {
        $formats = $this->_api['plugins']->getPlugins('Format');

        if (!empty($format) && is_string($format) && in_array(ucfirst($format),array_keys($formats))) {
            $format_class = $formats[ucfirst($format)];

            $format_class::setApi($this->_api);
            $model = $format_class::encodeFromModel($model_name, $model);
        }
        return $model;
    }

    /**
     * Loads and returns a list of Model instances
     *
     * @param string $model_name
     * @param [Query $query] if not set all instances of the model are loaded
     * @param [string $format]
     * @return array|null
     */
    public function load($model_name, \FluxAPI\Query $query = NULL, $format = NULL)
    {
        $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_LOAD, new ModelEvent($model_name, $query));

        $models = $this->_api['plugins']->getPlugins('Model');

        if (isset($models[$model_name])) {
            $instances = $this->_api['storages']->get($model_name)->load($model_name,$query);

            foreach($instances as &$instance) {
                $this->_api['dispatcher']->dispatch(ModelEvent::LOAD, new ModelEvent($model_name, $query, $instance));
            }

            return $this->_modelsToFormat($model_name, $instances, $format);
        }

        return NULL;
    }

    public function loadFirst($model_name, \FluxAPI\Query $query = NULL, $format = NULL)
    {
        $query->filter('limit',array(0,1));
        $models = $this->load($model_name, $query);

        if (!empty($models)) {
            return $this->_modelToFormat($model_name, $models[0], $format);
        }

        return NULL;
    }

    /**
     * Saves a list of or a single model instance
     *
     * @param string $model_name
     * @param array|Model $instances
     * @return bool
     */
    public function save($model_name, $instances)
    {
        if (is_array($instances)) {
            foreach($instances as $instance) {
                $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_SAVE, new ModelEvent($model_name, NULL, $instance));
            }
        } else {
            $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_SAVE, new ModelEvent($model_name, NULL, $instances));
        }


        $models = $this->_api['plugins']->getPlugins('Model');

        if (isset($models[$model_name])) {
            if (empty($instances)) {
                return FALSE;
            }

            $storage = $this->_api['storages']->get($model_name);

            if (is_array($instances)) {
                foreach($instances as &$instance) {
                    $storage->save($model_name,$instance);
                    $this->_api['dispatcher']->dispatch(ModelEvent::SAVE, new ModelEvent($model_name, NULL, $instance));
                }
                return TRUE;
            } else {
                $return = $storage->save($model_name,$instances);
                $this->_api['dispatcher']->dispatch(ModelEvent::SAVE, new ModelEvent($model_name, NULL, $instances));
                return $return;
            }
        }

        return FALSE;
    }

    /**
     * Updates models with certain data
     *
     * @param string $model_name
     * @param Query $query
     * @param mixed $data
     * @param [string $format] data format - if not set the data will be treated as an array
     * @return bool
     */
    public function update($model_name, \FluxAPI\Query $query, $data, $format = NULL)
    {
        $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_UPDATE, new ModelEvent($model_name, $query));

        $storage = $this->_api['storages']->get($model_name);

        $return = $storage->update($model_name, $query, $data);
        $this->_api['dispatcher']->dispatch(ModelEvent::UPDATE, new ModelEvent($model_name, $query));
        return $return;
    }

    /**
     * Deletes models by a query
     *
     * @param string $model_name
     * @param [Query $query] if not set all instances of the model will be deleted
     * @return bool
     */
    public function delete($model_name, \FluxAPI\Query $query = NULL)
    {
        $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_DELETE, new ModelEvent($model_name, $query));

        $models = $this->_api['plugins']->getPlugins('Model');

        if (isset($models[$model_name])) {
            $storage = $this->_api['storages']->get($model_name);
            $return = $storage->delete($model_name, $query);
            $this->_api['dispatcher']->dispatch(ModelEvent::DELETE, new ModelEvent($model_name, $query));
            return $return;
        }

        return FALSE;
    }
}