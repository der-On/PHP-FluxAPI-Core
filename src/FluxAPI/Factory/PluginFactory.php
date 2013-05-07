<?php
namespace FluxAPI\Factory;

class PluginFactory
{
    protected $_api;

    /**
     * @var array Internal lookup for plugins
     */
    private $_plugins = array(
        'Model' => array(),
        'Format' => array(),
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

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }

    /**
     * Registers all available plugins found within the Plugins/ directory
     */
    public function registerPlugins()
    {
        $plugins = scandir($this->_api->config['plugins_path']);

        $allowed_plugin_types = array_keys($this->_plugins);

        foreach($plugins  as $plugin) {
            if (in_array($plugin, array('.', '..'))) {
                continue;
            }

            $plugin_base_path = $this->_api->config['plugins_path'].'/'.$plugin;

            // skip disabled root plugins
            if (in_array($plugin, $this->_api->config['plugin.options']['disabled'])) {
                continue;
            }

            if (is_dir($plugin_base_path)) {

                $plugin_dirs = scandir($plugin_base_path);

                foreach($plugin_dirs as $plugin_type) {
                    if (in_array($plugin_type, array('.', '..'))) {
                        continue;
                    }

                    $plugin_rel_path = $plugin . '/' . $plugin_type;

                    // skip disabled plugin types for this plugin root
                    if (in_array($plugin_rel_path, $this->_api->config['plugin.options']['disabled'])) {
                        continue;
                    }

                    $plugin_dir_path = $plugin_base_path.'/'.$plugin_type;

                    // directories
                    if (is_dir($plugin_dir_path) && in_array($plugin_type,$allowed_plugin_types)) {

                        $plugin_files = scandir($plugin_dir_path);

                        foreach($plugin_files as $plugin_file) {
                            if (in_array($plugin_file, array('.', '..'))) {
                                continue;
                            }

                            $plugin_file_rel_path = $plugin_rel_path . '/' . str_replace('.php','', $plugin_file);

                            // skip disabled plugin files
                            if (in_array($plugin_file_rel_path, $this->_api->config['plugin.options']['disabled'])) {
                                continue;
                            }

                            $plugin_file_path = $plugin_dir_path.'/'.$plugin_file;

                            if (is_file($plugin_file_path) && substr($plugin_file,-strlen('.php')) == '.php') {

                                $plugin_name = ucfirst(basename($plugin_file,'.php'));
                                $plugin_class_name = 'Plugins\\'.ucfirst($plugin).'\\'.ucfirst($plugin_type).'\\'.$plugin_name;

                                $this->_plugins[$plugin_type][$plugin_name] = $plugin_class_name;

                                $this->_api->registerPluginMethods($plugin_type,$plugin_name);
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
            $plugin_class_name::register($this->_api);
        }
    }

    /**
     * Registers all extensions found in the extends directory
     */
    public function registerExtends()
    {
        foreach(array_keys($this->_extends) as $type) {
            $extends_dir = $this->_api->config['extends_path'].'/'.$type;

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
        $extends_dir = $this->_api->config['extends_path'].'/'.$type;
        $file = $name.'.json';

        switch($type) {
            case 'Model':
                $this->_extends[$type][$name] = json_decode(file_get_contents($extends_dir.'/'.$file),TRUE);

                // if this is a dynamic model we have to register it as a model plugin
                if (!isset($this->_plugins[$type][$name])) {
                    $this->_plugins[$type][$name] = 'FluxAPI\DynamicModel';
                }

                $this->_api->registerPluginMethods($type,$name);
                break;
        }
    }

    /**
     * Unregisters a single extension
     *
     * @param $type
     * @param $name
     */
    public function unregisterExtend($type, $name)
    {
        if (isset($this->_extends[$type][$name])) {
            unset($this->_extends[$type][$name]);

            // if this is a dynamic model we have to remove it from the plugins
            if (isset($this->_plugins[$type][$name]) && $this->_plugins[$type][$name] == 'FluxAPI\DynamicModel') {
                unset($this->_plugins[$type][$name]);
            }

            // the api must remove the methods for this plugin
            $this->_api->unregisterPluginMethods($type,$name);
        }
    }

    /**
     * @param string $type
     * @param string $name
     * @return bool
     */
    public function hasPlugin($type, $name)
    {
        return (isset($this->_plugins[$type]) && isset($this->_plugins[$type][$name]));
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
     * Reduces a model by removing existing fields or if no fields are given the entire model extend.
     *
     * @param string $model_name
     * @param array $fields Field names to remove.
     * @param [string $format] the format of the $fields. Default is Api::DATA_FORMAT_ARRAY.
     */
    public function reduceModel($model_name, array $fields = NULL, $format = \FluxAPI\Api::DATA_FORMAT_ARRAY)
    {
        $extend_dir = $this->_api->config['extends_path'].'/Model';
        $file = $extend_dir.'/'.$model_name.'.json';

        // an extend must already exist. We cannot reduce build in fields/models.
        if (file_exists($extend_dir)) {

            // remove the extend completely
            if ($fields === NULL) {
                if (file_exists($file)) {
                    unlink($file);
                    $this->unregisterExtend('Model',$model_name);
                } else {
                    throw new \RuntimeException(sprintf('Extends file %s does not exists.', $file));
                }
            } else {
                $config = json_decode(file_get_contents($file),TRUE);

                if (!empty($config)) {
                    $version = intval($config['version']) + 1;

                    foreach($fields as $name) {
                        $i = 0;

                        while($i < count($config['fields'])) {
                            if ($config['fields'][$i]['name'] == $name) {
                                unset($config['fields'][$i]);
                                continue;
                            }
                            $i++;
                        }
                    }

                    $config = array(
                        'name' => $model_name,
                        'updated' => date('c'),
                        'version' => $version,
                        'fields' => $config['fields'],
                    );

                    file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));

                    $this->registerExtend('Model',$model_name);

                    $this->_api->migrate($model_name);
                } else {
                    throw new \RuntimeException(sprintf('Unable to parse extends file %s', $file));
                }
            }
        } else {
            throw new \RuntimeException(sprintf('Extends directory %s does not exist.', $extend_dir));
        }
    }

    /**
     * Extends a model with new fields. If the model does not exists, it will be created.
     *
     * @param string $model_name
     * @param array $fields Field definitions. Either containing real Field instances or key => value pairs
     * @param [string $format] the format of the $fields. Default is Api::DATA_FORMAT_ARRAY.
     */
    public function extendModel($model_name, array $fields, $format = \FluxAPI\Api::DATA_FORMAT_ARRAY)
    {
        $extend_dir = $this->_api->config['extends_path'].'/Model';
        $file = $extend_dir.'/'.$model_name.'.json';
        $version = 1;

        if (!file_exists($extend_dir)) {

            // create models directory if not existing
            if (!mkdir($extend_dir,0755,TRUE)) {
                throw new \RuntimeException(sprintf('Unable to create extends directory %s', $extend_dir));
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
                $_fields = $config['fields'];
            } else {
                $_fields = array();
            }

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

            $this->_api->migrate($model_name);

        } else {
            throw new \RuntimeException(sprintf('Unable to parse extends file %s', $file));
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
}