<?php
require_once __DIR__ . '/FluxApi_Database_TestCase.php';

class ApiTest extends FluxApi_Database_TestCase
{
    public function testCrudNodes()
    {
        $this->migrate();
        $this->createNodes();
        $this->loadAllNodes();
        $this->loadNodesWithFilters();
        $this->loadSingleNode();
        $this->updateNodes();
        $this->deleteNodes();
        $this->deleteSingleNode();
        $this->deleteAllNodes();
    }

    public function testLoadXml()
    {
        $this->migrate();
        $this->createNodes();
        $nodes = self::$fluxApi->loadNodes(NULL,'xml');

        // we need to remove the datetime fields which change everytime
        $nodes = $this->removeDateTimesFromXml($nodes);

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/_files/nodes.xml',$nodes);
    }

    public function testLoadSingleXml()
    {
        $this->migrate();
        $this->createSingleNode();

        $node = self::$fluxApi->loadNode('1','xml');

        // we need to remove the datetime fields which change everytime
        $node = $this->removeDateTimesFromXml($node);

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/_files/node.xml',$node);
    }

    public function testLoadJson()
    {
        $this->migrate();
        $this->createNodes();

        $nodes = self::$fluxApi->loadNodes(NULL,'json');

        // we need to remove the datetime fields which change everytime
        $nodes = $this->removeDateTimesFromJson($nodes);

        $this->assertJsonStringEqualsJsonFile(__DIR__ . '/_files/nodes.json',$nodes);
    }

    public function testLoadSingleJson()
    {
        $this->migrate();
        $this->createSingleNode();

        $node = self::$fluxApi->loadNode('1','json');

        // we need to remove the datetime fields which change everytime
        $node = $this->removeDateTimesFromJson($node);

        $this->assertJsonStringEqualsJsonFile(__DIR__ . '/_files/node.json',$node);
    }

    public function testLoadYaml()
    {
        $this->migrate();
        $this->createNodes();

        $nodes = self::$fluxApi->loadNodes(NULL,'yaml');

        $nodes = $this->removeDateTimesFromYaml($nodes);

        $this->assertStringEqualsFile(__DIR__ . '/_files/nodes.yml',$nodes);
    }

    public function testLoadSingleYaml()
    {
        $this->migrate();
        $this->createSingleNode();

        $node = self::$fluxApi->loadNode('1','yaml');

        $node = $this->removeDateTimesFromYaml($node,0);

        $this->assertStringEqualsFile(__DIR__ . '/_files/node.yml',$node);
    }

    public function testRelations()
    {
        $this->migrate();
        $this->createNodes();
        $this->addRelatedNodes();
        $this->addNewRelatedNodes();
        $this->removeRelatedNodes();
    }

    public function testExtendExistingModel()
    {
        $model = 'Node';

        $fields = array(
          array(
              'name' => 'newField',
              'type' => \FluxAPI\Field::TYPE_STRING,
              'length' => 64
          ),
          array(
              'name' => 'newField2',
              'type' => \FluxAPI\Field::TYPE_INTEGER,
              'length' => 10
          )
        );

        self::$fluxApi->extendNode($fields);

        $node = self::$fluxApi->createNode(array(
            'newField' => 'value 1',
            'newField2' => 2
        ));

        // check that node contains the new fields
        $this->assertNotEmpty($node);
        $this->assertEquals($node->newField, 'value 1');
        $this->assertEquals($node->newField2, 2);

        // now make the node persistant and reload it form the storage
        self::$fluxApi->saveNode($node);

        $node = self::$fluxApi->loadNode('1');

        // check that node contains the new fields
        $this->assertNotEmpty($node);
        $this->assertEquals($node->newField, 'value 1');
        $this->assertEquals($node->newField2, 2);
    }

