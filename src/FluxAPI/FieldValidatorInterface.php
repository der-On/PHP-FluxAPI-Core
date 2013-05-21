<?php
namespace FluxAPI;


interface FieldValidatorInterface
{
    /**
     * @param mixed $value
     * @param Field $field
     * @param Model $model
     * @param [array $options]
     * @return bool - true if field is valid, else false
     */
    public function validate($value, Field $field, Model $model, array $options = array());
}