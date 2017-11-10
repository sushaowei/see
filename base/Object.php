<?php
/**
 * 框架基础类
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/4
 * Time: 下午5:10
 */
namespace see\base;
class Object
{
    
    public function __construct($config=[])
    {
        if(!empty($config)){
            \See::configure($this,$config);
        }
        $this->init();
    }
    
    public function init(){}

    public function className(){
        return get_called_class();
    }
    
}