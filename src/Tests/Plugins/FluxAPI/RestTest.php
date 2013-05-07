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

        $data = array('title'=>'Node','body'=>'Body for Node','active'=>false);
        $data_json = json_encode($data);

        // first save the node
        $client->request('POST','/node',$data);

        $this->assertTrue($client->getResponse()->isOk());

        // now load it
        $client->request('GET','/node?title=Node');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromJson($response_data);
        $this->assertJsonStringEqualsJsonString($data_json, $response_data);

        // now load all nodes at once
        $data_all_json = json_encode(array($data));
        $client->request('GET','/nodes');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromJson($response_data);
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
        $client->request('GET','/node.json?title=Node%20title');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromJson($response_data);
        $this->assertJsonStringEqualsJsonString($data_json, $response_data);

        // now load all nodes at once
        $data_all_json = json_encode(array(json_decode($data_json,TRUE)));
        $client->request('GET','/nodes.json');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromJson($response_data);
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
        $client->request('GET','/node.xml?title=Node%20title');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromXml($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromXml($response_data);
        $this->assertXmlStringEqualsXmlString($data_xml, $response_data);

        $data_all_xml = str_replace('<?xml version="1.0"?>','<?xml version="1.0"?><Nodes>',$data_xml).'</Nodes>';

        // now load all nodes at once
        $client->request('GET','/nodes.xml');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromXml($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromXml($response_data);
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
        $client->request('GET','/node.yaml?title=Node%20title');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromYaml($client->getResponse()->getContent(),0);
        $response_data = $this->removeIdsFromYaml($response_data, 0);
        $this->assertEquals($data_yaml, $response_data);

        $data_yaml_lines = explode("\n",$data_yaml);

        $data_all_yaml = rtrim("-\n    ".str_replace("","",implode("\n    ",$data_yaml_lines)))."\n";

        // now load all nodes at once
        $client->request('GET','/nodes.yaml');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromYaml($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromYaml($response_data, 4);
        $this->assertEquals($data_all_yaml, $response_data);
    }

    public function testDeleteNode()
    {
        $this->migrate();

        $client = $this->createClient();

        $data = array('title'=>'Node','body'=>'Body for Node','active'=>false);
        $data_json = json_encode($data);

        // first save the node
        $client->request('POST','/node',$data);

        $this->assertTrue($client->getResponse()->isOk());

        // now load it
        $client->request('GET','/node?title=Node');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromJson($response_data);
        $this->assertJsonStringEqualsJsonString($data_json, $response_data);

        // now delete it
        $client->request('DELETE','/node?title=Node');
        $this->assertTrue($client->getResponse()->isOk());

        // now load all nodes, we should get an empty array back
        $client->request('GET','/nodes');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromJson($response_data);
        $this->assertJsonStringEqualsJsonString(json_encode(array()), $response_data);
    }

    public function testUpdateNode()
    {
        $this->migrate();

        $client = $this->createClient();

        $data = array('title'=>'Node','body'=>'Body for Node','active'=>false);

        // first save the node
        $client->request('POST','/node',$data);

        $this->assertTrue($client->getResponse()->isOk());

        // now update
        $data['title'] = 'Node updated';
        $data_json = json_encode($data);

        $client->request('PUT','/node?title=Node',$data);

        $this->assertTrue($client->getResponse()->isOk());

        // now load it
        $client->request('GET','/node?title=Node%20updated');

        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromJson($response_data);
        $this->assertJsonStringEqualsJsonString($data_json, $response_data);
    }

    /*
    public function testFilters()
    {
        $this->migrate();

        $client = $this->createClient();
        $data_all = array();

        // first create a bunch of nodes
        for($i = 1; $i <= 10; $i++) {
            $data = array('title'=>'Node '.$i,'body'=>'Body for Node '.$i, 'active'=>false);
            $data_all[] = $data;
            $node = self::$fluxApi->createNode($data);

            self::$fluxApi->saveNode($node);
        }

        // now load them with various filters
        $client->request('GET','/node?title=Node%202'); // simple title filter shortcut

        $this->assertTrue($client->getResponse()->isOk());
        $data_json = json_encode($data_all[1]);
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromJson($response_data);
        $this->assertJsonStringEqualsJsonString($data_json,$response_data);

        $client->request('GET','/nodes?@gte=title,Node%202&@lte=title,Node%205&@order=title,DESC'); // get all with an title from 2 to 4 order by id descending
        $data_json = json_encode(array($data_all[4],$data_all[3],$data_all[2],$data_all[1]));
        $this->assertTrue($client->getResponse()->isOk());
        $response_data = $this->removeDateTimesFromJson($client->getResponse()->getContent());
        $response_data = $this->removeIdsFromJson($response_data);
        $this->assertJsonStringEqualsJsonString($data_json,$response_data);
    }
    */

    public function createClient(array $server = array())
    {
        return new Client(self::$fluxApi->app, $server);
    }
}