    public function testExtendNewModel()
    {
        $model = 'TestModel';

        $fields = array(
          new \FluxAPI\Field(array(
              'name' => 'title',
              'type' => \FluxAPI\Field::TYPE_STRING,
              'length' => 512
          )),
          new \FluxAPI\Field(array(
            'name' => 'description',
            'type' => \FluxAPI\Field::TYPE_LONGSTRING,
          ))
        );

        self::$fluxApi->extendModel($model,$fields);

        $instance = self::$fluxApi->createTestModel(array('title'=>'new test model','description'=>'test description'));

        $this->assertNotEmpty($instance);
        $this->assertEquals($instance->title,'new test model');
        $this->assertEquals($instance->description,'test description');

        self::$fluxApi->saveTestModel($instance);

        $instance = self::$fluxApi->loadTestModel('1');

        $this->assertNotEmpty($instance);
        $this->assertEquals($instance->title,'new test model');
        $this->assertEquals($instance->description,'test description');
    }

    public function testExtendExistingModelMultipleTimes()
    {
        $model = 'Node';

        $fields = array(
            array(
                'name' => 'newField',
                'type' => \FluxAPI\Field::TYPE_STRING,
                'length' => 64
            ),
        );

        self::$fluxApi->extendNode($fields);

        $fields = array(
            array(
                'name' => 'newField2',
                'type' => \FluxAPI\Field::TYPE_INTEGER,
                'length' => 10
            )
        );

        self::$fluxApi->extendNode($fields);

        $node = self::$fluxApi->createNode(array(
            'newField' => 'value 1',
            'newField2' => 2
        ));

        // check that node contains the new fields
        $this->assertNotEmpty($node);
        $this->assertEquals($node->newField, 'value 1');
        $this->assertEquals($node->newField2, 2);

        // now make the node persistant and reload it form the storage
        self::$fluxApi->saveNode($node);

        $node = self::$fluxApi->loadNode('1');

        // check that node contains the new fields
        $this->assertNotEmpty($node);
        $this->assertEquals($node->newField, 'value 1');
        $this->assertEquals($node->newField2, 2);
    }

    public function testExtendAndRecudeNewModel()
    {
        $model = 'TestModel';

        $fields = array(
            new \FluxAPI\Field(array(
                'name' => 'title',
                'type' => \FluxAPI\Field::TYPE_STRING,
                'length' => 512
            )),
            new \FluxAPI\Field(array(
                'name' => 'description',
                'type' => \FluxAPI\Field::TYPE_LONGSTRING,
            ))
        );

        self::$fluxApi->extendModel($model,$fields);

        $instance = self::$fluxApi->createTestModel(array('title'=>'new test model','description'=>'test description'));

        $this->assertNotEmpty($instance);
        $this->assertEquals($instance->title,'new test model');
        $this->assertEquals($instance->description,'test description');

        self::$fluxApi->saveTestModel($instance);

        $instance = self::$fluxApi->loadTestModel('1');

        $this->assertNotEmpty($instance);
        $this->assertEquals($instance->title,'new test model');
        $this->assertEquals($instance->description,'test description');

        // now reduce it, first by removing a field
        self::$fluxApi->reduceModel($model, array('description'));

        $instance = self::$fluxApi->loadTestModel('1');

        $this->assertNotEmpty($instance);
        $this->assertEquals($instance->title,'new test model');
        $this->assertEmpty($instance->description);

        // now remove it completely
        self::$fluxApi->reduceModel($model);

        $this->assertNull(self::$fluxApi->loadTestModel('1'));
    }

    public function createSingleNode()
    {
        $node = self::$fluxApi->createNode(array('title'=>'Node title','body'=>"Node body\non multiple lines"));

        $this->assertNotEmpty($node);
        $this->assertEquals($node->title,'Node title');
        $this->assertEquals($node->body,"Node body\non multiple lines");

        self::$fluxApi->saveNode($node);
    }

    public function createNodes()
    {
        for($i = 1; $i <= 10; $i++) {
            $node = self::$fluxApi->createNode(array('title'=>'Node '.$i,'body'=>'Body for Node '.$i));

            $this->assertNotEmpty($node);
            $this->assertEquals($node->title,'Node '.$i);
            $this->assertEquals($node->body,'Body for Node '.$i);

            self::$fluxApi->saveNode($node);
        }
    }

    public function loadAllNodes()
    {
        $nodes = self::$fluxApi->loadNodes();

        $this->assertCount(10,$nodes);
    }

