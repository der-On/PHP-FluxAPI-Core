<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ondrej
 * Date: 09.04.13
 * Time: 13:41
 * To change this template use File | Settings | File Templates.
 */

namespace Plugins\FluxAPI\Model;

use \FluxAPI\Field;

class File extends \Plugins\FluxAPI\Model
{
    public function defineFields()
    {
        parent::defineFields();

        // path/uri to the file
        $this->addField(new Field(array(
            'name' => 'path',
            'type' => Field::TYPE_STRING,
            'length' => 2048,
        )))
        // path to the preview image
        ->addField(new Field(array(
            'name' =>  'preview',
            'type' => Field::TYPE_STRING,
            'length' => 2048,
        )))
        // filetype/extension
        ->addField(new Field(array(
           'name' => 'type',
           'type' => Field::TYPE_STRING,
           'length' => 4,
        )));
    }
}