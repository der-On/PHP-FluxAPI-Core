<?php
require_once __DIR__ . '/FluxApi_Database_TestCase.php';

class ModelTest extends FluxApi_Database_TestCase
{
    public function getNodeData()
    {
        return array(
            'id' => '1',
            'title' => 'Node title',
            'body' => "Node body\non multiple lines",
        );
    }

    public function testToArray()
    {
        $data = $this->getNodeData();
        $node = new \Plugins\Core\Model\Node($data);

        $array = $node->toArray();
        $this->assertEquals($array,$data);
    }

    public function textToString()
    {
        $data = $this->getNodeData();
        $node = new \Plugins\Core\Model\Node($data);

        $string = $node->toString();
        $this->assertJsonStringEqualsJsonString(json_encode($data,4), $string);
    }

    public function testToJson()
    {
        $data = $this->getNodeData();
        $node = new \Plugins\Core\Model\Node($data);

        $json = $node->toJson();
        $this->assertJsonStringEqualsJsonString(json_encode($data), $json);
    }

    public function testToYaml()
    {
        $data = $this->getNodeData();
        $node = new \Plugins\Core\Model\Node($data);

        $yaml = trim($node->toYaml());

        $yaml_file = trim(file_get_contents(__DIR__ . '/_files/node.yml'));

        $this->assertEquals($yaml_file,$yaml);
    }

    public function testToXml()
    {
        $this->migrate();

        $data = $this->getNodeData();
        $node = new \Plugins\Core\Model\Node($data);

        $xml = $node->toXml();
        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/_files/node.xml', $xml);
    }

    public function testFromArray()
    {
        $data = $this->getNodeData();

        $node = \Plugins\Core\Model\Node::fromArray($data);

        $this->assertNotNull($node);

        foreach($data as $key => $value) {
            $this->assertEquals($value,$node->$key);
        }
    }

    public function testFromJson()
    {
        $data = $this->getNodeData();
        $json = json_encode($data,4);

        $node = \Plugins\Core\Model\Node::fromJson($json);

        $this->assertNotNull($node);

        foreach($data as $key => $value) {
            $this->assertEquals($value,$node->$key);
        }
    }

    public function testFromXml()
    {
        $data = $this->getNodeData();
        $xml = file_get_contents(__DIR__ . '/_files/node.xml');

        $node = \Plugins\Core\Model\Node::fromXml($xml);

        $this->assertNotNull($node);

        foreach($data as $key => $value) {
            $this->assertEquals($value,$node->$key);
        }
    }

    public function testFromYaml()
    {
        $data = $this->getNodeData();
        $yaml = file_get_contents(__DIR__ . '/_files/node.yml');

        $node = \Plugins\Core\Model\Node::fromYaml($yaml);

        foreach($data as $key => $value) {
            $this->assertEquals($value,$node->$key);
        }
    }
}
