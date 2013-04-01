<?php
namespace FluxAPI;

/**
 * A Models field definition
 * @package FluxAPI
 */
class Field
{
    /**
     * @var string Name of the field as stored in the storage
     */
    public $name = NULL;

    /**
     * @var string Datatype of the field
     */
    public $type = NULL;

    /**
     * @var bool Signed or unsigned (used for integers and floats)
     */
    public $unsigned = FALSE;

    /**
     * @var int Maximum length of the fields value
     */
    public $length = NULL;

    /**
     * @var mixed Default field value
     */
    public $default = NULL;

    /**
     * @var bool Flag if field is primary (mostly used for IDs)
     */
    public $primary = FALSE;

    /**
     * @var bool Flag if field value must be unique
     */
    public $unique = FALSE;

    /**
     * @var bool Flag to set field values to automaticly increase
     */
    public $autoIncrement = FALSE;

    /**
     * @var string If the $type is of Field::TYPE_RELATION this must be set to the relation type: Field::HAS_ONE, Field::BELONGS_TO_ONE, Field::HAS_MANY, Field::BELONGS_TO_MANY
     */
    public $relationType = NULL;
    public $relationModel = NULL;

    const TYPE_STRING = 'string';
    const TYPE_BINARY = 'binary';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_LONGSTRING = 'longstring';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';
    const TYPE_RELATION = 'relation';

    const HAS_ONE = 'has_one';
    const HAS_MANY = 'has_many';
    const BELONGS_TO_ONE = 'belongs_to_one';
    const BELONGS_TO_MANY = 'belongs_to_many';

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        foreach($config as $name => $value) {
            if (property_exists($this,$name)) {
                $this->$name = $value;
            }
        }
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
