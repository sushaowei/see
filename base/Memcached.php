<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/7
 * Time: 下午7:02
 */
namespace see\base;


use see\exception\ErrorException;

class Memcached extends Object
{
    public $servers;

    /**
     * @var \Memcached
     */
    public $memcached;

    /**
     * 前缀
     */
    public $prefix;
    /**
     * 验证
     */
    public $sasl;

    public $on = true;

    /**
     * 连接配置
     */
    protected $config;
    /**
     * 连接
     */
    protected $connect;

    /**
     * 设置
     */
    public $options = [];
    public function init(){
        $this->default = [
            'servers'=>$this->servers,
            'options'=>$this->options,
            'prefix'=>$this->prefix,
            'sasl'=>$this->sasl,
            'on'=>$this->on
        ];

        $this->memcached =  $this->connect($this->default);
        $this->connect['default'] = $this->memcached;
    }

    public function setConnect($cache='default'){
        if(!isset($this->$cache)){
            throw new ErrorException("error memcache config:".$cache);
        }
        $config = $this->$cache;//连接配置
        $this->prefix = isset($config['prefix'])? $config['prefix']:"";
        if(isset($this->connect[$cache])){//连接池中已存在
            $this->memcached = $this->connect[$cache];
        }else{//新创建连接,存入连接池
            $this->memcached = $this->connect($config);
            $this->connect[$cache] = $this->memcached;
        }
    }

    protected function connect($config){
        $memcached = new \Memcached();
        $memcached->addServers($config['servers']);
        if(!empty($config['options'])){
            foreach($config['options'] as $k=>$v){
                $memcached->setOption($k,$v);
            }
        }
        if(!empty($config['sasl']['username']) && isset($config['sasl']['password'])){
            $memcached->setSaslAuthData($config['sasl']['username'],$config['sasl']['password']);
        }
        return $memcached;
    }
    
    public function set($key, $value, $expiration=0){
        $key = !empty($this->prefix) ? $this->prefix.$key : $key;
        return $this->memcached->set($key,$value, $expiration);
    }
    
    public function get($key){
        if($this->on == false){
            return false;
        }
        $key = !empty($this->prefix) ? $this->prefix.$key : $key;
        $result = $this->memcached->get($key);
        if($result === false || $result === \Memcached::RES_NOTFOUND){
            return false;
        }
        return $result;
    }

    public function delete($key){
        return $this->memcached->delete($key);
    }
}