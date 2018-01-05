<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/10 0010
 * Time: 下午 9:10
 */

namespace see\web;
use see\event\Event;
use see\exception\NotFoundException;

/**
 * Controller 的 Action 对象
 * Class Action
 * @package see\base
 */
class Action extends \see\base\Action
{
    // run
    public function runWithParams($params){
        $args = $this->controller->bindActionParams($this, $params);
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

    
}