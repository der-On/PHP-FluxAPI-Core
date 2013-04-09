<?php

namespace Plugins\FluxAPI\Model;

use \FluxAPI\Field;

class User extends \Plugins\FluxAPI\Model
{
    public function defineFields()
    {
        parent::defineFields();

        $this->addField(new Field(array(
            'type' => Field::TYPE_STRING,
            'name' => 'username'
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_STRING,
            'name' => 'email'
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_STRING,
            'name' => 'password'
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_STRING,
            'name' => 'token'
        )))
        ->addField(new Field(array(
            'type' => Field::TYPE_ARRAY,
            'name' => 'permissions'
        )))
        ->addField(new Field(array(
            'name' => 'usergroups',
            'type' => Field::TYPE_RELATION,
            'relationType' => Field::BELONGS_TO_MANY,
            'relationModel' => 'UserGroup'
        )));
    }
}