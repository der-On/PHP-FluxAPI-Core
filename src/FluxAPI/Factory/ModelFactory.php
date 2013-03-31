<?php

namespace FluxAPI\Factory;

use \FluxAPI\Event\ModelEvent;

class ModelFactory
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
     * @param [array $data] if set the model will contain that initial data
     * @param [string $format] the format of the given $data
     * @return null|Model
     */
    public function create($model_name, array $data = array(), $format = \FluxAPI\Api::DATA_FORMAT_ARRAY)
    {
        $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_CREATE, new ModelEvent($model_name));

        $models = $this->_api->getPlugins('Model');
        $extend = $this->_api->getExtends('Model',$model_name);

        if (isset($models[$model_name])) {
            switch($format) {
                case \FluxAPI\Api::DATA_FORMAT_ARRAY:
                    $instance = $this->createFromArray($model_name, $data);
                    break;

                case \FluxAPI\Api::DATA_FORMAT_JSON:
                    $instance = $this->createFromJson($model_name, $data);
                    break;

                case \FluxAPI\Api::DATA_FORMAT_XML:
                    $instance = $this->createFromXml($model_name, $data);
                    break;

                case \FluxAPI\Api::DATA_FORMAT_YAML:
                    $instance = $this->createFromYaml($model_name, $data);
                    break;

                default:
                    $instance = $this->createFromArray($model_name, $data);
            }

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

    /**
     * Returns a new model instance with data from an array
     *
     * @param string $model_name
     * @param [array $data]
     * @return Model
     */
    public function createFromArray($model_name, array $data = array())
    {
        $className = $this->_api->getPluginClass('Model',$model_name);

        if (!empty($className) && !empty($data)) {
            return new $className($data);
        } else {
            return new $className();
        }
    }

    /**
     * Returns a new model instance with data form an object
     *
     * @param string $model_name
     * @param object $object
     * @return Model
     */
    public function createFromObject($model_name, $object)
    {
        $data = array();

        if (is_object($object)) {
            foreach(get_object_vars($object) as $name => $value) {
                $data[$name] = $value;
            }
        }

        return $this->createFromArray($model_name, $data);
    }

    /**
     * Returns a new model instance with data from a JSON string
     *
     * @param string $model_name
     * @param string $json
     * @return Model|null
     */
    public function createFromJson($model_name, $json)
    {
        $data = array();

        if (!empty($json)) {
            $data = json_decode($json,TRUE);
        }

        return $this->createFromArray($model_name, $data);
    }

    /**
     * Returns a new model instance with data from a XML string
     *
     * @param string $model_name
     * @param string $xml
     * @return Model|null
     */
    public function createFromXml($model_name, $xml)
    {
        $data = array();

        if (!empty($xml)) {
            $parser = new \Symfony\Component\Serializer\Encoder\XmlEncoder($model_name);
            $data = $parser->decode($xml,'xml');
        }

        return $this->createFromArray($model_name, $data);
    }

    /**
     * Returns a new model instance with data from a YAML string
     *
     * @param string $model_name
     * @param string $yaml
     * @return Model|null
     */
    public function createFromYaml($model_name, $yaml)
    {
        $data = array();

        if (!empty($yaml)) {
            $parser = new \Symfony\Component\Yaml\Parser();
            $data = $parser->parse($yaml);
        }

        return $this->createFromArray($model_name, $data);
    }

    /**
     * Loads and returns a list of Model instances
     *
     * @param string $model_name
     * @param [Query $query] if not set all instances of the model are loaded
     * @return array|null
     */
    public function load($model_name, \FluxAPI\Query $query = NULL)
    {
        $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_LOAD, new ModelEvent($model_name, $query));

        $models = $this->_api->getPlugins('Model');

        if (isset($models[$model_name])) {
            $instances = $this->_api['storage_factory']->get($model_name)->load($model_name,$query);

            foreach($instances as &$instance) {
                $this->_api['dispatcher']->dispatch(ModelEvent::LOAD, new ModelEvent($model_name, $query, $instance));
            }

            return $instances;
        }

        return array();
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
        $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_SAVE, new ModelEvent($model_name));

        $models = $this->_api->getPlugins('Model');

        if (isset($models[$model_name])) {
            if (empty($instances)) {
                return FALSE;
            }

            $storage = $this->_api['storage_factory']->get($model_name);

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
     * @param array $data
     * @return bool
     */
    public function update($model_name, \FluxAPI\Query $query, array $data)
    {
        $this->_api['dispatcher']->dispatch(ModelEvent::BEFORE_UPDATE, new ModelEvent($model_name, $query));

        $storage = $this->_api['storage_factory']->get($model_name);

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

        $models = $this->_api->getPlugins('Model');

        if (isset($models[$model_name])) {
            $storage = $this->_api['storage_factory']->get($model_name);
            $return = $storage->delete($model_name, $query);
            $this->_api['dispatcher']->dispatch(ModelEvent::DELETE, new ModelEvent($model_name, $query));
            return $return;
        }

        return FALSE;
    }
}