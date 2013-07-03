<?php
namespace FluxAPI\Collection;

class ModelErrorCollection extends \FluxAPI\Collection
{
    public function toString()
    {
        $str = '';
        foreach($this->_items as $key => $item) {
            $str .= $item->getMessage() . "\n";
        }

        return $str;
    }
}