<?php
require_once __DIR__ . '/../../FluxAPI/FluxApi_Database_TestCase.php';

use Symfony\Component\HttpKernel\Client;

class RestTest extends FluxApi_Database_TestCase
{
    public function setUp()
    {
        parent::setUp();

        self::$fluxApi->app['exception_handler']->disable();
    }

    public function testCreateNodeArray()
    {
        $this->migrate();

        $client = $this->createClient();

        $data = array('title'=>'Node','body'=>'Body for Node','active'=>false,'id' => 1);
        $data_json = json_encode($data);

        // first save the node
        $client->request('POST','/node',$data);

        $this->assertTrue($client->getResponse()->isOk());

        // now load it
        $client->request('GET','/node/1');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString($data_json, $response_data);

        // now load all nodes at once
        $data_all_json = json_encode(array($data));
        $client->request('GET','/nodes');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString($data_all_json, $response_data);
    }

    public function testCreateNodeJson()
    {
        $this->migrate();

        $client = $this->createClient();

        $data_json = file_get_contents(__DIR__ . '/../../FluxAPI/_files/node.json');

        // first save the node
        $client->request('POST','/node.json',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'application/json',
            ),
            $data_json);

        $this->assertTrue($client->getResponse()->isOk());

        // now load it
        $client->request('GET','/node/1.json');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString($data_json, $response_data);

        // now load all nodes at once
        $data_all_json = json_encode(array(json_decode($data_json,TRUE)));
        $client->request('GET','/nodes.json');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString($data_all_json, $response_data);
    }

    public function testCreateNodeXml()
    {
        $this->migrate();

        $client = $this->createClient();

        $data_xml = file_get_contents(__DIR__ . '/../../FluxAPI/_files/node.xml');

        // first save the node
        $client->request('POST','/node.xml',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'text/xml',
            ),
            $data_xml);

        $this->assertTrue($client->getResponse()->isOk());

        // now load it
        $client->request('GET','/node/1.xml');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromXml($client->getResponse()->getContent());

        $this->assertXmlStringEqualsXmlString($data_xml, $response_data);

        $data_all_xml = str_replace('<?xml version="1.0"?>','<?xml version="1.0"?><Nodes>',$data_xml).'</Nodes>';

        // now load all nodes at once
        $client->request('GET','/nodes.xml');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromXml($client->getResponse()->getContent());
        $this->assertXmlStringEqualsXmlString($data_all_xml, $response_data);
    }

    public function testCreateNodeYaml()
    {
        $this->migrate();

        $client = $this->createClient();

        $data_yaml = file_get_contents(__DIR__ . '/../../FluxAPI/_files/node.yml');

        // first save the node
        $client->request('POST','/node.yaml',
            array(),
            array(),
            array(
                'CONTENT_TYPE' => 'text/yaml',
            ),
            $data_yaml);

        $this->assertTrue($client->getResponse()->isOk());

        // now load it
        $client->request('GET','/node/1.yaml');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromYaml($client->getResponse()->getContent(),0);

        $this->assertEquals($data_yaml, $response_data);

        $data_yaml_lines = explode("\n",$data_yaml);

        $data_all_yaml = rtrim("-\n    ".str_replace("","",implode("\n    ",$data_yaml_lines)))."\n";

        // now load all nodes at once
        $client->request('GET','/nodes.yaml');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromYaml($client->getResponse()->getContent());
        $this->assertEquals($data_all_yaml, $response_data);
    }

    public function testDeleteNode()
    {
        $this->migrate();

        $client = $this->createClient();

        $data = array('title'=>'Node','body'=>'Body for Node','active'=>false,'id' => 1);
        $data_json = json_encode($data);

        // first save the node
        $client->request('POST','/node',$data);

        $this->assertTrue($client->getResponse()->isOk());

        // now load it
        $client->request('GET','/node/1');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString($data_json, $response_data);

        // now delete it
        $client->request('DELETE','/node/1');
        $this->assertTrue($client->getResponse()->isOk());

        // now load all nodes, we should get an empty array back
        $client->request('GET','/nodes');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonString(json_encode(array()), $response_data);
    }

    public function createClient(array $server = array())
    {
        return new Client(self::$fluxApi->app, $server);
    }
}