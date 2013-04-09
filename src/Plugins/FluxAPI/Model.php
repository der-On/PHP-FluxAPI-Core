<?php

namespace Plugins\FluxAPI;

use \FluxAPI\Field;

class Model extends \FluxAPI\Model
{
    public function defineFields()
    {
        parent::defineFields();

        $this->addField(new Field(array(
            'type' => Field::TYPE_DATETIME,
            'name' => 'updatedAt'
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_DATETIME,
            'name' => 'createdAt'
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_BOOLEAN,
            'name' => 'active',
            'default' => FALSE,
        )));
    }
}