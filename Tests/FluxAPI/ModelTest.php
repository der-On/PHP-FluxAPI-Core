<?php
require_once __DIR__ . '/FluxApi_Database_TestCase.php';

class ModelTest extends FluxApi_Database_TestCase
{
    public function getNodeData()
    {
        return array(
            'id' => 1,
            'active' => FALSE,
            'title' => 'Node title',
            'body' => "Node body\non multiple lines",
            'updatedAt' => NULL,
            'createdAt' => NULL
        );
    }

    public function testToArray()
    {
        $data = $this->getNodeData();
        $node = new \Plugins\FluxAPI\Core\Model\Node(self::$fluxApi, $data);

        $array = $node->toArray();
        $this->assertEquals($array,$data);
    }

    public function testToString()
    {
        $data = $this->getNodeData();
        $node = new \Plugins\FluxAPI\Core\Model\Node(self::$fluxApi, $data);

        $string = $node->toString();
        $this->assertJsonStringEqualsJsonString(json_encode($data,4), $string);
    }
}
