<?php
namespace Plugins\FluxAPI\Storage;

use \FluxAPI\Query;
use \FluxAPI\Field;
use \Doctrine\DBAL\Query\QueryBuilder;
use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\Comparator;

class MySql extends \FluxAPI\Storage
{
    public function __construct(\FluxAPI\Api $api, array $config = array())
    {
        parent::__construct($api, $config);

        if (!\Doctrine\DBAL\Types\Type::hasType('varbinary')) {
            \Doctrine\DBAL\Types\Type::addType('varbinary', 'Plugins\FluxAPI\DbalTypeVarbinary');
        }
    }


    public function addFilters()
    {
        parent::addFilters();

        $this->addFilter('join','filterJoin');
    }

    public function filterSelect(&$qb, array $params)
    {
        $qb->select($params);
        return $qb;
    }

    public function filterEqual(&$qb, array $params)
    {
        $isId = ($params[0] == 'id' || substr($params[0],-3) == '_id');

        if ($isId) {
            $params[1] = $this->uuidToHex($params[1]);
            $type = 'varbinary';
        } else {
            $type = (isset($params[2]))?$params[2]:'string';
        }

        $qb->andWhere($qb->expr()->eq($params[0],($type!='string')?$params[1]:$qb->expr()->literal($params[1])));
        return $qb;
    }

    public function filterNotEqual(&$qb, array $params)
    {
        $qb->andWhere($qb->expr()->neq($params[0],$params[1]));
        return $qb;
    }

    public function filterGreaterThen(&$qb, array $params)
    {
        $qb->andWhere($qb->expr()->gt($params[0],$params[1]));
        return $qb;
    }

    public function filterGreaterThenOrEqual(&$qb, array $params)
    {
        $qb->andWhere($qb->expr()->gte($params[0],$params[1]));
        return $qb;
    }

    public function filterLessThen(&$qb, array $params)
    {
        $qb->andWhere($qb->expr()->lt($params[0],$params[1]));
        return $qb;
    }

    public function filterLessThenOrEqual(&$qb, array $params)
    {
        $qb->andWhere($qb->expr()->lte($params[0],$params[1]));
        return $qb;
    }

    public function filterRange(&$qb, array $params)
    {
        $qb->andWhere($qb->expr()->andX(
            $qb->expr()->gte($params[0],$params[1]),
            $qb->expr()->lte($params[0],$params[2])
        ));
        return $qb;
    }

    public function filterOrder(&$qb, array $params)
    {
        $qb->orderBy($params[0],isset($params[1])?$params[1]:'ASC');
        return $qb;
    }

    public function filterLimit(&$qb, array $params)
    {
        $qb->setFirstResult(intval($params[0]));
        $qb->setMaxResults(intval($params[1]));
        return $qb;
    }

    public function filterCount(&$qb, array $params)
    {
        $qb->select('COUNT('.$params[0].')');
        return $qb;
    }

    public function filterLike(&$qb, array $params)
    {
        $qb->andWhere($qb->expr()->like($params[0],$params[1]));
        return $qb;
    }

    public function filterIn(&$qb, array $params)
    {
        $values = $params[1];

        $in = '';

        if (!is_array($values)) {
            $values = explode(',',$values);
        }

        foreach($values as $i => $value) {
            $in .= $qb->expr()->literal($value);

            if ($i < count($values) -1) {
                $in .= ', ';
            }
        }

        $qb->andWhere($params[0].' IN ('.$in.')');
        return $qb;
    }

    public function filterJoin(&$qb, array $params)
    {
        $_params = $params;
        array_shift($_params);

        switch($params[0]) {
            case 'inner':
                return $this->filterInnerJoin($qb,$_params);
                break;

            case 'left':
                return $this->filterLeftJoin($qb,$_params);
                break;

            default:
                return $qb;
        }
    }

    public function filterInnerJoin(&$qb, array $params)
    {
        return $qb;
    }

