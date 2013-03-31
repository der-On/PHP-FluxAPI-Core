<?php
require_once __DIR__ . '/../../../FluxAPI/FluxApi_Database_TestCase.php';

use \FluxAPI\Field;

class TestObject
{
    public $property = 'value';
}

class MySqlTest extends FluxApi_Database_TestCase
{
    public function testSerialization()
    {
        $model = 'TestModel';

        $fields = array(
            'int' => array(
                'name' => 'int',
                'type' => Field::TYPE_INTEGER,
            ),
            'date' => array(
                'name' => 'date',
                'type' => Field::TYPE_DATE,
            ),
            'timestamp' => array(
                'name' => 'timestamp',
                'type' => Field::TYPE_TIMESTAMP,
            ),
            'array' => array(
                'name' => 'array',
                'type' => Field::TYPE_ARRAY,
            ),
            'object' => array(
                'name' => 'object',
                'type' => Field::TYPE_OBJECT,
            ),
        );

        $now = time();
        $date = new DateTime();
        $date->setTimestamp($now);

        $data = array(
            'int' => 10,
            'date' => $date,
            'timestamp' => $now,
            'array' => array('string',20,array('another string',30,new TestObject())),
            'object' => new TestObject()
        );

        self::$fluxApi->extendModel($model, $fields);

        $model = self::$fluxApi->createTestModel($data);
        var_dump($model);
        $this->assertNotEmpty($model);
        $this->assertEquals($model->toArray(),$data);
    }
}