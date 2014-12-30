<?php
// Copyright 2015 The Smpe Authors. All rights reserved.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

class Smpe_DbMysql implements Smpe_DbInterface
{
    /**
     * @var int Query counter
     */
    public static $queries = 0;

    /**
     * @param $module
     * @return mixed
     * @throws Exception
     */
    public static function db($module) {
        //Allows multiple modules to use the same database
        $dataIndex = Config::$modules[$module]['dsn'];
        if(empty(self::$db[$dataIndex])){
            $db = Config::$dsn[$dataIndex];
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;', $db['server'], $db['port'], $db['database']);
            $options = array(
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            );

            try{
                //PDO::ATTR_EMULATE_PREPARES => 1
                self::$db[$dataIndex] = new PDO($dsn, $db['user'], $db['password'], $options);
                self::$db[$dataIndex]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                if($db['profiling']){ //profiling
                    self::$db[$dataIndex]->exec("SET profiling = 1");
                }
            } catch (PDOException $e) {
                throw new Exception($e->getMessage());
            }
        }

        return self::$db[$dataIndex];
    }

    /**
     * @param $module
     * @param $table
     * @param $primary
     * @param $joins
     */
    public function __construct($module, $table, $primary, $joins = array()) {
        $this->module  = $module;
        $this->table   = $table;
        $this->primary = $primary;
        $this->joins   = $joins;
    }

    /**
     * Quotes a string for use in a query.
     * @param string $value
     * @return string
     */
    public function quote($value) {
        return self::db($this->module)->quote($value);
    }

