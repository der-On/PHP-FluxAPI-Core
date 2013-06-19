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
    public $name = null;

    /**
     * @var string Datatype of the field
     */
    public $type = null;

    /**
     * @var bool Signed or unsigned (used for integers and floats)
     */
    public $unsigned = false;

    /**
     * @var int Maximum length of the fields value
     */
    public $length = null;

    /**
     * @var mixed Default field value
     */
    public $default = null;

    /**
     * @var array
     */
    public $validators = array();

    /**
     * @var bool Flag if field is primary (mostly used for IDs)
     */
    public $primary = false;

    /**
     * @var bool Flag if field value must be unique
     */
    public $unique = false;

    /**
     * @var bool Flag to set field values to automaticly increase
     */
    public $autoIncrement = false;

    /**
     * @var string If the $type is of Field::TYPE_RELATION this must be set to the relation type: Field::HAS_ONE, Field::BELONGS_TO_ONE, Field::HAS_MANY, Field::BELONGS_TO_MANY
     */
    public $relationType = null;

    /**
     * Model name of the relation
     * @var string
     */
    public $relationModel = null;

    /**
     * Field name for BELONGS-TO-Relations. This must be the name of the HAS-Relation field in the related model.
     * @var string
     */
    public $relationField = null;

    /**
     * Order in which to load MANY-Relations. If set it is an array where the keys are fields and values can be one of Field::ORDER_ASC or Field::ORDER_DESC
     * @var array | null
     */
    public $relationOrder = null;

    const TYPE_STRING = 'string';
    const TYPE_BINARY = 'binary';
    const TYPE_BYTEARRAY = 'bitearray';
    const TYPE_BOOLEAN = 'boolean';
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

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

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

    /**
     * Adds a validator to this field
     * @param $validator_name
     */
    public function addValidator($validator_name)
    {
        $this->validators[] = $validator_name;
    }

    /**
     * Returns all validators for this field
     * @return array
     */
    public function getValidators()
    {
        return $this->validators;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
