<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/10 0010
 * Time: 下午 9:05
 */

namespace see\web;
use see\exception\ErrorException;
use see\event\Event;

/**
 * 模板对象
 * Class View
 * @package see\base
 */
class View extends \see\base\View
{
    /**
     * render view
     * @param $view
     * @param null $controller
     * @param array $params
     * @return string
     */
    public function render($view,  $params=[],$controller=null){
        $this->controller = $controller;
        $this->params = array_merge($this->params,$params);
        //触发beforeAction 事件
        $event = new Event();
        $event->sender = $this;
        Event::trigger($this,'BeforeRender',$event);

        $viewFile = $this->findViewFile($view);
        
        $ext = pathinfo($viewFile, PATHINFO_EXTENSION);
        if (isset($this->renderers[$ext])) {
            if (is_array($this->renderers[$ext]) || is_string($this->renderers[$ext])) {
                $this->renderers[$ext] = \See::createObject($this->renderers[$ext]);
            }
            $renderer = $this->renderers[$ext];
            /* @var $renderer \see\base\ViewRenderInterface */
            $output = $renderer->render($this, $viewFile, $this->params);
        } else {
            ob_start();
            ob_implicit_flush(false);
            extract($params);
            require($viewFile);
            $output = ob_get_clean();
        }
        return $output;
    }
}