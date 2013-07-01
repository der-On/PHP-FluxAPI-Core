<?php
namespace FluxAPI\Collection;

class ModelCollection extends \FluxAPI\Collection
{
    /**
     * @param string $id
     * @return Collection
     */
    public function findById($id)
    {
        return $this->findBy('id', $id);
    }
}