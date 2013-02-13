<?php
namespace FluxAPI;

class Core
{
    private $_plugins = array(
        'model' => array(),
        'controller' => array(),
        'storage' => array(),
        'cache' => array(),
        'permission' => array()
    );

    public $api = NULL;

    public $config = array();

    public function __construct($config = array())
    {
        // overwrite default config with given config
        $this->config = array_merge(
            array(
                'plugins_path' => realpath(__DIR__ . '/../../src/Plugins')
            ),
            $config
        );

        $this->registerPlugins();

        $this->api = new Api($this);
    }

    public function registerPlugins()
    {
        $plugins = scandir($this->config['plugins_path']);

        $allowed_plugin_dirs = array_keys($this->_plugins);

        foreach($plugins  as $plugin) {
            $plugin_base_path = $this->config['plugins_path'].'/'.$plugin;

            if (is_dir($plugin_base_path)) {

                $plugin_dirs = scandir($plugin_base_path);

                foreach($plugin_dirs as $plugin_dir) {
                    $plugin_dir_path = $plugin_base_path.'/'.$plugin_dir;

                    if (is_dir($plugin_dir_path) && in_array($plugin_dir,$allowed_plugin_dirs)) {

                        $plugin_files = scandir($plugin_dir_path);

                        foreach($plugin_files as $plugin_file) {
                            $plugin_file_path = $plugin_dir_path.'/'.$plugin_file;

                            if (is_file($plugin_file_path) && substr($plugin_file,-strlen('.php')) == '.php') {

                                $plugin_name = ucfirst(basename($plugin_file,'.php'));
                                $plugin_class_name = 'Plugins\\'.ucfirst($plugin).'\\'.ucfirst($plugin_dir).'\\'.$plugin_name;

                                $this->_plugins[$plugin_dir][$plugin_name] = $plugin_class_name;
                            }
                        }
                    }
                }
            }
        }
    }

    public function getPlugins()
    {
        return $this->_plugins;
    }
}
