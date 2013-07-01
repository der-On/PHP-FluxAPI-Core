<?php
namespace FluxAPI\Collection;

class ModelCollection extends \FluxAPI\Collection
{
    /**
     * Query wich was used to retrieve this collection from the storage
     *
     * @var \FluxAPI\Query
     */
    protected $_query = NULL;

    /**
     * @param \FluxAPI\Query $query
     */
    public function setQuery(\FluxAPI\Query $query = NULL)
    {
        $this->_query = $query;
    }

    /**
     * @return \FluxAPI\Query|null
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * @param string $id
     * @return Collection
     */
    public function findById($id)
    {
        return $this->findBy('id', $id);
    }

    protected function _prepareItem($item)
    {
        if (is_object($item)) {
            $item->setQuery($this->getQuery());
        }

        return $this;
    }


}