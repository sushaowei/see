<?php
namespace see\event;
abstract class EventHandler
{
	protected static $eventName;
	protected static $eventClass;

	//set route callable 
	public static function on(){
		list($requestedRoute,$_) = \See::$app->getRequest()->resolve();
		$parts = static::resolve(func_get_args());
		if($parts !== false){
			list($route,$handler,$append) = $parts;
			$add = false;
			if(is_string($route)){
				$add = self::match($route,$requestedRoute);
			}elseif(is_array($route)){
				foreach($route as $routePattern){
					$add = self::match($routePattern,$requestedRoute);
					if($add == true){
						break;
					}
				}
			}
			if($add){
				Event::on(static::$eventClass, static::$eventName, $handler, null, $append);
			}
		}
		
	}
    //set route callable
    public static function on_(){
        list($requestedRoute,$_) = \See::$app->getRequest()->resolve();
        $parts = static::resolve(func_get_args());
        if($parts !== false){
            list($route,$handler,$append) = $parts;
            $add = true;
            if(is_string($route)){
                $add = !self::match($route,$requestedRoute);
            }elseif(is_array($route)){
                foreach($route as $routePattern){
                    $add = self::match($routePattern,$requestedRoute);
                    if($add == true){
                        $add = false;
                    }
                }
            }
            if($add){
                Event::on(static::$eventClass, static::$eventName, $handler, null, $append);
            }
        }

    }
	// remove route event 
	public static function off($handler=null){
		Event::off(static::$eventClass, static::$eventName,$handler);
	}
	//resolve args
	public static function resolve($args){
		$result = ["",null,true];
		$count = count($args);
		if($count > 0 ){
			foreach($args as $v){
				if(is_callable($v)){
					$result[1] = $v;
				}elseif(is_string($v) || is_array($v)){
					$result[0] = $v;
				}elseif(is_bool($v)){
					$result[2] = $v;
				}
			}
		}
		if($result[1] !== null){
			return $result;
		}else{
			return false;
		}

	}
	//判断一个route是否匹配
	private static function match($routePattern,$route){
		if($routePattern == '*' || empty($routePattern)){
			return true;
		}
		if(preg_match("/^#.*#$/",$routePattern)){
			//正则表达式
			if(preg_match($routePattern, $route)){
				return true;
			}
		}elseif(is_string($routePattern)){
			return $routePattern == $route;
		}
		return false;
	}
}