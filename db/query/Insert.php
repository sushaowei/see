<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/6
 * Time: 上午10:54
 */

namespace see\db\query;

use see\db\PdoMysql;

/**
 * Class Insert
 * @package see\db\query
 */
class Insert extends Query
{
    /**
     * @var PdoMysql
     */
    public $db;

    /**
     * @var
     */
    public $table;

    /**
     * @var array
     */
    public $columns = [];

    /**
     * @var array
     */
    public $placeholders = [];

    /**
     * Insert constructor.
     * @param $db
     * @param $table
     */
    public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function values (array $params){
        foreach($params as $key=>$values){
            $this->columns[] = '`'.$key.'`';
            $this->values[] = $values;
            $this->placeholders[] = "?";
        }
        return $this;
    }

    /**
     * @return string
     */
    public function execute(){
        $this->db->query($this, $this->getValues());
        return $this->db->lastInsertId();
    }


    /**
     * @return string
     */
    public function __toString()
    {
        if($this->table === null){
            trigger_error("The query table must not to be null");
        }else{
            $str  = 'insert into '. $this->table;
            $str .= $this->getColumns();
            $str .= "values";
            $str .= $this->getPlaceholders();
            $str .= ' '.$this->getWhere();
            return $str;
        }
    }

    /**
     * @return string
     */
    public function getColumns(){
        return ' ('. implode(', ', $this->columns) . ') ';
    }

    /**
     * @return string
     */
    public function getPlaceholders(){
        return ' ('. implode(', ', $this->placeholders) . ') ';
    }
}