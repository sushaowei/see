<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/6
 * Time: 下午2:38
 */
namespace see\base;

use see\db\PdoMysql;
use see\db\query\Query;
use see\db\query\Select;
use see\exception\ErrorException;

class Model extends Object
{
    /**
     * 表字段名
     * @var array
     */
    public $attributes=[];

    /**
     * 表主键
     * @var string
     */
    public $primaryKey;

    /**
     * model 属性值
     * @var array
     */
    public $values =[];

    /**
     *  是否新记录
     * @var bool
     */
    public $isNewRecord = false;

    /**
     * @var Query 查询对象
     */
    public $query;
    
    /**
     * @var PdoMysql
     */
    public $db;

    public static $tableDesc = [];
    
    public function init()
    {
        $this->db = \See::$app->getDb();
    }

    /**
     * 表名
     * @return string
     */
    public function tableName(){
        return null;
    }

    /**
     * 初始化表结构
     */
    public function ensureTable(){
        if($this->tableName() && empty($this->attributes)){
            foreach(static::descTable($this->tableName(),$this->db) as $row){
                if($row['Key'] == 'PRI'){
                    $this->primaryKey = $row['Field'];
                }
                $this->attributes[] = $row['Field'];
            }
        }
    }

    /**
     * 根据主键查询一条数据
     * @param $id
     * @return mixed
     * @throws ErrorException
     */
    public function findOne($id){
        $this->ensureTable();
        if($this->primaryKey == null){
            throw new ErrorException("the primaryKey is empty, model:". get_called_class());
        }
        if($id === null){
            return [];
        }
        $where = [$this->primaryKey => $id];
        $this->query = $this->db->select()->from($this->tableName());
        $result = $this->query->where($where)->one();
        $this->load($result, false);
        return $result;
    }

    /**
     * 载入参数
     * @param $values
     * @param bool $newRecord
     * @return $this
     */
    public function load($values, $newRecord = true){
        $this->ensureTable();
        $this->isNewRecord = $newRecord;
        $this->values = [];//清除原有值
        foreach($this->attributes as $column){
            if(isset($values[$column])){
                $this->values[$column] = $values[$column];
            }
        }
        return $this;
    }

    /**
     * 插入一条数据
     * @param array $values
     * @return int
     */
    public function add($values=[]){
        $this->ensureTable();
        if(!empty($values)){
            $this->load($values, false);
        }
        $this->query =  $this->db->insert($this->tableName());
        $lastInsertId = $this->query->values($this->values)->execute();
        $this->values[$this->primaryKey] = $lastInsertId;
        return intval($lastInsertId);
    }

    /**
     * 保存一条数据
     * @param array $values
     * @return bool|int
     */
    public function save($values=[]){
        $this->ensureTable();
        if(!empty($values)){
            $this->load($values, false);
        }
        if(isset($this->values[$this->primaryKey])){
            $where = [$this->primaryKey=>$this->values[$this->primaryKey]];
            $values = $this->values;
            unset($values[$this->primaryKey]);
            $this->query = $this->db->update($this->tableName());
            return $this->query->where($where)->values($values)->execute();
        }
        return false;
    }

    /**
     * 创建查询
     * @param array $columns
     * @param string $as
     * @return Query|Select
     */
    public function find(array $columns=['*'], $as=null){
        if ($as){
            $asStr = " as {$as}";
        }else{
            $asStr = "";
        }
        return $this->db->select($columns)->from($this->tableName().$asStr);
    }
    
    
    public function label($column, $value=null){
        $key = 'label'.ucfirst($column);
        if(!isset($this->$key)){
            return null;
        }

        if($value === null){
            if(isset($this->values[$column])){
                $value = $this->values[$column];
            }else{
                return null;
            }
        }
        $arr = $this->$key;
        if(isset($arr[$value])){
            return $arr[$value];
        }
        return null;
    }

    //表结构
    public static function descTable($tableName,$db){
        if(isset(static::$tableDesc[$tableName])){
            return static::$tableDesc[$tableName];
        }
        $sql = "desc ".$tableName;
        $stat = $db->query($sql);
        return static::$tableDesc[$tableName] = $stat->fetchAll();
    }
}