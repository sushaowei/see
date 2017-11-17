<?php
/**
 * 查询生成器
 */
namespace see\helper;

use see\base\Object;
use see\db\query\Query;
use see\db\query\Select;

/**
 * Class QueryBuilder
 * @package app\helper
 */
class QueryBuilder extends Object
{
    /**
     * @var Query | Select
     */
    public $query;

    /**
     * @var int
     */
    public $pageSize = 20;

    /**
     * @var string
     */
    public $pageSign = 'page';

    /**
     * @var
     */
    public $maxPage;

    /**
     * @var int 当前页码
     */
    public $page;

    /**
     * @var int 上一页码
     */
    public $prevPage;

    /**
     * @var int 下一页码
     */
    public $nextPage;
    
    /**
     * @var
     */
    public $total;

    /**
     * @var
     */
    public $offset;

    /**
     * @var
     */
    public $statement;

    /**
     * @var [] 查询结果
     */
    public $result;

    /**
     * is paged
     */
    public $isPaged=false;

    /**
     *
     */
    public function init(){
        
    }

    /**
     * @param $data
     * @param string $operator
     * @param string $chainType
     * @return $this
     */
    public function filterWhere($data, $operator='=', $chainType = 'and'){
        foreach($data as $key=>$value){
            if(isset($value) && $value !== null){
                $this->query->where([$key=>$value], $operator, $chainType);
            }
        }
        return $this;
    }

    /**
     * @param $data
     * @param string $operator
     * @param string $chainType
     * @return $this|QueryBuilder
     */
    public function andWhere($data, $operator = '=', $chainType ='and'){
        foreach($data as $key=>$value){
            $this->query->where([$key=>$value], $operator, $chainType);
        }
        return $this;
    }

    /**
     * @param $str
     * @return $this
     */
    public function order($str){
        if(empty($str)){
            return $this;
        }
        $asc = (substr_compare($str, '-', 0, 1) == 0) ? false : true;
        $str = trim($str, '- ');
        $this->query->order($str, $asc);
        return $this;
    }

    /**
     * 分页
     * @return $this
     */
    public function page($page=null){
        if($this->isPaged){
            return $this;
        }
        $page = ($page === null) ? \See::$app->getRequest()->get($this->pageSign, 1) : $page;
        $total = $this->query->count();
        $maxPage = ceil($total/$this->pageSize);
        $page = max(1, min($page, $maxPage));
        $offset = ($page-1)*$this->pageSize;
        $this->query->limit($offset, $this->pageSize);
        
        $this->page = $page;
        $this->offset = $offset;
        $this->maxPage = $maxPage;
        $this->total = $total;
        
        $this->prevPage = max(1, $page-1);
        $this->nextPage = min($maxPage, $page+1);
        $this->isPaged = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function execute(){
        $this->page();
        $this->statement = $this->query->execute();
        return $this;
    }

    /**
     * @return $this
     */
    public function all(){
        $this->page();
        if($this->total>0){
            $this->result = $this->query->all();
        }else{
            $this->result=[];
        }
        return $this;
    }

    /**
     * get page info
     */
    public function getPage(){
        $result = [];
        $result['page'] = $this->page;
        $result['total'] = $this->total;
        $result['maxPage'] = $this->maxPage;
        $result['offset'] = $this->offset;
        $result['pageSize'] = $this->pageSize;
        $result['prevPage'] = $this->prevPage;
        $result['nextPage'] = $this->nextPage;
        return $result;
    }

    public function __sleep()
    {
        return ['pageSize','pageSign','maxPage','page','prevPage','nextPage','total','offset','result'];
    }
}