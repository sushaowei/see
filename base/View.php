<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/10 0010
 * Time: 下午 9:05
 */

namespace see\base;
use see\exception\ErrorException;
use see\event\Event;

/**
 * 模板对象
 * Class View
 * @package see\base
 */
class View extends Object 
{
    public $params = [];

    public $defaultExtension = 'php';

    public $renderers;

    public $controller;
    
    public function findViewFile($view){
        if(strncmp($view, '@',1) === 0){
            $file = \See::getAlias($view);
        }elseif (strncmp($view, '//', 2) ===0) {
            $file = \See::$app->getViewPath() . DIRECTORY_SEPARATOR. ltrim($view, '/');
        }elseif (strncmp( $view, '/', 1 ) === 0) {
            if (\See::$app->controller !== null) {
                $file = \See::$app->controller->module->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
            } else {
                $file = \See::$app->getViewPath() . DIRECTORY_SEPARATOR. ltrim($view, '/');
            }
        }elseif(strpos($view, '/') === false){
            if (\See::$app->controller !== null){
                $file = \See::$app->controller->getViewPath() . DIRECTORY_SEPARATOR . $view;
            }else{
                throw new ErrorException("Unable to resolve view file for view '{$view}' : no active controller");
            }
        }else{
            throw new ErrorException("Unable to resolve view file for view '{$view}' ");
        }
        
        $path = $file . '.' . $this->defaultExtension;
        if(!file_exists($path)){
            throw new ErrorException("file not exists:'{$path}'");
        }
        return $path;
    }

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

    /**
     * get uniqueId controller/action
     * @return null|string
     */
    public function getUniqueId(){
        if(\See::$app->controller !== null && \See::$app->controller->action !== null){
            return \See::$app->controller->action->getUniqueId();
        }
        return null;
    }

    public function assign($key,$value){
        $this->params[$key] = $value;
    }
}