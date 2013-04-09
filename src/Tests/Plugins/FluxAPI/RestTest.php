<?php
require_once __DIR__ . '/../../FluxAPI/FluxApi_Database_TestCase.php';

use Symfony\Component\HttpKernel\Client;

class RestTest extends FluxApi_Database_TestCase
{
    public function testCrudNodes()
    {
        $this->migrate();
        $this->createNodes();
    }

    public function createNodes()
    {
        $client = $this->createClient();
        $crawler = $client->request('POST','/node');
    }

    public function createClient(array $server = array())
    {
        return new Client(self::$fluxApi->_app, $server);
    }
}