<?php
namespace FluxAPI;

/**
 * A dynamic model definition.
 *
 * This class is used for all dynamically created models.
 * @package FluxAPI
 */
class DynamicModel extends Model
{
    protected $_modelName;

    public function setModelName($name)
    {
        $this->_modelName = $name;
    }
}