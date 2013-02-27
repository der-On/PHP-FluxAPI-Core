<?php
namespace Plugins\Core\Storage;

class MySql extends \FluxAPI\Storage
{
    public function addFilters()
    {
        $this->addFilter('equals',function(QueryBuilder $qb) {

        });
    }

    public function executeQuery($query)
    {
        parent::executeQuery($query);

        $connection =
        $qb = $connection->createQueryBuilder();

        $filters = $query->getFilters();

        foreach($filters as $filter) {
            if ($this->hasFilter($filter)) {
                $callback = $this->getFilter($filter);

                $callback($this,);
            }
        }
    }

}
