<?php
namespace Plugins\Core\Model;

use \FluxAPI\Field;

class Node extends \FluxAPI\Model
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
        )));
    }
}
