<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/10 0010
 * Time: 下午 9:10
 */

namespace see\base;
use see\event\Event;
/**
 * Controller 的 Action 对象
 * Class Action
 * @package see\base
 */
class Action extends Object
{

    public $id;
    /**
     * @var Controller
     */
    public $controller;
    //方法
    public $actionMethod;
    
    public function __construct($id, $controller, $methodName, array $config=[])
    {
        $this->id = $id;
        $this->controller = $controller;
        $this->actionMethod = $methodName;
        parent::__construct($config);
    }
    
    public function getUniqueId(){
        return $this->controller->getUniqueId() . DIRECTORY_SEPARATOR . $this->id;
    }
    // run 
    public function runWithParams($params){
        $args = $this->bindActionParams($this, $params);
        if(\See::$app->requestedParams === null){
            \See::$app->requestedParams = $args;
        }
        \See::$app->requestedRoute = $this->getUniqueId();

        //触发beforeAction 事件
        $event = new Event();
        $event->sender = $this;
        Event::trigger($this,'BeforeAction',$event);

        return call_user_func_array([$this->controller, $this->actionMethod], $args);
    }

    //绑定参数
    public function bindActionParams($action, $params)
    {
        $reflection = new \ReflectionMethod($action->controller, $action->actionMethod);
        $arg = $reflection->getParameters();
        $result = [];
        foreach ($arg as $parameter){
            $name = $parameter->getName();
            if($parameter->isDefaultValueAvailable()){
                $value = $parameter->getDefaultValue();
            }
            if(isset($params[$name])){
                $value = $params[$name];
            }
            if(!isset($value)){
                $className = (new \ReflectionClass($action->controller))->getName();
                throw new NotFoundException("action argv error, controller: $className, action: $action->actionMethod, not set arv: $name", 1);
            }
            $result[] = $value;
        }
        return $result;
    }
}