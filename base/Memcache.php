<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/7
 * Time: 下午7:02
 */
namespace see\base;


class Memcache extends Object
{
    public $servers;

    /**
     * @var \Memcache
     */
    public $memcached;

    /**
     * 前缀
     */
    public $prefix;

    /**
     * 设置
     */
    public $options = [];
    public function init(){
        $this->memcached =  new \Memcache();
        $this->memcached->addServer($this->servers[0],$this->servers[1]);
    }
    
    public function set($key, $value, $expiration=0){
        $key = !empty($this->prefix) ? $this->prefix.$key : $key;
        return $this->memcached->set($key,$value,0, $expiration);
    }
    
    public function get($key){
        $key = !empty($this->prefix) ? $this->prefix.$key : $key;
        $result = $this->memcached->get($key);
        return $result;
    }
}