    public function filterLeftJoin(&$qb, array $params)
    {
        $qb->leftJoin($params[0],$params[1], $params[1], $params[2]);
        return $qb;
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
                'debug_sql' => FALSE,
            ),
        ));
    }

    public function getConnection()
    {
        return $this->_api->app['db'];
    }

    public function getLastId($model_name)
    {
        $connection = $this->getConnection();
        $table_name = $this->getTableName($model_name);

        $sql = 'SELECT LAST_INSERT_ID() FROM '.$table_name;

        if ($this->config['debug_sql']) {
            print("\nSQL: ".$sql."\n");
        }

        $result = $connection->query($sql)->fetch();

        return $result['LAST_INSERT_ID()'];
    }

    public function loadRelation(\FluxAPI\Model $model, $name)
    {
        if (!$model->hasField($name)) {
            return NULL;
        } else {
            $field = $model->getField($name);
        }

        if ($field->type == Field::TYPE_RELATION && !empty($field->relationModel)) {
            $id = $model->id;
            $model_name = $model->getModelName();
            $id_field_name = strtolower($model_name).'_id';

            $table_name = $this->getTableName($model_name);
            $relation_table = $this->getRelationTableName($model_name);

            $rel_field_name = $field->name.'_id';

            $query = new Query();
            $query->filter('join',array('left', $table_name, $relation_table, $relation_table.'.'.$id_field_name.'='.$this->uuidToHex($id)))
                  ->filter('equal',array($table_name.'.id',$relation_table.'.'.$rel_field_name,'field'));

            $models = $this->load($model_name,$query);

            if (in_array($field->relationType,array(Field::BELONGS_TO_ONE,Field::HAS_ONE))) {
                if (count($models) > 0) {
                    return $models[0];
                } else {
                    return NULL;
                }
            } else {
                return $models;
            }
        }
        return NULL;
    }

    public function addRelation(\FluxAPI\Model $model, \FluxAPI\Model $relation, \FluxAPI\Field $field)
    {
        $model_name = $model->getModelName();
        $rel_table = $this->getRelationTableName($model_name); // get the table name of the relations table
        $model_field_name = $this->getCollectionName($model_name).'_id';
        $rel_field_name = $field->name.'_id';

        $connection = $this->getConnection();

        // before a new record is inserted we need to check if it's not related already
        $sql = 'SELECT COUNT('.$rel_field_name.') FROM '.$rel_table.' WHERE '.$model_field_name.'='.$this->uuidToHex($model->id).' AND '.$rel_field_name.'='.$this->uuidToHex($relation->id);

        $result = $connection->query($sql)->fetch();

        $count = intval($result['COUNT('.$rel_field_name.')']);

        if ($count == 0) {
            $sql = 'INSERT INTO '.$rel_table.' ('.$model_field_name.','.$rel_field_name.') VALUES('.$this->uuidToHex($model->id).','.$this->uuidToHex($relation->id).')';

            if ($this->config['debug_sql']) {
                print("\nSQL: ".$sql."\n");
            }

            $connection->query($sql);
        }
    }

    public function removeRelation(\FluxAPI\Model $model, \FluxAPI\Model $relation, \FluxAPI\Field $field)
    {
        $model_name = $model->getModelName();
        $rel_table = $this->getRelationTableName($model_name); // get the table name of the relations table
        $model_field_name = $this->getCollectionName($model_name).'_id';
        $rel_field_name = $field->name.'_id';

        $connection = $this->getConnection();

        $sql = 'DELETE FROM '.$rel_table.' WHERE '.$model_field_name.'='.$this->uuidToHex($model->id).' AND '.$rel_field_name.'='.$this->uuidToHex($relation->id);

        if ($this->config['debug_sql']) {
            print("\nSQL: ".$sql."\n");
        }

        $connection->query($sql);
    }

    public function removeAllRelations(\FluxAPI\Model $model, \FluxAPI\Field $field, array $exclude_ids = array())
    {
        $model_name = $model->getModelName();
        $rel_table = $this->getRelationTableName($model_name); // get the table name of the relations table
        $model_field_name = $this->getCollectionName($model_name).'_id';
        $rel_field_name = $field->name.'_id';

        $connection = $this->getConnection();

        $sql = 'DELETE FROM '.$rel_table.' WHERE '.$model_field_name.'='.$this->uuidToHex($model->id).' AND '.$rel_field_name.'!=""';

        foreach($exclude_ids as $i => $id) {
            $exclude_ids[$i] = $this->uuidToHex($id);
        }

        if (count($exclude_ids) > 0) {
            $sql .= ' AND '.$rel_field_name.' NOT IN ('.implode(',',$exclude_ids).')';
        }

        if ($this->config['debug_sql']) {
            print("\nSQL: ".$sql."\n");
        }

        $connection->query($sql);
    }

    public function getTableName($model_name)
    {
        return $this->config['table_prefix'].$this->getCollectionName($model_name);
    }

    public function getRelationTableName($model_name)
    {
        return $this->config['table_prefix'].$this->getCollectionName($model_name).'_rel';
    }

    public function executeQuery(\FluxAPI\Query $query)
    {
        parent::executeQuery($query);

        $model_name = $query->getModelName();
        $model = $this->_api['models']->create($model_name);
        $fields = $model->getFields();
        $tableName = $this->getTableName($model_name);

        $connection = $this->getConnection();
        $qb = $connection->createQueryBuilder();

        if ($query->getType() == Query::TYPE_INSERT) { // Doctrines query builder does not support INSERTs so we need to create the SQL manually
            $data = $query->getData();

            // serialize and remove empty fields
            foreach($data as $name => $value) {
                if (empty($value)) {
                    unset($data[$name]);
                } elseif (isset($fields[$name]) && $fields[$name]->type != Field::TYPE_RELATION) {
                    $data[$name] = $this->serialize($data[$name], $fields[$name]);
                }
            }

            $sql = 'INSERT INTO '.$tableName
                .' ('
                    .implode(', ',array_keys($data))
                .') VALUES(';

                $i = 0;
                foreach($data as $name => $value) {
                    // convert uuids to hex
                    if ($name == 'id') {
                        $value = $this->uuidToHex($value);
                        $sql .= $value;
                    } else {
                        $sql .= $qb->expr()->literal($value);
                    }

                    if ($i < count($data) - 1) {
                        $sql .= ',';
                    }
                    $i++;
                }
                $sql .= ')';

            if ($this->config['debug_sql']) {
                print("\nSQL: ".$sql."\n");
            }

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

                    foreach($query->getData() as $name => $value)
                    {
                        if ($name != 'id' && isset($fields[$name]) && $fields[$name]->type != Field::TYPE_RELATION) { // do not set the ID again
                            $value = $this->serialize($value, $fields[$name]);
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
                    $this->executeFilter($callback,array(&$qb,$filter[1]));
                }
            }

            if ($this->config['debug_sql']) {
                print("\nSQL: ".$qb->getSQL()."\n");
            }

            $result = $qb->execute();

            if (!is_object($result)) {
                return (intval($result) > 0) ? TRUE : FALSE;
            }
            $result = $result->fetchAll();

            if ($query->getType() == Query::TYPE_COUNT) {
                return intval($result[0]['COUNT(id)']);
            } else {
                $instances = array();

                foreach($result as $data) {
                    // unserialize the data
                    foreach($data as $name => $value) {
                        if (isset($fields[$name]) && $fields[$name]->type != Field::TYPE_RELATION) {
                            $data[$name] = $this->unserialize($data[$name], $fields[$name]);
                        }
                    }

                    $instances[] = $this->_api['models']->create($model_name, $data, NULL, FALSE);
                }
                return $instances;
            }
        }

        return NULL;
    }

    public function getFieldType(\FluxAPI\Field $field)
    {
        switch($field->type) {
            case Field::TYPE_LONGSTRING:
                $type = 'text';
                break;

            case Field::TYPE_TIMESTAMP:
                $type = 'integer';
                break;

            case Field::TYPE_BYTEARRAY:
                $type = 'varbinary';
                break;

            default:
                $type = $field->type;
        }

        return $type;
    }

    public function getFieldConfig(\FluxAPI\Field $field)
    {
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

        return $config;
    }

    public function getRelationField(\FluxAPI\Field $field)
    {
        $rel_model_instance = $this->_api['models']->create($field->relationModel);

        if (!empty($rel_model_instance)) {
            // we need the id field of the model so we can create a field in the relation table matching the field config
            $rel_id_field = $rel_model_instance->getField('id');

            return $rel_id_field;
        } else {
            return NULL;
        }
    }

    public function migrate($model_name = NULL)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $connection = $this->getConnection();

        $toSchema = new Schema();

        $models = $this->_api['plugins']->getPlugins('Model');

        foreach($models as $model_name => $modelClass) {
            $model = $this->_api['models']->create($model_name);
            $table_name = $this->getTableName($model_name);
            $table = $toSchema->createTable($table_name);
            $primary = array();
            $unique = array();

            // create relation table for this model
            $relation_table = $toSchema->createTable($this->getRelationTableName($model_name));

            // TODO: split this into multiple methods

            foreach($model->getFields() as $field) {
                if (!empty($field->name) && !empty($field->type)) {

                    if ($field->type != Field::TYPE_RELATION) {

                        $type = $this->getFieldType($field);
                        $config = $this->getFieldConfig($field);

                        $table->addColumn($field->name,$type,$config);

                        // add own model id field to relation table
                        if ($field->name == 'id') {
                            $relation_field_name = $this->getCollectionName($model_name).'_id';

                            // autoincrement must be removed
                            if (isset($config['autoincrement'])) {
                                unset($config['autoincrement']);
                            }

                            $relation_table->addColumn($relation_field_name, $type, $config);
                        }

                        if ($field->primary) {
                            $primary[] = $field->name;
                        }
                    } elseif($field->type == Field::TYPE_RELATION && !empty($field->relationModel)) { // add relation model id field to relation table
                        // we need the id field of the related model so we can create a matching field in the relation table
                        $rel_id_field = $this->getRelationField($field);

                        if ($rel_id_field) {
                            $relation_field_name = $field->name.'_id';

                            $rel_field_type = $this->getFieldType($rel_id_field);
                            $rel_field_config = $this->getFieldConfig($rel_id_field);

                            // autoincrement must be removed
                            if (isset($rel_field_config['autoincrement'])) {
                                unset($rel_field_config['autoincrement']);
                            }

                            $relation_table->addColumn($relation_field_name, $rel_field_type, $rel_field_config);
                        }
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

        $comparator = new Comparator();
        $sm = $connection->getSchemaManager();
        $dp = $connection->getDatabasePlatform();
        $fromSchema = $sm->createSchema();

        $schemaDiff = $comparator->compare($fromSchema,$toSchema);
        $sql = $schemaDiff->toSql($dp);

        foreach($sql as $query) {
            if ($this->config['debug_sql']) {
                print("\nSQL: ".$query."\n");
            }
            $connection->query($query);
        }
    }

    public function unserialize($str, \FluxAPI\Field $field)
    {
        // restore truncated uuid's
        if ($field->name == 'id' && $field->type == Field::TYPE_BYTEARRAY) {
            return $this->hexToUuid($str);
        } else {
            return parent::unserialize($str, $field);
        }
    }

    public function uuidToHex($uuid)
    {
        return '0x' . str_replace('-','',$uuid);
    }

    public function hexToUuid($hex)
    {
        $hex = bin2hex($hex);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
