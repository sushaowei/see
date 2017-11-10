<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/6
 * Time: 上午10:17
 */
namespace see\db;

use see\base\Object;
use see\db\query\Delete;
use see\db\query\Insert;
use see\db\query\Query;
use see\db\query\Select;
use see\db\query\Update;
use see\exception\ErrorException;

/**
 * Class PdoMysql
 * @package see\db
 */
class PdoMysql extends Object{

    /**
     * @var \PDO
     */
    public $db;


    /**
     * @var
     */
    public $dns;

    /**
     * @var
     */
    public $user;

    /**
     * @var
     */
    public $password;

    public $options;

    /**
     * @var \PDOStatement
     */
    public $stat;

    private $connect;

    public $slowLog = true;

    public $slowCost = 100;

    /**
     *
     */
    public function init(){
//        $this->db = new \PDO($this->dns, $this->user, $this->password, $this->getOptions());
        $this->connect['default'] = new \PDO($this->dns, $this->user, $this->password, $this->options);
        $this->db = $this->connect['default'];
    }

    public function setDb($dbName){
        if(isset($this->connect[$dbName])){
            $this->db = $this->connect[$dbName];
        }else{
            if(!empty($this->$dbName)){
                $dbConfig = $this->$dbName;
                $this->connect[$dbName] = new \PDO($dbConfig['dns'], $dbConfig['user'], $dbConfig['password'], $dbConfig['options']);
                $this->db = $this->connect[$dbName];
            }else{
                throw new ErrorException("not found db config:".$dbName);
            }
        }
    }

    /**
     * query sql
     * @param $sql Query|string
     * @param array $values
     * @return bool|\PDOStatement
     */
    public function query($sql, array $values=[]){
        $sqlStart = microtime(true);
        $logSql = is_string($sql) ? $sql :$sql->buildSql();
        \See::$log->trace("sql:%s", $logSql);
        $this->stat = $this->db->prepare($sql);
        if($this->stat->execute($values)){
            $sqlEnd = microtime(true);
            $cost =round( ($sqlEnd - $sqlStart)*1000 ,2);
            if($this->slowLog && $cost > $this->slowCost){
                \See::$log->warning('slowsql, cost:%s,sql:%s',$cost,$logSql);
            }

            return $this->stat;
        }else{
            return false;
        }
        
    }


    /**
     * @return string
     */
    public function lastInsertId(){
        return $this->db->lastInsertId();
    }

    /**
     * @return int
     */
    public function rowCount(){
        return $this->stat->rowCount();
    }

    /**
     * @return mixed
     */
    public function fetch(){
        return $this->stat->fetch();
    }

    /**
     * @return array
     */
    public function fetchAll(){
        return $this->stat->fetchAll();
    }

    /**
     * @param array $columns
     * @return Select
     */
    public function select($columns=['*']){
        return new Select($this, $columns);
    }

    /**
     * @param $table
     * @return Insert
     */
    public function insert($table){
        return new Insert($this,$table);
    }

    /**
     * @param $table
     * @return Update
     */
    public function update($table){
        return new Update($this,$table);
    }

    /**
     * @param $table
     * @return Delete
     */
    public function delete($table){
        return new Delete($this, $table);
    }
}