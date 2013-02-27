<?php
namespace Plugins\Core\Storage;

class MySql extends \FluxAPI\Storage
{
    public function addFilters()
    {
        $this->addFilter('equals',function(QueryBuilder $qb) {

        });
    }

    public function isConnected()
    {
        return exists($this->_api->app['db']);
    }

    public function connect()
    {
        $this->_api->app->register(new \Silex\Provider\DoctrineServiceProvider(), array(
            'db.options' => array(
                'driver' => 'pdo_mysql',
                'host' => $this->config['host'],
                'user' => $this->config['user'],
                'password' => $this->config['password'],
                'dbname' => $this->config['database'],
            ),
        ));
    }

    public function getConnection()
    {
        return $this->_api->app['db'];
    }

    public function executeQuery($query)
    {
        parent::executeQuery($query);

        $connection = $this->getConnection();

        $qb = $connection->createQueryBuilder();

        $queryFilters = $query->getFilters();

        $filters = $this->getFilters();

        foreach($queryFilters as $filter) {
            if ($this->hasFilter($filter)) {
                $filters[$filter]($qb);
            }
        }

        $result = $qb->getQuery()->execute();
        var_dump($result);
    }

}
