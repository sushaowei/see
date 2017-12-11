<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/9 0009
 * Time: 下午 9:21
 */

namespace see\base;
use see\exception\NotFoundException;
/**
 * 控制器基础类
 * Class Controller
 * @package see\base
 */
class Controller extends Object
{

    public $id;

    /**
     * @var Module
     */
    public $module;

    public $defaultAction = 'index';
    /**
     * @var Action
     */
    public $action;

    private $_view;

    private $_viewPath;


    public function __construct($id, $module, array $config = [])
    {
        $this->id = $id;
        $this->module = $module;
        parent::__construct($config);
    }
    //run action
    public function runAction($id, $params = [])
    {
        $action = $this->createAction($id);
        if ($action === null) {
            throw new NotFoundException('Action error, Unable to resolve the request: ' . $this->getUniqueId() . DIRECTORY_SEPARATOR . $id);
        }

        if (\See::$app->requestedAction === null) {
            \See::$app->requestedAction = $action;
        }
        $this->action = $action;
        $result = $action->runWithParams($params);
        return $result;
    }
    //create action
    public function createAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }
        $methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
        if (method_exists($this, $methodName)) {
            $reflection = new \ReflectionMethod($this, $methodName);
            if ($reflection->isPublic() && $reflection->getName() == $methodName) {
                return new Action($id, $this, $methodName);
            }
        }
        return null;
    }
    //获取模板服务
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = \See::$app->getView();
        }
        return $this->_view;
    }
    //获取模板路径
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            $this->_viewPath = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
        }
        return $this->_viewPath;
    }
    //设置模板路径
    public function setViewPath($path)
    {
        $this->_viewPath = \See::getAlias($path);
    }

    public function getUniqueId()
    {
        return $this->module instanceof Application ? $this->id : $this->module->getUniqueId() . DIRECTORY_SEPARATOR . $this->id;
    }

    public function getRoute()
    {
        return $this->action !== null ? $this->action->getUniqueId() : $this->getUniqueId();
    }
    
    //render view
    public function render($view, $params = [])
    {
        return $this->getView()->render($view, $params,$this);
    }
    // assign
    public function assign($key,$value){
        $this->getView()->assign($key,$value);
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