<?php
require_once __DIR__ . '/FluxApi_Database_TestCase.php';

use \FluxApi\Query;

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

        $query = new Query();
        $query->filter('order', array('title'));

        $nodes = self::$fluxApi->loadNodes($query, 'xml');

        // we need to remove the datetime fields which change everytime
        $nodes = $this->removeDateTimesFromXml($nodes);
        $nodes = $this->removeIdsFromXml($nodes);

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/_files/nodes.xml',$nodes);
    }

    public function testLoadSingleXml()
    {
        $this->migrate();
        $this->createSingleNode();

        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','Node title'));

        $node = self::$fluxApi->loadNode($query,'xml');

        // we need to remove the datetime fields which change everytime
        $node = $this->removeDateTimesFromXml($node);
        $node = $this->removeIdsFromXml($node);

        $this->assertXmlStringEqualsXmlFile(__DIR__ . '/_files/node.xml',$node);
    }

    public function testLoadJson()
    {
        $this->migrate();
        $this->createNodes();

        $query = new Query();
        $query->filter('order', array('title'));

        $nodes = self::$fluxApi->loadNodes($query, 'json');

        // we need to remove the datetime fields which change everytime
        $nodes = $this->removeDateTimesFromJson($nodes);
        $nodes = $this->removeIdsFromJson($nodes);

        $this->assertJsonStringEqualsJsonFile(__DIR__ . '/_files/nodes.json',$nodes);
    }

    public function testLoadSingleJson()
    {
        $this->migrate();
        $this->createSingleNode();

        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','Node title'));

        $node = self::$fluxApi->loadNode($query,'json');

        // we need to remove the datetime fields which change everytime
        $node = $this->removeDateTimesFromJson($node);
        $node = $this->removeIdsFromJson($node);

        $this->assertJsonStringEqualsJsonFile(__DIR__ . '/_files/node.json',$node);
    }

    public function testLoadYaml()
    {
        $this->migrate();
        $this->createNodes();

        $query = new Query();
        $query->filter('order', array('title'));

        $nodes = self::$fluxApi->loadNodes($query,'yaml');

        $nodes = $this->removeDateTimesFromYaml($nodes);
        $nodes = $this->removeIdsFromYaml($nodes);

        $this->assertStringEqualsFile(__DIR__ . '/_files/nodes.yml',$nodes);
    }

    public function testLoadSingleYaml()
    {
        $this->migrate();
        $this->createSingleNode();

        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','Node title'));

        $node = self::$fluxApi->loadNode($query,'yaml');

        $node = $this->removeDateTimesFromYaml($node, 0);
        $node = $this->removeIdsFromYaml($node, 0);

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

        $query = new \FluxAPI\Query();
        $query->filter('equal',array('newField','value 1'));

        $node = self::$fluxApi->loadNode($query);

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

        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','new test model'));

        $instance = self::$fluxApi->loadTestModel($query);

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

        $query = new \FluxAPI\Query();
        $query->filter('equal',array('newField','value 1'));

        $node = self::$fluxApi->loadNode($query);

        // check that node contains the new fields
        $this->assertNotEmpty($node);
        $this->assertEquals($node->newField, 'value 1');
        $this->assertEquals($node->newField2, 2);
    }

    public function testExtendAndReduceNewModel()
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

        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','new test model'));

        $instance = self::$fluxApi->loadTestModel($query);

        $this->assertNotEmpty($instance);
        $this->assertEquals($instance->title,'new test model');
        $this->assertEquals($instance->description,'test description');

        // now reduce it, first by removing a field
        self::$fluxApi->reduceModel($model, array('description'));

        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','new test model'));

        $instance = self::$fluxApi->loadTestModel($query);

        $this->assertNotEmpty($instance);
        $this->assertEquals($instance->title,'new test model');
        $this->assertEmpty($instance->description);

        // now remove it completely
        self::$fluxApi->reduceModel($model);

        $this->assertNull(self::$fluxApi->loadTestModel($query));
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
        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','Node 1'));

        $node = self::$fluxApi->loadNode($query);

        $this->assertNotEmpty($node);

        $this->assertEquals('Node 1',$node->title);
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
        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','Node 5'));
        self::$fluxApi->deleteNode($query);

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
        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','Node 1'));

        $node = self::$fluxApi->loadNode($query);

        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','Node 2'));

        $parent_node = self::$fluxApi->loadNode($query);

        $query = new \FluxAPI\Query();
        $query->filter('limit', array(3, 7))
            ->filter('order', array('title'));
        $child_nodes = self::$fluxApi->loadNodes($query);

        $node->parent = $parent_node;
        $node->children = $child_nodes;

        self::$fluxApi->saveNode($node);

        $query = new \FluxAPI\Query();
        $query->filter('equal', array('title', 'Node 1'));

        $node = self::$fluxApi->loadNode($query);

        $this->assertNotEmpty($node->parent);

        $this->assertEquals($node->parent->title, 'Node 2');

        $this->assertNotEmpty($node->children);
        $this->assertCount(7,$node->children);

        $titles = array('Node 3','Node 4','Node 5','Node 6','Node 7','Node 8','Node 9');

        foreach($node->children as $child_node) {
            $this->assertContains($child_node->title,$titles);
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
        $query = new \FluxAPI\Query();
        $query->filter('equal',array('title','Node 1'));

        $node = self::$fluxApi->loadNode($query);

        $this->assertNotEmpty($node->parent);

        $this->assertNotEmpty($node->children);
        $this->assertCount(7,$node->children);

        // as parent is a belongs-to relation we have to remove it from parents children
        $parent = $node->parent;
        $parent->children = array();

        // to prevent re-adding the node to the parents children we have to remove the parent from the node too
        unset($node->parent);

        // now we remove the children
        $node->children = array();

        // and save both, the parent and the node
        self::$fluxApi->saveNode($parent);
        self::$fluxApi->saveNode($node);

        $node = self::$fluxApi->loadNode($query);

        $this->assertEmpty($node->parent);
        $this->assertCount(0, $node->children);
    }
}
