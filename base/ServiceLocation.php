<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/4
 * Time: 下午5:34
 */
namespace see\base;

use see\exception\ErrorException;

class ServiceLocation extends Object
{
    private $components = [];
    
    private $definitions = [];
    
    public function get($id){
        if(isset($this->components[$id])){
            return $this->components[$id];
        }
        
        if(isset($this->definitions[$id])){
            if(is_object($this->definitions[$id]) && !$this->definitions instanceof \Closure){
                return $this->components[$id] = $this->definitions[$id];
            }else{
                return $this->components[$id] = $this->createObject($this->definitions[$id] );
            }
        }else{
            throw new \ErrorException("Unknown component ID:'{$id}'");
        }
    }
    
    public function set($id, $definitions){
        if($definitions === null){
            $this->clear($id);
        }
        if(is_object($definitions) || is_callable($definitions, true)){
            $this->definitions[$id] = $definitions;
        }elseif (is_array($definitions)){
            if(isset ($definitions['class'])){
                $this->definitions[$id] = $definitions;
            }else{
                throw new ErrorException("The configuration for the \"{$id}\" component must contain a 'class' element");    
            }
        }else{
            throw new  ErrorException("Unexpected configuration for thd '$id' component");
        }
    }

    public function clear($id){
        unset($this->components[$id], $this->definitions[$id]);
    }
    
    public function createObject($type, $params=[]){
        if(is_string($type) && class_exists($type)){
            $reflection= new \ReflectionClass($type);
            return $reflection->newInstanceArgs($params);
        }elseif(is_array($type) && isset($type['class'])){
            $class = $type['class'];
            unset($type['class']);
            $params[] = $type;
            $reflection= new \ReflectionClass($class);
            return $reflection->newInstanceArgs($params);
        }elseif(is_callable($type, true)){
            // return call_user_func_array($type,$params);
            return $type;
        }elseif(is_array($type)){
            throw new ErrorException("The configuration must contain a 'class' elment");
        }else{
            throw new ErrorException("Unsupported configuration type: ".gettype($type));
        }
    }

    public function has($id, $checkInstance = false)
    {
        return $checkInstance ? isset($this->components[$id]) : isset($this->definitions[$id]);
    }
}