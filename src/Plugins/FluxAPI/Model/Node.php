<?php
namespace Plugins\FluxAPI\Model;

use \FluxAPI\Field;

class Node extends \Plugins\FluxAPI\Model
{
    public function defineFields()
    {
        parent::defineFields();

        $this->addField(new Field(array(
            'name' => 'body',
            'type' => Field::TYPE_LONGSTRING
        )))
        ->addField(new Field(array(
            'name' => 'title',
            'type' => Field::TYPE_STRING
        )))
        ->addField(new Field(array(
            'name' => 'parent',
            'type' => Field::TYPE_RELATION,
            'relationType' => Field::BELONGS_TO_ONE,
            'relationModel' => 'Node'
        )))
        ->addField(new Field(array(
            'name' => 'children',
            'type' => Field::TYPE_RELATION,
            'relationType' => Field::HAS_MANY,
            'relationModel' => 'Node'
        )))
        ->addField(new Field(array(
            'name' => 'author',
            'type' => Field::TYPE_RELATION,
            'relationType' => Field::HAS_ONE,
            'relationModel' => 'User',
        )));
    }
}
