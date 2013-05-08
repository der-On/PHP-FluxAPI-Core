<?php
require_once "PHPUnit/Extensions/Database/TestCase.php";

$loader = require __DIR__ . '/../../../autoload.php';

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

        // create application
        $app = new Silex\Application();

        if (self::$config['debug'] == TRUE) {
            $app['debug'] = TRUE;
        }

        self::$fluxApi = new FluxAPI\Api($app,self::$config);
    }

    protected function getConfig()
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../../../../config/testing.json'),TRUE);
        $config['extends_path'] = realpath(__DIR__ . '/../../../../' . $config['extends_path']);
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

    public function removeDateTimesFromXml($xml)
    {
        $xml =  preg_replace('/\<createdAt\>(.*)\<\/createdAt\>/','',$xml);
        $xml =  preg_replace('/\<updatedAt\>(.*)\<\/updatedAt\>/','',$xml);
        $xml =  preg_replace('/\<updatedAt\/\>/','',$xml);
        $xml =  preg_replace('/\<createdAt\/\>/','',$xml);
        return $xml;
    }

    public function removeIdsFromXml($xml)
    {
        $xml =  preg_replace('/\<id\>(.*)\<\/id\>/','',$xml);
        return $xml;
    }

    public function removeDateTimesFromJson($json)
    {
        $json =  preg_replace('/"createdAt":"\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}",/','',$json);
        $json =  preg_replace('/"updatedAt":"\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}",/','',$json);
        return $json;
    }

    public function removeIdsFromJson($json)
    {
        $json =  preg_replace('/"id":"(([0-9]|\-|[a-z])*)",/','',$json);
        return $json;
    }

    public function removeDateTimesFromYaml($yaml, $indent = 4)
    {
        $yaml =  preg_replace('/\s{'.$indent.'}createdAt: \'\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}\'(\n|\t)/','',$yaml);
        $yaml =  preg_replace('/\s{'.$indent.'}updatedAt: \'\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}\'(\n|\t)/','',$yaml);
        return $yaml;
    }

    public function removeIdsFromYaml($yaml, $indent = 4)
    {
        $yaml =  preg_replace('/\s{'.$indent.'}id: (.*)(\n|\t)/','',$yaml);
        return $yaml;
    }
}