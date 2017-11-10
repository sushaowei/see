<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/6
 * Time: 上午11:49
 */
namespace see\db\query;

use see\db\PdoMysql;
use see\exception\ErrorException;

/**
 * Class Query
 * @package see\db\query
 */
class Query
{
    /**
     * @var PdoMysql
     */
    public $db;

    /**
     * @var string
     */
    public $table;

    /**
     * @var array
     */
    public $whereContent = [];

    /**
     * @var array
     */
    public $whereValues = [];

    /**
     * @var string
     */
    public $joinContent = '';

    /**
     * @var string
     */
    public $groupContent = '';

    /**
     * @var array
     */
    public $orderContent = [];

    /**
     * @var string
     */
    public $limitContent='';

    /**
     * @var array
     */
    public $values=[];

    /**
     * @var \PDOStatement
     */
    public $statement;
    
    public $count;

    /**
     * @param array $params
     * @param string $operator
     * @param string $chainType
     * @return $this|Select
     */
    public function where(array $params, $operator='=', $chainType='and'){
        foreach($params as $key=>$value){
            $this->whereContent[] = $chainType .' '. $key .' '. $operator . ' ?';
            $this->whereValues[] = $value;
        }
        return $this;
    }

    /**
     * @param array $params
     * @param string $operator
     * @param string $chainType
     * @return $this
     */
    public function filterWhere(array $params, $operator='=', $chainType='and'){
        foreach($params as $key=>$value){
            if($value !== null){
                $this->whereContent[] = $chainType .' '. $key .' '. $operator . ' ?';
                $this->whereValues[] = $value;
            }
        }
        return $this;
    }
    /**
     * @return string
     */
    public function getWhere (){
        if(!empty($this->whereContent)){
            $str = implode(' ', $this->whereContent);
            $str = trim($str,' ');
            if(substr_compare($str, "and",0,3) == 0){
                $str = substr($str, 3);
            }
            if(substr_compare($str, "or",0,2) == 0){
                $str = substr($str, 2);
            }
            
            $str = 'where '. trim($str);
            return $str;
        }
        return '';
    }

    /**
     * @param $column
     * @param $begin
     * @param $end
     * @return $this
     */
    public function betweenWhere($column, $begin, $end, $chainType = 'and'){
        $this->whereContent[] = $chainType . ' ' .$column . ' between ? and ?';
        $this->whereValues[] = $begin;
        $this->whereValues[] = $end;
        return $this;
    }

    /**
     * @param $str
     * @param array $value
     * @return $this
     */
    public function strWhere($str, array $value=[]){
        $this->whereContent [] =$str;
        $this->whereValues = array_merge($this->whereValues, $value);
        return $this;
    }

    /**
     * @param $str
     * @return $this
     */
    public function join($str){
        $this->joinContent .= ' '.$str;
        return $this;
    }

    /**
     * @return string
     */
    public function getJoin(){
        return $this->joinContent;
    }

    /**
     * @param $str
     * @return $this
     */
    public function group($str){
        $this->groupContent .= ' '.$str;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroup(){
        if(empty($this->groupContent)){
            return '';
        }
        return 'group by'.$this->groupContent;
    }

    /**
     * @param $columns
     * @param bool $asc
     * @return $this
     */
    public function order($columns, $asc=true){
        $this->orderContent[] = [$columns, $asc];
        return $this;
    }

    /**
     * @return string
     */
    public function getOrder(){
        if(empty($this->orderContent)){
            return '';
        }
        $str = 'order by ';
        foreach ($this->orderContent as $v){
            $str .= $v[0] . ' '. ($v[1]? 'asc' : 'desc') . ',';
        }
        $str = trim($str,', ');
        return $str;
    }

    /**
     * @param $offset
     * @param $size
     * @return $this
     */
    public function limit($offset, $size){
        $this->limitContent = 'limit '.$offset.', '.$size;
        return $this;
    }

    /**
     * @return string
     */
    public function getLimit(){
        return $this->limitContent;
    }

    /**
     * @return array
     */
    public function getValues(){
        return array_merge($this->values, $this->whereValues);
    }

    /**
     *
     */
    public function clearLimit(){
        $this->limitContent = '';
    }

    /**
     * build sql
     */
    public function buildSql(){
        $sql = (string)$this;
        $value  = $this->getValues();
        $result = "";
        $t = 0;
        for($i=0; $i<strlen($sql);$i++){
            if($sql[$i]=='?'){
                if(isset($value[$t])){
                    $result .= "'{$value[$t]}'";
                    $t++;
                }else{
                    throw new ErrorException("the sql value count error, sql: $sql, value:".serialize($value));
                }
            }else{
                $result .= $sql[$i];
            }
        }
        return $result;
    }
}