<?php
/**
 * Created by PhpStorm.
 * User: sushaowei
 * Date: 2018/5/30
 * Time: 下午4:58
 */

namespace see\helper;


class Conf
{
    public static $conf;
    public static $init = false;

    public static function initConf($file){
        if(self::$init ===false){
            if(file_exists($file)){
                $str = @file_get_contents($file);
                self::$conf = json_decode($str,true);
                self::$init = true;
            }else{
                trigger_error("cat not found the file: {$file}",E_USER_ERROR);
            }
        }
    }

    public static function get($key,$project,$environment){
        if(self::$init === false){
            trigger_error("Conf has not init",E_USER_ERROR);
        }
        if(isset(self::$conf[$project][$key][$environment])){
            return self::$conf[$project][$key][$environment];
        }else{
            trigger_error("the key is not exists:{$key}",E_USER_ERROR);
        }
    }
}