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
     * @param \see\web\Request $request
     * @return mixed|Response|\see\web\Response
     */
    public function handleRequest($request)
    {
        $response = $this->getResponse();
        $request = $this->getRequest();
        try {
            try {
                \See::$log->addBasic('clientIp', $request->getRealUserIp());

                $parts = $request->resolve();
                if($parts === false){
                    throw new NotFoundException("Page not found", 404);
                }
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
            }catch(EventException $e){
                $event = new Event();
                $event->sender = $this;
                $event->e = $e;
                Event::trigger($this,'EventException',$event);
            }
        } catch (NotFoundException $e) {
            $code = $e->getCode();
            $response = \See::$app->getResponse();
            switch ($code) {
                case '301'://重定向
                    $response->setStatusCode(301);
                    break;
                default:
                    $response->setStatusCode(404);
                    if(\See::$app->has('notFound')){
                        $notFound = \See::$app->get('notFound');
                        $notFound($e);
                    }else{
                        $response->notFoundSend($e);
                    }
                    break;
            }
        }

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
            'view' => ['class' => '\see\web\View'],
        ]);
    }

}