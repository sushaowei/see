<?php
namespace see\web;

class UrlRule {
    public $urlPattern;
    public $route;
    private $urlTemplate;
    private $isPreg = false;
    private $default = [];

    public function __construct($pattern, $rule)
    {
        if(is_string($rule)){
            $this->urlPattern = $pattern;
            $this->route = $rule;
        }else{
            $this->isPreg = true;
            $this->route = $rule['route'];
            if(preg_match_all("/<([\w._-|\/]+):?([^>]+)?>/",$rule['pattern'], $matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER)){
                $tr = [];
                foreach($matches as $match){
                    $name = $match[1][0];
                    $pattern_p = isset($match[2][0]) ? $match[2][0] : '[^\/]+';
                    $tr["<$name>"] = "(?P<$name>$pattern_p)";
                }
                $template = preg_replace('/<([\w._-]+):?([^>]+)?>/', '<$1>', $rule['pattern']);
                $this->urlPattern = '#^' . trim(strtr($template, $tr), '/') . '$#u';
            }else{
                $this->urlPattern = '#^' . trim($rule['pattern'], '/') . '$#u';
            }
            $this->urlTemplate = $rule['urlTemplate'];
            $this->default = isset($rule['default'])? $rule['default'] : [];
        }

    }


    public function matchUrl($url){
        $route = "";
        $param = [];
        $url = trim($url,'/');
        if($url == trim($this->urlPattern,'/')){
            $route = $this->route;
            return [$route,$param];
        }elseif($this->isPreg){
            if(preg_match_all($this->urlPattern,$url, $matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER)){
                $route = $this->route;
                foreach($matches[0] as $k=>$match){
                    if(is_string($k)){
                        $param[$k] = $match[0];
                    }
                }
                return [$route,$param];
            }
        }
        return false;
    }

    public function createUrl($route,$param){
        $url= "";
        if($route == $this->route){
            //直接字符
            if(!$this->isPreg){
                $url =  $this->urlPattern;
            }else{
                $tr = [];
                if(preg_match_all("/<([\w._-|\/]+)?>/",$this->urlTemplate, $matches,PREG_OFFSET_CAPTURE | PREG_SET_ORDER)){
                    foreach($matches as $match){
                        if(!empty($match[1][0])){
                            $name = $match[1][0];
                            $tr["<$name>"] = isset($param[$name]) ? $param[$name] :"";
                            if(!isset($param[$name])){
                                $tr["<$name>"] = isset($this->default[$name]) ? $this->default[$name] :"";
                            }else{
                                unset($param[$name]);
                            }
                        }
                    }
                }
                $url = strtr($this->urlTemplate ,$tr);
                $url = preg_replace("#/+#","/",$url);
            }
        }
        if(!empty($url)){
            $url = "/".ltrim($url,'/');
        }
        if($url && !empty($param)){
            $str = http_build_query($param);
            $url .= '?'.$str;
        }
        return trim($url);
    }
}
