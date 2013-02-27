<?php
namespace Plugins\Core\Model;

use \FluxAPI\Field;

class Project extends \FluxAPI\Model
{
    public function defineFields()
    {
        parent::defineFields();

        $this->addField(new Field(array(
            'name' => 'body',
            'type' => Field::TYPE_LONGSTRING
        )));
    }
}
