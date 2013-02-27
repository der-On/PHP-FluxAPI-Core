<?php
namespace FluxAPI;

class Field
{
    public $name = NULL;
    public $type = NULL;
    public $unsigned = FALSE;
    public $length = NULL;
    public $default = NULL;
    public $primary = FALSE;
    public $unique = FALSE;
    public $autoIncrement = FALSE;
    public $relationType = NULL;
    public $relationModel = NULL;

    const TYPE_STRING = 'string';
    const TYPE_BINARY = 'binary';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_LONGSTRING = 'longstring';
    const TYPE_DATE = 'date';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';
    const TYPE_RELATION = 'relation';

    const HAS_ONE = 'has_one';
    const HAS_MANY = 'has_many';
    const BELONGS_TO_ONE = 'belongs_to_one';
    const BELONGS_TO_MANY = 'belongs_to_many';

    public function __construct(array $config = array())
    {
        foreach($config as $name => $value) {
            if (property_exists($this,$name)) {
                $this->$name = $value;
            }
        }
    }
}
