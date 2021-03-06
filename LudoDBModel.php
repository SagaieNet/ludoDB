<?php
/**
 * Representation of a ludoDB table
 */
abstract class LudoDBModel extends LudoDBObject
{
    protected $data = array();
    protected $updates;
    private $externalClasses = array();
    private $commitDisabled;
    private $populated = false;

    protected function populate()
    {
        $this->populated = true;
        $this->arguments = $this->getValidArguments($this->arguments);
        $data = $this->db->one($this->sqlHandler()->getSql(), $this->arguments);
        if (isset($data)) {
            $this->populateWith($data);
            $this->setId($this->getValue($this->parser->getIdField()));
        }
    }

    private function getValidArguments($params)
    {
        $paramNames = $this->parser->getConstructorParams();
        for ($i = 0, $count = count($params); $i < $count; $i++) {
            $params[$i] = $this->getValidArgument($paramNames[$i], $params[$i]);
        }
        return $params;
    }

    protected function getValidArgument($key, $value)
    {
        return $value;
    }

    private function populateWith($data = array())
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    protected function getValue($column)
    {
        $this->autoPopulate();
        if($this->parser->isStaticColumn($column)){
            return $this->parser->getStaticValue($column);
        }
        if ($this->parser->isExternalColumn($column)) {
            return $this->getExternalValue($column);
        }
        if (isset($this->updates) && isset($this->updates[$column])) {
            return $this->updates[$column] == LudoDBSql::DELETED ? null : $this->updates[$column];
        }
        return isset($this->data[$column]) ? $this->data[$column] : $this->parser->getDefaultValue($column);
    }

    private function autoPopulate()
    {
        if (!$this->populated && !empty($this->arguments)) {
            $this->populate();
        }
    }

    private function getExternalValue($column)
    {
        $method = $this->parser->getGetMethod($column);
        return $this->getExternalClassFor($column)->$method();
    }

    /**
     * @param String $column
     * @return LudoDBCollection table
     */
    private function getExternalClassFor($column)
    {
        if (!isset($this->externalClasses[$column])) {
            $class = $this->parser->externalClassNameFor($column);
            $fk = $this->parser->foreignKeyFor($column);
            if (isset($fk)) {
                $val = $this->getValue($fk);
            } else {
                if (!$this->getId()) $this->commit();
                $val = $this->getId();
            }
            $this->externalClasses[$column] = new $class($val);
        }
        return $this->externalClasses[$column];
    }

    protected function setValue($column, $value)
    {
        if ($this->parser->isExternalColumn($column)) {
            $this->setExternalValue($column, $value);
        } else {
            $value = $this->db->escapeString($value);
            if (!isset($this->updates)) $this->updates = array();
            $this->updates[$this->parser->getInternalColName($column)] = $value;
        }
        return null;
    }

    private function setExternalValue($column, $value)
    {
        $method = $this->parser->getSetMethod($column);
        if (isset($method)) {
            $this->getExternalClassFor($column)->$method($value);
        }
    }

    public function disableCommit()
    {
        $this->commitDisabled = true;
    }

    public function enableCommit()
    {
        $this->commitDisabled = false;
    }

    public function commit()
    {
        if ($this->commitDisabled) return null;
        if (!isset($this->updates)) {
            if ($this->getId() || !$this->parser->isIdAutoIncremented()) {
                return null;
            }
        }
        if ($this->getId()) {
            $this->update();
        } else {
            $this->insert();
        }

        if (isset($this->updates)) {
            foreach ($this->updates as $key => $value) {
                $this->data[$key] = $value === LudoDBSql::DELETED ? null : $value;
            }
        }
        foreach ($this->externalClasses as $class) {
            $this->commitExternal($class);
        }
        $this->updates = null;
        return $this->getId();
    }

    /**
     * @param LudoDBObject $class
     */
    private function commitExternal($class)
    {
        $class->commit();
    }

    private function update()
    {
        if ($this->isValid()) {
            $this->beforeUpdate();
            $this->clearCache();
            $this->db->query($this->sqlHandler()->getUpdateSql(), isset($this->updates) ? array_values($this->updates) : null);
        }
    }

    public function getUncommitted()
    {
        return $this->updates;
    }

    private function insert()
    {
        if ($this->isValid()) {
            $this->beforeInsert();
            $this->db->query($this->sqlHandler()->getInsertSQL(), isset($this->updates) ? array_values($this->updates) : null);
            $this->setId($this->db->getInsertId());
        }
    }

    /**
     * Method executed before record is updated
     * @method beforeUpdate
     */
    protected function beforeUpdate()
    {
    }

    /**
     * Method executed before new record is saved in db
     * @method beforeInsert
     */
    protected function beforeInsert()
    {
    }

    /**
     * Rollback updates
     * @method rollback
     */
    public function rollback()
    {
        $this->updates = null;
    }

    protected function setId($id)
    {
        $field = $this->parser->getIdField();
        if(!isset($this->data[$field])){
            $this->data[$field] = $id;
            $this->externalClasses = array();
        }
    }

    public function getId()
    {
        $this->autoPopulate();
        $field = $this->parser->getIdField();
        return isset($this->data[$field]) ? $this->data[$field] : null;
    }

