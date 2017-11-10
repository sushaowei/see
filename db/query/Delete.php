<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/6
 * Time: 上午11:44
 */
namespace see\db\query;

use see\db\PdoMysql;

/**
 * Class Delete
 * @package see\db\query
 */
class Delete extends Query
{
    /**
     * Delete constructor.
     * @param $db PdoMysql
     */
    public function __construct($db,$table)
    {
        $this->db= $db;
        $this->table = $table;
    }

    /**
     * @param $table
     * @return $this
     */
    public function from($table){
        $this->table=$table;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // TODO: Implement __toString() method.
        $str  = 'delete from '.$this->table;
        $str .= ' '.$this->getWhere();
        $str .= ' '.$this->getLimit();
        return $str;
    }

    /**
     * @return string
     */
    public function execute(){
        return $this->db->query($this, $this->getValues());
    }
}