    /**
     * Run SQL
     * @param $sql
     * @param array $parameters
     * @return PDOStatement
     * @throws Exception
     */
    public function query($sql, $parameters = array()) {
        self::$queries++;

        try {
            $statement = self::db($this->module)->prepare($sql);
            $statement->execute($parameters);
            return $statement;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Read data, paging
     * @param string $fields
     * @param string $join
     * @param string $where Include where/group/having/order
     * @param array $parameters
     * @param int $pageIndex
     * @param int $pageSize
     * @param array $opts
     * @return array|bool|string
     */
    public function fetchAll($fields = 'a.*', $join = '', $where = '1', $parameters = array(), $pageIndex = 0, $pageSize = 10000, $opts = array()) {
        $lock = $this->lockField((empty($opts['lock']) ? '' : $opts['lock']));
        $fetchType = empty($opts['fetch_type']) ? PDO::FETCH_ASSOC : $opts['fetch_type'];
        $sql = sprintf("SELECT %s FROM `%s` a %s WHERE %s LIMIT %d, %d %s", $fields, $this->table, $join, $where, $pageIndex, $pageSize, $lock);
        return $this->query($sql, $parameters)->fetchAll($fetchType);
    }

    /**
     * Read the data, paging, sortable
     * @param array $filter array('user_id' => '2', 'gender' => 'male')
     * @param string $group
     * @param array $order array('a.user_id' => '0', 'b.gender' => '1')
     * @param int $pageIndex
     * @param int $pageSize
     * @param int $lock
     * @return array|bool|string
     */
    public function all($filter = array(), $group = '', $order = array(), $pageIndex = 0, $pageSize = 10000, $lock = 0) {
        $where = $this->where($filter, $group, $order);
        return $this->fetchAll('a.*', $where['join'], $where['where'], $where['param'], $pageIndex, $pageSize, array('lock'=>$lock));
    }

    /**
     *
     * @param $arr
     * @param $data
     * @param $count
     */
    public function page($arr, &$data, &$count) {
        $data  = $this->all($arr['Where'], $arr['Group'], $arr['Order'], $arr['PageIndex'], $arr['PageSize']);
        $count = $this->count($arr['Where'], $arr['Group'], $arr['Order']);
    }

    /**
     * Read a single line, sortable
     * @param array $filter array('user_id' => '2', 'gender' => 'male')
     * @param string $group
     * @param array $order array('a.user_id' => '0', 'b.gender' => '1')
     * @param int $lock
     * @return array|bool|string
     */
    public function row($filter = array(), $group = '', $order = array(), $lock = 0) {
        $where = $this->where($filter, $group, $order);
        $result = $this->fetchAll('a.*', $where['join'], $where['where'], $where['param'], 0, 1, array('lock'=>$lock));
        return (empty($result) ? $result : $result[0]);
    }

    /**
     * @param array $filter
     * @param string $group
     * @param array $order
     * @param int $lock
     * @return array|bool|string
     * @throws Exception
     */
    public function rowEx($filter = array(), $group = '', $order = array(), $lock = 0) {
        $r = $this->row($filter, $group, $order, $lock);
        if(empty($r)) {
            throw new Exception('No data.');
        } else {
            return $r;
        }
    }

    /**
     * @param string $column
     * @param array $filter array('user_id' => '2', 'gender' => 'male')
     * @param string $group
     * @param array $order array('a.user_id' => '0', 'b.gender' => '1')
     * @param int $lock
     * @return string
     */
    public function value($column, $filter = array(), $group = '', $order = array(), $lock = 0) {
        $where = $this->where($filter, $group, $order);
        $result = $this->fetchAll($column, $where['join'], $where['where'], $where['param'], 0, 1, array('lock'=>$lock));
        return (empty($result) ? '' : $result[0]);
    }

    /**
     * Total read row, sortable
     * @param string $column
     * @param array $filter array('user_id' => '2', 'gender' => 'male')
     * @param string $group
     * @param array $order array('a.user_id' => '0', 'b.gender' => '1')
     * @param int $lock
     * @return array()
     */
    public function total($column, $filter = array(), $group = '', $order = array(), $lock = 0) {
        $sql = "SELECT %s FROM (SELECT 1 FROM `%s` a %s WHERE %s %s) z";
        $where = $this->where($filter, $group, $order);
        $sql = sprintf($sql, $column, $this->table, $where['join'], $where['where'], $this->lockField($lock));
        $result = $this->query($sql, $where['param'])->fetchAll(PDO::FETCH_ASSOC);
        return (empty($result) ? 0 : $result[0]);
    }

    /**
     * Total read row, sortable
     * @param array $filter array('user_id' => '2', 'gender' => 'male')
     * @param string $group
     * @param array $order array('a.user_id' => '0', 'b.gender' => '1')
     * @param int $lock
     * @return int
     */
    public function count($filter = array(), $group = '', $order = array(), $lock = 0) {
        $sql = "SELECT COUNT(*) FROM (SELECT 1 FROM `%s` a %s WHERE %s %s) z";
        $where = $this->where($filter, $group, $order);
        $sql = sprintf($sql, $this->table, $where['join'], $where['where'], $this->lockField($lock));
        $result = $this->query($sql, $where['param'])->fetchAll(PDO::FETCH_NUM);
        return (empty($result) ? 0 : (int)$result[0][0]);
    }

    /**
     * Add data (single, multi-line)
     * @param mixed $data
     * @return number
     */
    public function insert($data) {
        if(empty($data)) {
            return 0;
        }

        if(!isset($data[0])) {
            $data = array($data); //Support insert multiple rows
        }

        $sql = 'INSERT INTO `%s`(`%s`) VALUES %s';
        $sql = sprintf($sql, $this->table, implode('`,`', array_keys($data[0])), $this->insertFields($data));

        if($this->query($sql)->rowCount() > 0){
            return self::db($this->module)->lastInsertId();
        }

        return 0;
    }

    /**
     * Update
     * @param mixed $data
     * @param mixed $filter
     * @return number
     */
    public function update($data, $filter) {
        $where = $this->where($filter, '', false);
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $this->table, $this->updateFields($data), $where['where']);
        return $this->query($sql, $where['param'])->rowCount();
    }

    /**
     * Deleting data.
     * @param mixed $filter
     * @return number
     */
    public function delete($filter) {
        $where = $this->where($filter, '', false);
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $this->table, $where['where']);
        return $this->query($sql, $where['param'])->rowCount();
    }

