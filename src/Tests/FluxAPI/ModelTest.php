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

    public function testToString()
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
}