    public function loadNodesWithFilters()
    {
        $query = new \FluxAPI\Query();
        $query
            ->filter('in',array('title',array('Node 1','Node 2')))
            ->filter('order',array('title'));
        $nodes = self::$fluxApi->loadNodes($query);
        $this->assertCount(2,$nodes);

        for($i = 1; $i <= 2; $i++) {
            $node = $nodes[$i-1];
            $this->assertNotEmpty($node);
            $this->assertEquals($node->title,'Node '.$i);
        }
    }

    public function loadSingleNode()
    {
        $node = self::$fluxApi->loadNode('1');

        $this->assertNotEmpty($node);

        $this->assertEquals($node->title,'Node 1');
    }

    public function updateNodes()
    {
        $query = new \FluxAPI\Query();
        $query->filter('in',array('title',array('Node 1','Node 2')));
        self::$fluxApi->updateNodes($query,array('body' => 'Updated'));

        $nodes = self::$fluxApi->loadNodes($query);

        $this->assertNotEmpty($nodes);
        $this->assertCount(2,$nodes);

        foreach($nodes as $node) {
            $this->assertEquals($node->body,'Updated');
        }
    }

    public function deleteNodes()
    {
        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','Node 1'));

        self::$fluxApi->deleteNodes($query);

        $nodes = self::$fluxApi->loadNodes();

        $this->assertNotEmpty($nodes);
        $this->assertCount(9,$nodes);

    }

    public function deleteSingleNode()
    {
        self::$fluxApi->deleteNode('5');

        $nodes = self::$fluxApi->loadNodes();

        $this->assertNotEmpty($nodes);
        $this->assertCount(8,$nodes);
    }

    public function deleteAllNodes()
    {
        self::$fluxApi->deleteNodes();

        $nodes = self::$fluxApi->loadNodes();

        $this->assertCount(0,$nodes);
    }

    public function addRelatedNodes()
    {
        $node = self::$fluxApi->loadNode('1');
        $parent_node = self::$fluxApi->loadNode('2');

        $query = new \FluxAPI\Query();
        $query->filter('range',array('id',3,10));
        $child_nodes = self::$fluxApi->loadNodes($query);

        $node->parent = $parent_node;
        $node->children = $child_nodes;

        self::$fluxApi->saveNode($node);

        $node = self::$fluxApi->loadNode('1');

        $this->assertNotEmpty($node->parent);
        $this->assertEquals($node->parent->id,2);

        $this->assertNotEmpty($node->children);
        $this->assertCount(8,$node->children);

        $ids = array(3,4,5,6,7,8,9,10);

        foreach($node->children as $child_node) {
            $this->assertContains($child_node->id,$ids);
        }
    }

    public function addNewRelatedNodes()
    {
        $node = self::$fluxApi->createNode(array('title'=>'new Node'));

        $node->parent = self::$fluxApi->createNode(array('title'=>'new parent Node'));

        $node->children = array(
            self::$fluxApi->createNode(array('title'=>'new child Node 1')),
            self::$fluxApi->createNode(array('title'=>'new child Node 2')),
        );

        self::$fluxApi->saveNode($node);

        // reload the node
        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','new Node'));
        $node = self::$fluxApi->loadNode($query);

        $this->assertNotEmpty($node->parent);
        $this->assertEquals($node->parent->title,'new parent Node');

        $this->assertNotEmpty($node->children);
        $this->assertCount(2,$node->children);
        $this->assertEquals($node->children[0]->title,'new child Node 1');
        $this->assertEquals($node->children[1]->title,'new child Node 2');
    }

    public function removeRelatedNodes()
    {
        $node = self::$fluxApi->loadNode('1');

        $this->assertNotEmpty($node->parent);

        $this->assertNotEmpty($node->children);
        $this->assertCount(8,$node->children);

        unset($node->parent);

        $node->children = array();

        self::$fluxApi->saveNode($node);

        $node = self::$fluxApi->loadNode('1');

        $this->assertEmpty($node->parent);
        $this->assertEmpty($node->children);
    }
}
