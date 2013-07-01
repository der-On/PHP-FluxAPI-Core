<?php

namespace FluxAPI\Factory;

use \FluxAPI\Event\ModelEvent;
use \FluxAPI\Exception\AccessDeniedException;

class ModelFactory extends \Pimple
{
    protected $_api;

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
        $this['permissions'] = $api['permissions'];
        $this['dispatcher'] = $api['dispatcher'];
        $this['plugins'] = $api['plugins'];
        $this['caches'] = $api['caches'];
        $this['storages'] = $api['storages'];
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
        // skip if user has no access
        if (!$this['permissions']->hasModelAccess($model_name, null, \FluxAPI\Api::MODEL_CREATE)) {
            throw new AccessDeniedException(sprintf('You are not allowed to create a %s model.', $model_name));
            return null;
        }

        $this['dispatcher']->dispatch(ModelEvent::BEFORE_CREATE, new ModelEvent($model_name));

        $models = $this['plugins']->getPlugins('Model');
        $extend = $this['plugins']->getExtends('Model',$model_name);

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

            $this['dispatcher']->dispatch(ModelEvent::CREATE, new ModelEvent($model_name, NULL, $instance));
            return $instance;
        }

        return NULL;
    }

    protected function _modelDataFromFormat($model_name, $data, $format)
    {
        $formats = $this['plugins']->getPlugins('Format');

        if (!empty($format) && is_string($format) && in_array(ucfirst($format),array_keys($formats))) {
            $format_class = $formats[ucfirst($format)];

            $format_class::setApi($this->_api);
            $data = $format_class::decodeForModel($model_name, $data);
        }

        return $data;
    }

    protected function _modelsToFormat($model_name, \FluxAPI\Collection\ModelCollection $models, $format)
    {
        $formats = $this['plugins']->getPlugins('Format');

        if (!empty($format) && is_string($format) && in_array(ucfirst($format),array_keys($formats))) {
            $format_class = $formats[ucfirst($format)];

            $format_class::setApi($this->_api);

            $models = $format_class::encodeFromModels($model_name, $models);
        }
        return $models;
    }

    protected function _modelToFormat($model_name, \FluxAPI\Model $model, $format)
    {
        $formats = $this['plugins']->getPlugins('Format');

        if (!empty($format) && is_string($format) && in_array(ucfirst($format),array_keys($formats))) {
            $format_class = $formats[ucfirst($format)];

            $format_class::setApi($this->_api);
            $model = $format_class::encodeFromModel($model_name, $model);
        }
        return $model;
    }

    /**
     * Validates a model instance
     *
     * @param \FluxAPI\Model $model
     * @param array $explicit_fields - if set only this fields will be validated
     * @return bool - true if model is valid, else false
     */
    protected function _validate(\FluxAPI\Model $model, array $explicit_fields = NULL)
    {
        foreach($model->getFields() as $field) {
            $field_name = $field->name;

            // ignore field if it is not in explicit
            if (is_array($explicit_fields) && count($explicit_fields) > 0 && !in_array($field_name, $explicit_fields)) {
                continue;
            }

            foreach($field->getValidators() as $key => $validator_name) {
                if (is_array($validator_name)) {
                    $validator_options = $validator_name;
                    $validator_name = $key;
                } else {
                    $validator_options = array();
                }
                $validator_class = $this['plugins']->getPluginClass('FieldValidator', $validator_name);

                if ($validator_class) {
                    $validator = new $validator_class($this->_api);

                    if (!$validator->validate($model->$field_name, $field, $model, $validator_options)) {
                        return FALSE;
                    }
                }
            }
        }

        return TRUE;
    }

    /**
     * @param string $model_name
     * @param \FluxAPI\Query $query
     * @return null|array
     */
    public function getCachedModels($model_name, \FluxAPI\Query $query = NULL)
    {
        $source = new \FluxAPI\Cache\ModelSource($model_name, $query);
        $instances = $this['caches']->getCached(\FluxAPI\Cache::TYPE_MODEL, $source);

        return $instances;
    }

    public function cacheModels($model_name, \FluxAPI\Query $query = NULL, \FluxAPI\Collection\ModelCollection $instances)
    {
        $source = new \FluxAPI\Cache\ModelSource($model_name, $query, $instances);
        $this['caches']->store(\FluxAPI\Cache::TYPE_MODEL, $source, $instances);
    }

    public function removeCachedModels($model_name, \FluxAPI\Collection\ModelCollection $instances)
    {
        $source = new \FluxAPI\Cache\ModelSource($model_name, NULL, $instances);
        $this['caches']->remove(\FluxAPI\Cache::TYPE_MODEL, $source);
    }

    /**
     * Loads and returns a list of Model instances
     *
     * @param string $model_name
     * @param [Query $query] if not set all instances of the model are loaded
     * @param [string $format]
     * @return array
     */
    public function load($model_name, \FluxAPI\Query $query = NULL, $format = NULL)
    {
        // skip if user has no access
        if (!$this['permissions']->hasModelAccess($model_name, null, \FluxAPI\Api::MODEL_LOAD)) {
            throw new AccessDeniedException(sprintf('You are not allowed to load %s models.', $model_name));
            return null;
        }

        $this['dispatcher']->dispatch(ModelEvent::BEFORE_LOAD, new ModelEvent($model_name, $query));

        $models = $this['plugins']->getPlugins('Model');

        if (isset($models[$model_name])) {
            $cached = TRUE;
            $instances = $this->getCachedModels($model_name, $query);

            if ($instances === NULL) {
                $cached = FALSE;
                $instances = $this['storages']->getStorage($model_name)->load($model_name,$query);
            }

            foreach($instances as $instance) {
                $this['dispatcher']->dispatch(ModelEvent::LOAD, new ModelEvent($model_name, $query, $instance));
            }

            if (!$cached) $this->cacheModels($model_name, $query, $instances);

            return $this->_modelsToFormat($model_name, $instances, $format);
        }

        return array();
    }

    public function loadFirst($model_name, \FluxAPI\Query $query = NULL, $format = NULL)
    {
        // skip if user has no access
        if (!$this['permissions']->hasModelAccess($model_name, null, \FluxAPI\Api::MODEL_LOAD)) {
            throw new AccessDeniedException(sprintf('You are not allowed to load a %s model.', $model_name));
            return null;
        }

        $query->filter('limit',array(0,1));
        $models = $this->load($model_name, $query);

        if (!empty($models) && $models->count() > 0) {
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
        // skip if user has no access
        if (!$this['permissions']->hasModelAccess($model_name, null, \FluxAPI\Api::MODEL_SAVE)) {
            throw new AccessDeniedException(sprintf('You are not allowed to save %s models.', $model_name));
            return null;
        }

        if (\FluxAPI\Collection\ModelCollection::isInstance($instances)) {
            foreach($instances as $instance) {
                // skip if user has no access
                if (!$this['permissions']->hasModelAccess($model_name, $instance, \FluxAPI\Api::MODEL_CREATE)) {
                    throw new AccessDeniedException(sprintf('You are not allowed to save the %s model with the id %s.', $model_name, $instance->id));
                    return FALSE;
                }

                $this['dispatcher']->dispatch(ModelEvent::BEFORE_SAVE, new ModelEvent($model_name, NULL, $instance));
            }
        } else {
            $this['dispatcher']->dispatch(ModelEvent::BEFORE_SAVE, new ModelEvent($model_name, NULL, $instances));
        }

        $models = $this['plugins']->getPlugins('Model');

        if (isset($models[$model_name])) {
            if (empty($instances)) {
                return FALSE;
            }

            $storage = $this['storages']->getStorage($model_name);

            if (\FluxAPI\Collection\ModelCollection::isInstance($instances)) {
                foreach($instances as $instance) {
                    if ($this->_validate($instance)) {
                        $storage->save($model_name, $instance);
                        $this['dispatcher']->dispatch(ModelEvent::SAVE, new ModelEvent($model_name, NULL, $instance));

                        // update cache
                        //$this->cacheModels($model_name, NULL, array($instance));
                    } else {
                        throw new \InvalidArgumentException(sprintf('The %s model is invalid.', $model_name));
                    }
                }

                return TRUE;
            } else {
                if ($this->_validate($instances)) {
                    $return = $storage->save($model_name, $instances);
                    $this['dispatcher']->dispatch(ModelEvent::SAVE, new ModelEvent($model_name, NULL, $instances));

                    // update cache
                    //$this->cacheModels($model_name, NULL, array($instances));
                    return $return;
                } else {
                    throw new \InvalidArgumentException(sprintf('The %s model is invalid.', $model_name));
                    return FALSE;
                }
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
        // skip if user has no access
        if (!$this['permissions']->hasModelAccess($model_name, null, \FluxAPI\Api::MODEL_UPDATE)) {
            throw new AccessDeniedException(sprintf('You are not allowed to update %s models.', $model_name));
            return null;
        }

        $this['dispatcher']->dispatch(ModelEvent::BEFORE_UPDATE, new ModelEvent($model_name, $query));

        $storage = $this['storages']->getStorage($model_name);

        // validate data
        $createMethod = 'create' . ucfirst($model_name);
        $model_instance = $this->_api->$createMethod($data);

        if ($this->_validate($model_instance, array_keys($data))) {

            foreach($data as $key => $value) {
                $data[$key] = $model_instance->$key;
            }

            $return = $storage->update($model_name, $query, $data);
            $this['dispatcher']->dispatch(ModelEvent::UPDATE, new ModelEvent($model_name, $query));

            return $return;
        } else {
            throw new \InvalidArgumentException(sprintf('The %s model is invalid.', $model_name));
            return NULL;
        }
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
        // skip if user has no access
        if (!$this['permissions']->hasModelAccess($model_name, null, \FluxAPI\Api::MODEL_DELETE)) {
            throw new AccessDeniedException(sprintf('You are not allowed to delete %s models.', $model_name));
            return null;
        }

        $this['dispatcher']->dispatch(ModelEvent::BEFORE_DELETE, new ModelEvent($model_name, $query));

        $models = $this['plugins']->getPlugins('Model');

        if (isset($models[$model_name])) {
            $storage = $this['storages']->getStorage($model_name);
            $instances = $storage->load($model_name, $query);

            $return = $storage->delete($model_name, $query);

            $this['dispatcher']->dispatch(ModelEvent::DELETE, new ModelEvent($model_name, $query));

            // remove from cache
            $this->removeCachedModels($model_name, $instances);
            return $return;
        }

        return FALSE;
    }

    /**
     * Counts the number of all model instances or the ones matching a query.
     *
     * @param string $model_name
     * @param [\FluxAPI\Query $query] - if not given the count for all instances will be returned
     * @return int
     */
    public function count($model_name, \FluxAPI\Query $query = NULL)
    {
        $storage = $this['storages']->getStorage($model_name);
        $return = $storage->count($model_name, $query);
        return $return || 0;
    }
}