    /**
     * @var string module name
     */
    private $module  = '';

    /**
     * @var string table name
     */
    private $table   = '';

    /**
     * @var string primary
     */
    private $primary = '';

    /**
     * @var array join tables
     */
    private $joins = array();

    /**
     * @var PDO database instance
     */
    private static $db = array();

    /**
     * Build Conditions. <br>
     *
     * Simple search. <br>
     * $filter = array('user_id' => '2', 'gender' => 'male'); <br>
     *
     * Advanced Search: <br>
     * $filter = array( <br>
     *     array('AND', 'a', 'field_a', '=',     monle_filter::datetime('field1', INPUT_GET), true), <br>
     *     array('OR',  'a', 'field_b', '<',     monle_filter::datetime('field1', INPUT_GET)), <br>
     *     array('AND', 'a', 'field_c', 'RLIKE', monle_filter::datetime('field1', INPUT_GET)), <br>
     *     array('AND', 'a', 'field_d', 'IN',    array('1', '2')), <br>
     *     array('AND', '',  '',        '(',     array( //复合条件 <br>
     *         array('AND', 'a', 'field_e', '=',     monle_filter::datetime('field1', INPUT_GET)), <br>
     *     )), <br>
     * ); <br>
     *
     * @param array $filter
     * @param string $group
     * @param array $order
     * @return array
     */
    private function where($filter, $group = '', $order = array()) {
        $result = array('join_tables' => array(), 'join' => '', 'where' => ' 1 ', 'param' => array());

        if(isset($filter[0])){ //Advanced Search
            foreach($filter as $item){
                $this->whereItem($result, $item);
            }

            //JOIN
            foreach ($result['join_tables'] as $alias) {
                if(isset($this->joins[$alias])) {
                    $result['join'] .= $this->joins[$alias];
                }
            }
        } else { //Simple search
            foreach($filter as $key => $value){
                $result['where'] .= sprintf(" AND `%s` = :%s ", $key, $key);
                $result['param'][$key] = $value;
            }
        }


        //Group by
        if($group != ''){
            $result['where'] .= ' GROUP BY '.$group;
        }

        // Sort
        // false: none,
        // array(): Primary key reverse,
        // array('id'=>'asc','str'=>'desc'): Specify the sort
        if(is_array($order)){
            $result['where'] .= ' ORDER BY ';
            if(count($order) > 0){
                foreach($order as $key => $value){
                    if(!in_array($value, array('asc', 'desc'))) {
                        continue; //Security
                    }

                    //将形如a.user_id拆分为$tableAlias和$field
                    $fields = explode('.', $key);
                    if(count($fields) == 1){
                        $tableAlias = 'a';
                        $field = $fields[0];
                    } else {
                        $tableAlias = $fields[0];
                        $field = $fields[1];
                    }

                    $keys = $this->quoteField($tableAlias, $field, $result['param']);
                    $result['where'] .= $keys['field'].' '.strtoupper($value).' ,';
                }

                $result['where'] = rtrim($result['where'], ',');
            } else {
                $result['where'] .= ' a.'.$this->primary.' DESC ';
            }
        }

        return $result;
    }

