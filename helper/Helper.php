<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/5
 * Time: 下午4:46
 */
namespace see\helper;


class Helper{
    //url 生成
    public static function to(array $params = []){
        return \See::$app->getUrlManager()->createUrl($params);
    }
    
    //过滤null参数,保留其它get参数,生成url
    public static function filterGetTo($route, $params){
        $get = \See::$app->getRequest()->getQueryParams();
        unset($get['r']);
        $data = [];
        $data[0] = $route;
        $params = array_merge($data, $get, $params);
        foreach($params as $k=>$v){
//            $params[$k] = urldecode($v);
            if($v === null){
                unset($params[$k]);
            }
        }
        return self::to($params);
    }
    public static function compareQueryParam($values, $return=true){
        $request = \See::$app->getRequest();
        foreach($values as $k=>$v){
            $param = $request->get($k, null);
            if( $param != $v){
                return false;
            }
        }
        return $return;
    }
    //escape html
    public static function escape($text){
        return htmlspecialchars($text);
    }
}