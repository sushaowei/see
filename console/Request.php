<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/11 0011
 * Time: 上午 1:01
 */

namespace see\console;

use see\base\Object;

class Request extends Object
{
    public function resolve($argv){
        $route = isset($argv[1]) ? $argv[1] : "";
        $param = array_slice($argv,2);
        return [$route,$param];
    }

    public function get($k,$v){
        return null;
    }
}