    /**
     * Build a single condition <br>
     * $item[0] Logical connection conditions, e.g. AND,OR <br>
     * $item[1] Alias table, e.g. a <br>
     * $item[2] Field, e.g. a.user_id <br>
     * $item[3] Comparison of conditions, e.g. =,LIKE <br>
     * $item[4] Value <br>
     * $item[5] Strict Mode, true or false <br>
     * @param array $result
     * @param array $item
     */
    private function whereItem(&$result, $item) {
        if(is_null($item[4]) || $item[4] === false || (isset($item[5]) && $item[5] === false && $item[4] == '')){
            return;
        }

        $keys = $this->quoteField($item[1], $item[2], $result['param']);

        //将非空或非a表保存到$result['join_tables']
        if($item[1] != '' && $item[1] != 'a'){
            $result['join_tables'] = array_merge($result['join_tables'], array($item[1]));
        }

        switch($item[3]){
            case     '(': //复合条件
                $result['where'] .= $item[0].' ( 1 ';
                foreach($item[4] as $subItem){
                    $this->whereItem($result, $subItem);
                }
                $result['where'] .= ' ) ';
                break;
            case    'IN': //范围
                $result['where'] .= sprintf(" %s %s IN ( ", $item[0], $keys['field']);
                for($i = 0; $i < count($item[4]); $i++){
                    $result['where'] .= ($i > 0 ? ',' : '') . self::db($this->module)->quote($item[4][$i]);
                }
                $result['where'] .= ' ) ';
                break;
            case  'LIKE': //全模糊
                $result['where'] .= sprintf(" %s %s LIKE '%s' ", $item[0], $keys['field'], '%'.trim($this->quote($item[4]), '\'').'%');
                break;
            case 'LLIKE': //左模糊
                $result['where'] .= sprintf(" %s %s LIKE '%s' ", $item[0], $keys['field'], '%'.trim($this->quote($item[4]), '\''));
                break;
            case 'RLIKE': //右模糊
                $result['where'] .= sprintf(" %s %s LIKE '%s' ", $item[0], $keys['field'], trim($this->quote($item[4]), '\'').'%');
                break;
            case     '<':
            case    '<=':
                $result['where'] .= sprintf(" %s %s %s :%s ", $item[0], $keys['field'], $item[3], $keys['param'].'_max');
                $result['param'][$keys['param'].'_max'] = $item[4];
                break;
            case     '>':
            case    '>=':
                $result['where'] .= sprintf(" %s %s %s :%s ", $item[0], $keys['field'], $item[3], $keys['param'].'_min');
                $result['param'][$keys['param'].'_min'] = $item[4];
                break;
            case     '=':
            case    '<>':
            default:      //全等于/不等于
                $result['where'] .= sprintf(" %s %s %s :%s ", $item[0], $keys['field'], $item[3], $keys['param']);
                $result['param'][$keys['param']] = $item[4];
                break;
        }
    }

    /**
     * Quote a field with ``.
     * @param $tableAlias
     * @param $field
     * @param $params
     * @return array
     */
    private function quoteField($tableAlias, $field, $params) {
        //当$tableAlias为空值时, 忽略掉
        $fullField = empty($tableAlias) ? '`'.$field.'`' : $tableAlias.'.`'.$field.'`';

        $result = array('field'=>$fullField, 'param'=>$field);
        if(isset($params[$result['param']])){ //避免重复
            $result['param'] = $result['param'].'_'.rand(10000, 99999);
        }

        return $result;
    }

    /**
     * @param $lock
     * @return string
     */
    private function lockField($lock) {
        $str = '';
        switch($lock) {
            //case 0: $lock = ''; break;
            case 1: $str = 'FOR UPDATE'; break;
            case 2: $str = 'LOCK IN SHARE MODE'; break;
        }

        return $str;
    }

    /**
     * Generate set fields of sql statement for update
     * @param $values
     * @return string
     */
    private function updateFields($values) {
        $fields = '';
        foreach ($values as $key => $value) {
            $fields .= sprintf(',`%s` = %s', $key, $this->quote($value));
        }

        return substr($fields, 1);
    }

    /**
     * Generate multi value of sql statement for insert.
     * @param $values
     * @return string
     */
    private function insertFields($values) {
        $sql = '';
        foreach ($values as $value) {
            $a = '';
            foreach ($value as $v) {
                $a .= sprintf('%s,', $this->quote($v));
            }

            $sql .= sprintf('(%s),', rtrim($a, ','));
        }

        return rtrim($sql, ',');
    }
}