    /**
     * Create DB table
     * @method createTable
     */
    public function createTable()
    {
        $this->db->query($this->sqlHandler()->getCreateTableSql(), $this->parser->getDefaultValues());
        $this->createIndexes();
        $this->insertDefaultData();
    }

    /**
     * Returns true if database table exists.
     * @return bool
     */
    public function exists()
    {
        return $this->db->tableExists($this->parser->getTableName());
    }

    private $riskyQuery;

    /**
     * Drop database table
     * @method drop
     */
    public function drop()
    {
        if ($this->exists()) {
            $this->riskyQuery = "drop table " . $this->parser->getTableName();
        }
        return $this;
    }

    public function deleteTableData()
    {
        $this->riskyQuery = "delete from " . $this->parser->getTableName();
        return $this;
    }

    protected function beforeDelete(){

    }

    /**
     * Execute risky query,
     * @example
     * $p = new Person();
     * $p->drop()->yesImSure();
     */
    public function yesImSure()
    {
        if (isset($this->riskyQuery)) {
            $this->db->query($this->riskyQuery);
            if ($this->cacheEnabled()) {
                LudoDBCache::clearByClass(get_class($this));
                $json = new LudoDBCache();
                $json->deleteTableData()->yesImSure();
            }
            $this->riskyQuery = null;
        }
    }

    private function createIndexes()
    {
        $indexes = $this->parser->getIndexes();
        if (!isset($indexes)) return;
        foreach ($indexes as $index) {
            $this->db->query("create index " . $this->getIndexName($index) . " on " . $this->parser->getTableName() . "(" . $index . ")");
        }
    }

    private function getIndexName($field)
    {
        return 'IND_' . md5($this->parser->getTableName() . $field);
    }

    protected function insertDefaultData()
    {
        $data = $this->parser->getDefaultData();
        if (!isset($data)) return;
        foreach ($data as $row) {
            $cl = $this->getNewInstance();
            foreach ($row as $key => $value) {
                $cl->setValue($key, $value);
            }
            $cl->commit();
        }
    }

    /**
     * @method getClassName
     * @return LudoDBModel class
     */
    private function getNewInstance()
    {
        $className = get_class($this);
        return new $className;
    }

    /**
     * Return key-pair values with null values removed.
     * @param array $keys
     * @return array
     */
    public function getSomeValuesFiltered(array $keys)
    {
        return $this->some($keys, true);
    }

    /**
     * Return model values.
     * @param array $keys
     * @return array
     */
    public function getSomeValues(array $keys)
    {
        return $this->some($keys, false);
    }

    private function some(array $keys, $filtered = false)
    {
        $ret = array();
        foreach ($keys as $key) {
            $col = $this->parser->getPublicColumnName($key);
            $val = $this->getValue($key);
            if ($this->parser->canReadFrom($col)) {
                if (!$filtered || isset($val)) $ret[$col] = $val;
            }

        }
        return $ret;
    }

    public function clearValues()
    {
        $this->data = array();
        $this->updates = null;
    }

    public function getValues()
    {
        $this->autoPopulate();
        $columns = $this->parser->getColumns();
        $ret = array();
        foreach ($columns as $column => $def) {
            $colName = $this->parser->getPublicColumnName($column);
            if ($this->parser->canReadFrom($colName)) {
                $ret[$colName] = $this->getValue($column);
            }
        }
        return array_merge($ret, $this->getJoinColumns());
    }

    private function getJoinColumns()
    {
        $ret = array();
        if (isset($this->config['join'])) {
            foreach ($this->config['join'] as $join) {
                foreach ($join['columns'] as $col) {
                    $ret[$col] = $this->getValue($col);
                }
            }
        }
        return $ret;
    }

    public function isValid()
    {
        return true;
    }

    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) === 'set' && $name !== "setId") {
            $col = $this->parser->getColumnByMethod($name);
            if (isset($col) && $this->parser->canWriteTo($col)) {
                return $this->setValue($col, $arguments[0]);
            }
        }
        if (substr($name, 0, 3) === 'get') {
            $col = $this->parser->getColumnByMethod($name);
            if (isset($col) && $this->parser->canReadFrom($col)) {
                return $this->getValue($col);
            }
        }
        throw new Exception("Invalid method call " . $name);
    }

    public function setValues($data)
    {
        $valuesSet = false;
        foreach ($data as $column => $value) {
            if ($this->parser->canWriteTo($column)) {
                $this->setValue($column, $value);
                $valuesSet = true;
            }
        }
        return $valuesSet;
    }


    public function save($data)
    {
        if (empty($data)) return array();
        $idField = $this->parser->getIdField();
        if (isset($data[$idField])) $this->setId($data[$idField]);

        $this->setValues($data);
        $this->commit();

        return array($idField => $this->getId());
    }

    /**
     * Delete record
     */
    public function delete()
    {
        if ($this->getId()) {
            $this->db->query("delete from " . $this->parser->getTableName() . " where " . $this->parser->getIdField() . " = ?", $this->getId());
            $this->clearCache();
            $this->clearValues();
        }
    }
}
