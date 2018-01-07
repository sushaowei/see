<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/11 0011
 * Time: 上午 12:59
 */

namespace see\web;

use see\exception\NotFoundException;
use see\exception\EventException;
use see\event\Event;
/**
 * Class Application
 * @package see\web
 * @property UrlManager @urlManager
 */
class Application extends \see\base\Application
{
    public $defaultRoute = 'site';

    /**
     */
    public function handleRequest($request)
    {
        $response = $this->getResponse();
        $request = $this->getRequest();

        \See::$log->addBasic('clientIp', $request->getRealUserIp());

        $parts = $request->resolve();

        list($route,$params) = $parts;
        $this->requestedRoute = $route;

        \See::$log->addBasic('route', $this->requestedRoute);

        $result = $this->runAction($route, $params);

        if ($request instanceof Response) {
            $response = $result;
        } else {
            $response->data = $result;
        }
        $response->setStatusCode(200);

    }

    /**
     * @return \see\web\UrlManager
     */
    public function getUrlManager()
    {
        return $this->get('urlManager');
    }

    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'urlManager' => ['class' => '\see\web\UrlManager'],
            'request' => ['class' => '\see\web\Request'],
            'response' => ['class' => '\see\web\Response'],
        ]);
    }
     //event eventHandler
    public function eventHandlerInit(){
        $moduleDefault = $this->namespace . '\\events\\DefaultHandler';
        if(class_exists($moduleDefault)){
            $events = array_merge(['ModuleDefault'=>$moduleDefault],$this->events);
        }else{
            $events = $this->events;
        }
        foreach($events as $k=>$v){
            if(!$this->has($k,true)){
                $eventHandler = $this->createObject($v);
                $this->set("see_event_".$k,$eventHandler);
            }
        }
    }

}