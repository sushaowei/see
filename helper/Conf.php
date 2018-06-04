<?php
/**
 * Created by PhpStorm.
 * User: sushaowei
 * Date: 2018/5/30
 * Time: 下午4:58
 */
class Conf
{
    public static $conf;
    public static $init = false;

    /**
     * 初始化
     */
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

    /**
     * 获取某个变量
     */
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

    /**
     * 获取项目所有变量
     */
    public static function getProject($project,$environment){
        if(self::$init === false){
            trigger_error("Conf has not init",E_USER_ERROR);
        }

        if(isset(self::$conf[$project])){
            $result = [];
            $params = self::$conf[$project];
            if(is_array($params)){
                foreach($params as $k=>$e){
                    if(isset($params[$k][$environment])){
                        $result[$k] = $params[$k][$environment];
                    }
                }
            }
            return $result;
        }else{
            trigger_error("the project is not exists:{$project}",E_USER_ERROR);
        }

    }
}