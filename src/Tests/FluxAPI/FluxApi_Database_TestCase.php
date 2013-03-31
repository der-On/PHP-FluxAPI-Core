<?php
require_once "PHPUnit/Extensions/Database/TestCase.php";

abstract class FluxApi_Database_TestCase extends PHPUnit_Extensions_Database_TestCase
{
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    protected static $fluxApi;
    protected static $config;

    protected function setUp()
    {
        self::$config = $this->getConfig();

        // remove all extends
        foreach(scandir(__DIR__ .'/_extends/') as $file) {
            $file_path = __DIR__ .'/_extends/'.$file;
            if (!in_array($file,array('.','..')) && is_dir($file_path)) {
                exec('rm -r '.$file_path);
            }
        }

        // clear the database
        $conn = $this->getConnection();
        self::$pdo->exec('DROP TABLE IF EXISTS node');
        self::$pdo->exec('DROP TABLE IF EXISTS node_rel');

        $loader = require __DIR__ . '/../../../vendor/autoload.php';

        // create application
        $app = new Silex\Application();

        if (self::$config['debug'] == TRUE) {
            $app['debug'] = TRUE;
        }

        self::$fluxApi = new FluxAPI\Api($app,self::$config);
    }

    protected function getConfig()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../../../config/testing.json'),TRUE);
        $config['extends_path'] = realpath(__DIR__ . '/../../../' . $config['extends_path']);
        return $config;
    }

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    final public function getConnection()
    {
        $config = self::$config;

        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO('mysql:dbname='.$config['storage.options']['database'].';host='.$config['storage.options']['host'],$config['storage.options']['user'],$config['storage.options']['password']);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo,':'.$config['storage.options']['host'].':');
        }


        return $this->conn;
    }

    public function getDataSet()
    {
        return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }

    public function migrate()
    {
        self::$fluxApi->migrate();
    }
}