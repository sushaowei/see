<?php

namespace see\web;

use see\base\Object;

class UrlManager extends Object
{
    public $routeParam = 'r';

    public $pretty =false;

    public $showScriptFile = true;

    public $suffix = "";

    public $rule;

    private $_route;

    /**
     * @param $request Request
     * @return array
     */
    public function parseRequest(){
        $request = \See::$app->getRequest();
        $pathInfo = $request->getPathInfo();
        if($result = $this->parseRequestByRule($pathInfo['path'])){
            $_GET = array_merge($result[1],$_GET);
            return $result;
        }
        $param = [];
        if($this->_route !== null){
            $route = $this->_route;
        }elseif($this->pretty == true){
            $scriptFile = $request->getScriptUrl();
            \See::$log->debug("path:%s", $pathInfo['path']);
            $route = $pathInfo['path'];
            if(strpos( $route, $scriptFile) !== false){
                $route = substr($route, strlen($scriptFile));
            };


            \See::$log->debug("parse route: %s ", $route );
            if($this->suffix){
                \See::$log->debug("suffix:%s", $this->suffix);
                if(strrpos($route, $this->suffix) !== false){
                    $route = substr($route, 0, -1*strlen($this->suffix));
                }
            }
            $route = trim($route, '/.');
            $param = $request->getQueryParams();
        }else{
            $route = $request->get($this->routeParam, '');
        }
        return [$route, $param];
    }

    public function setRoute($route){
        $this->_route = $route;
    }

    private function parseRequestByRule($uri){
        if(!empty($this->rule)){
            foreach($this->rule as $pattern=>$value){
                $rule = new UrlRule($pattern,$value);
                $result = $rule->matchUrl($uri);
                if($result){
                    return $result;
                }
            }
        }
        return false;
    }

    public function createUrl(array $params){
        $request = \See::$app->getRequest();
        $scriptUrl = $request->getScriptUrl();
        $route = $params[0];
        unset($params[0]);
        $anchor = isset($params['#']) ? '#' . $params['#'] : '';
        unset( $params['#']);

        //匹配规则
        if($result = $this->createUrlByRule($route,$params)){
            return trim($result.$anchor,'?');
        }
        if($this->pretty){
            //首页直接返回
            if($route == 'site/index'){
                $url = "/";
            }else{
                $url = $this->showScriptFile ? $scriptUrl : "";
                $url .= DIRECTORY_SEPARATOR . trim($route, '/');
                $url .= $this->suffix ? $this->suffix : '';
            }
            if(!empty($params) && ($query = http_build_query($params))){
                $url .= '?'. $query;
            }
            return trim($url . $anchor, '?');
        }else{
            $url = "$scriptUrl?{$this->routeParam}=" . urlencode($route);
            if(!empty($params) && ($query = http_build_query($params))){
                $url .= '&'. $query;
            }
            return trim($url . $anchor,"?");
        }
    }

    private function createUrlByRule($route,$param){

        if(!empty($this->rule)){
            foreach($this->rule as $pattern=>$value){
                if(!empty($value['route']) && $route == $value['route']){
                    $tmp = explode('/',$route);
                    if(strpos($tmp[1],'-')!==false){
                        $tmpArr = explode('-',$tmp[1],2);
                        $tmp[1] = $tmpArr[0].ucfirst($tmpArr[1]);
                    }
                    if(count($tmp) ==2){
                        $methodName = "rule".ucfirst($tmp[0]).ucfirst($tmp[1]);
                        if(method_exists($this,$methodName)){
                            list($value,$param) = call_user_func_array([$this,$methodName],[$value,$param]);
                        }
                    }
                }
                $rule = new UrlRule($pattern,$value);
                $result = $rule->createUrl($route,$param);
                if($result){
                    return $result;
                }
            }
        }
        return false;
    }

}
