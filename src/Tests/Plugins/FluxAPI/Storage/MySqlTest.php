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
                'name' => 'int_field',
                'type' => Field::TYPE_INTEGER,
            ),
            'date' => array(
                'name' => 'date_field',
                'type' => Field::TYPE_DATE,
            ),
            'datetime' => array(
                'name' => 'datetime_field',
                'type' => Field::TYPE_DATETIME,
            ),
            'timestamp' => array(
                'name' => 'timestamp_field',
                'type' => Field::TYPE_TIMESTAMP,
            ),
            'array' => array(
                'name' => 'array_field',
                'type' => Field::TYPE_ARRAY,
            ),
            'object' => array(
                'name' => 'object_field',
                'type' => Field::TYPE_OBJECT,
            ),
        );

        $now = time();
        $date = new DateTime();
        $date->setTimestamp($now);

        $data = array(
            'int_field' => 10,
            'date_field' => $date,
            'datetime_field' => $date,
            'timestamp_field' => $now,
            'array_field' => array('string',20,array('another string',30,new TestObject())),
            'object_field' => new TestObject()
        );

        self::$fluxApi->extendModel($model, $fields);

        $model = self::$fluxApi->createTestModel($data);

        // check that data is in the model
        $this->assertNotEmpty($model);

        foreach($data as $name => $value) {
            $this->assertEquals($model->$name,$data[$name]);
        }

        // store and reload the data from database
        self::$fluxApi->saveTestModel($model);

        $model = self::$fluxApi->loadTestModel();

        // check that data is in the model
        $this->assertNotEmpty($model);

        $this->assertEquals($model->int_field,$data['int_field']);
        $this->assertEquals($model->date_field->format('Y-m-d'),$data['date_field']->format('Y-m-d'));
        $this->assertEquals($model->datetime_field->format('Y-m-d H:i:s'),$data['date_field']->format('Y-m-d H:i:s'));
        $this->assertEquals($model->timestamp_field,$data['timestamp_field']);
        $this->assertEquals($model->array_field,$data['array_field']);
        $this->assertEquals($model->object_field,$data['object_field']);
    }
}