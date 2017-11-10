<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/6
 * Time: 上午10:44
 */
namespace see\db\query;

use see\db\PdoMysql;

/**
 * Class Select
 * @package see\db\query
 */
class Select extends Query
{

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * use index
     */
    public $useIndex;

    /**
     * ignore index
     */
    public $ignoreIndex;

    public $forceIndex;

    /**
     * @var bool
     */
//    protected $count = false;
    /**
     * Select constructor.
     * @param $db PdoMysql
     * @param array $columns
     */
    public function __construct($db, $columns=["*"])
    {
        $this->db = $db;
        $this->columns = $columns;
    }

    /**
     * @param $table
     * @return $this
     */
    public function from($table){
        $this->table = $table;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if($this->table === null){
            trigger_error("The query table must not to be null");
        }else{
            $index = '';
            if($this->forceIndex){
                $index = " force index({$this->forceIndex})";
            }elseif($this->useIndex){
                $index = " use index({$this->useIndex})";
            }elseif($this->ignoreIndex){
                $index .= " ignore index({$this->ignoreIndex})";
            }
            $str  = "select ".$this->getColumns(); 
            $str .= " from ".$this->table;
            $str .= $index;
            $str .= ' '.trim($this->getJoin());
            $str =  trim($str).' '.trim($this->getWhere());
            $str = trim($str).' '.trim($this->getGroup());
            if(!$this->count){
                $str = trim($str).' '.trim($this->getOrder());
                $str = trim($str).' '.trim($this->getLimit());
            }
            return trim($str);
        }
    }

    /**
     * @return bool|\PDOStatement
     */
    public function execute(){
        return $this->statement = $this->db->query($this, $this->getValues());
    }


    /**
     * @return array
     */
    public function all(){
        $this->statement = $stat = $this->db->query($this,$this->getValues());
        return $stat->fetchAll();
    }

    /**
     * @return mixed
     */
    public function one(){
        $this->limit(0,1);
        $stat = $this->db->query($this,$this->getValues());
        $this->statement = $stat;
        return $stat->fetch();
    }

    /**
     * @return int
     */
    public function count(){
        $this->count = true;
        $stat = $this->db->query($this, $this->getValues());
        $result = $stat->fetch();
        $this->count = false;
        return intval($result['num']);
    }
    
    /**
     * @return string
     */
    public function getColumns(){
        if($this->count){
            return "count(*) as num";
        }else{
            return implode(", ", $this->columns);
        }
    }
    /**
     * use index
     * @return $this
     */
    public function useIndex($str){
        $this->useIndex = trim($str);
        return $this;
    }

    /**
     * ignore index
     * @return $this
     */
    public function ignoreIndex($str){
        $this->ignoreIndex = trim($str);
        return $this;
    }


    /**
     * force index
     * @return $this
     */
    public function forceIndex($str){
        $this->forceIndex = trim($str);
        return $this;
    }
}