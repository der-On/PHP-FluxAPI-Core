<?php
namespace Plugins\Core\Storage;

use \FluxAPI\Query;
use \FluxAPI\Field;
use \Doctrine\DBAL\Query\QueryBuilder;
use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\Comparator;

class MySql extends \FluxAPI\Storage
{
    public function addFilters()
    {
        $this->addFilter('select',function(QueryBuilder &$qb, array $params) {
            $qb->select($params);
        });

        $this->addFilter('equals',function(QueryBuilder &$qb, array $params) {
            $qb->andWhere($qb->expr()->eq($params[0],$params[1]));
        });

        $this->addFilter('order',function(QueryBuilder &$qb, array $params) {
            $qb->orderBy($params[0],isset($params[1])?$params[1]:'ASC');
        });

        $this->addFilter('limit',function(QueryBuilder &$qb, array $params) {
            $qb->setFirstResult(intval($params[0]));
            $qb->setMaxResults(intval($params[1]));
        });

        $this->addFilter('count',function(QueryBuilder &$qb, array $params) {
            $qb->select('COUNT('.$params[0].')');
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
                'dbname' => $this->config['database']
            ),
        ));
    }

    public function getConnection()
    {
        return $this->_api->app['db'];
    }

    public function getTableName($model)
    {
        return $this->config['table_prefix'].$model::getCollectionName();
    }

    public function executeQuery($query)
    {
        parent::executeQuery($query);

        $modelClass = $query->getModel();
        $tableName = $this->getTableName($modelClass);

        $connection = $this->getConnection();
        $qb = $connection->createQueryBuilder();

        if ($query->getType() == Query::TYPE_INSERT) { // Doctrines query builder does not support INSERTs so we need to create the SQL manually
            $data = $query->getData();

            // remove empty fields
            foreach($data as $name => $value) {
                if (empty($value)) {
                    unset($data[$name]);
                }
            }

            $sql = 'INSERT INTO '.$tableName
                .' ('
                    .implode(', ',array_keys($data))
                .') VALUES(';

                foreach(array_values($data) as $value) {
                    $sql .= $qb->expr()->literal($value);
                }
                $sql .= ')';

            $connection->query($sql);
            return TRUE;
        } else {

            if ($query->getType() == Query::TYPE_COUNT) {
                $query->filter('count',array('id'));
            }

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

                    $query->filter('equals',array('id',$query->getData('id')));

                    foreach($query->getData() as $name => $value)
                    {
                        if ($name != 'id') { // do not set the ID again
                            $qb->set($name,$qb->expr()->literal($value));
                        }
                    }
                    break;

                case Query::TYPE_COUNT:
                    $qb->from($tableName,$tableName);
                    break;
            }

            // apply query filters
            $queryFilters = $query->getFilters();

            foreach($queryFilters as $filter) {
                if ($this->hasFilter($filter[0])) {
                    $callback = $this->getFilter($filter[0]);
                    $callback($qb,$filter[1]);
                }
            }

            //var_dump($qb->getSQL());
            $result = $qb->execute();

            if (!is_object($result)) {
                return TRUE;
            }
            $result = $result->fetchAll();

            if ($query->getType() == Query::TYPE_COUNT) {
                return intval($result[0]['COUNT(id)']);
            } else {
                $instances = array();

                foreach($result as $data) {
                    $instances[] = new $modelClass($data);
                }
                return $instances;
            }
        }

        return NULL;
    }

    public function migrate()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $connection = $this->getConnection();

        $sm = $connection->getSchemaManager();

        $toSchema = new Schema();

        $models = $this->_api->getPlugins('Model');

        foreach($models as $model => $modelClass) {
            $model = new $modelClass();
            $table = $toSchema->createTable($this->getTableName($modelClass));
            $primary = array();
            $unique = array();

            foreach($model->getFields() as $field) {
                if (!empty($field->name) && !empty($field->type) && $field->type != Field::TYPE_RELATION) {

                    switch($field->type) {
                        case Field::TYPE_LONGSTRING:
                            $type = 'text';
                            break;

                        default:
                            $type = $field->type;
                    }

                    $config = array();

                    if (!empty($field->length)) {
                        $config['length'] = $field->length;
                    }

                    if ($field->unsigned) {
                        $config['unsigned'] = $field->unsigned;
                    }

                    if ($field->autoIncrement) {
                        $config['autoincrement'] = $field->autoIncrement;
                    }

                    $table->addColumn($field->name,$type,$config);

                    if ($field->primary) {
                        $primary[] = $field->name;
                    }
                }

                if (count($primary) > 0) {
                    $table->setPrimaryKey($primary);
                }

                if (count($unique) > 0) {
                    $table->addUniqueIndex($unique);
                }
            }
        }

        $sql = array();

        $comparator = new Comparator();
        $sm = $connection->getSchemaManager();
        $dp = $connection->getDatabasePlatform();
        $fromSchema = $sm->createSchema();

        $schemaDiff = $comparator->compare($fromSchema,$toSchema);
        $sql = $schemaDiff->toSql($dp);

        foreach($sql as $query) {
            $connection->query($query);
        }
    }
}
