<?php
require_once "PHPUnit/Framework/TestCase.php";

$loader = require __DIR__ . '/../../../vendor/autoload.php';

abstract class FluxApi_TestCase extends PHPUnit_Framework_TestCase
{
    protected static $fluxApi;
    protected static $config;

    protected function getApp($config)
    {
        // create application
        $app = new Silex\Application();

        if ($config['debug'] == TRUE) {
            $app['debug'] = TRUE;
        }

        return $app;
    }

    protected function getFluxApi($config)
    {
        if (self::$fluxApi) {
            return self::$fluxApi;
        } else {
            // create application
            $app = $this->getApp($config);

            self::$fluxApi = new FluxAPI\Api($app, $config);

            return self::$fluxApi;
        }
    }

    protected function deleteFluxApi()
    {
        self::$fluxApi = NULL;
    }

    protected function setUp()
    {
        // remove all extends
        foreach(scandir(__DIR__ .'/_extends/') as $file) {
            $file_path = __DIR__ .'/_extends/'.$file;
            if (!in_array($file,array('.','..')) && is_dir($file_path)) {
                exec('rm -r '.$file_path);
            }
        }
    }

    protected function tearDown()
    {
        $this->deleteFluxApi();
    }

    protected function getConfig()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../../../config/testing.json'),TRUE);
        $config['extends_path'] = realpath(__DIR__ . '/../../../' . $config['extends_path']);
        return $config;
    }
}