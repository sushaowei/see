<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/6
 * Time: 上午11:14
 */
namespace see\db\query;

use see\db\PdoMysql;

/**
 * Class Update
 * @package see\db\query
 */
class Update extends Query
{
    /**
     * @var array
     */
    public $columns = [];

    /**
     * Update constructor.
     * @param $db PdoMysql
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
    public function values(array $params){
        foreach($params as $key=>$values){
            $this->columns[] = '`'.$key.'`' . ' =?';
            $this->values[] = $values;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getColumns(){
        return ' '. implode(', ', $this->columns) . ' ';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // TODO: Implement __toString() method.
        if(!empty($this->table) && !empty($this->columns) && !empty($this->values)){
            $str  = 'update '.$this->table;
            $str .= ' set '.$this->getColumns();
            $str .= ' '.$this->getWhere();
            return $str;
        }else{
            trigger_error("update table, columns, values must not to be empty");
        }
    }

    /**
     * @return int
     */
    public function execute(){
        return $this->db->query($this,$this->getValues());
    }
}
