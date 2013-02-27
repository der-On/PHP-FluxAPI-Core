<?php
namespace Plugins\Core\Storage;

use \FluxAPI\Query;

class MySql extends \FluxAPI\Storage
{
    public function addFilters()
    {
        $this->addFilter('fields',function(QueryBuilder &$qb, array $params) {
            $qb->select($params);
        });
    }

    public function isConnected()
    {
        return isset($this->_api->app['db']);
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

        $modelClass = $query->getModel();
        $tableName = $modelClass::getCollectionName();

        $connection = $this->getConnection();

        $qb = $connection->createQueryBuilder();

        switch($query->getType()) {
            case Query::TYPE_SELECT:
                $qb->select('*'); // by default select all fields
                $qb->from($tableName,$tableName);
                break;

            case Query::TYPE_DELETE:
                $qb->delete($tableName);
                break;

            case Query::TYPE_UPDATE:
                $qb->update($tableName);
                break;
        }

        $queryFilters = $query->getFilters();

        foreach($queryFilters as $filter => $params) {
            if ($this->hasFilter($filter)) {
                $callback = $this->getFilter($filter);
                $callback($qb,$params);
            }
        }
        var_dump($qb->getSQL());
        $result = $qb->execute();
        var_dump($result);
    }

}
