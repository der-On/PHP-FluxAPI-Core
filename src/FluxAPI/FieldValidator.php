<?php
namespace FluxAPI;


abstract class FieldValidator implements FieldValidatorInterface
{
    protected $_api;

    public function __construct(Api $api)
    {
        $this->_api = $api;
    }

    /**
     * @param mixed $value
     * @param Field $field
     * @param Model $model
     * @param [array $options]
     * @return bool - true if field is valid, else false
     */
    public function validate($value, Field $field, Model $model, array $options = array())
    {
        return TRUE;
    }
}