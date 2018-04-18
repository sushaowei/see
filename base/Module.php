<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/4
 * Time: 下午6:39
 */
namespace see\base;
use see;
use see\exception\NotFoundException;
use see\exception\ErrorException;
class Module extends ServiceLocation
{
    //module id
    protected $id;
    
    //子模块
    public $modules = [];

    //默认控制器
    public $defaultRoute = 'site';
    
    /**
     * 父模块
     * @var Module
     */
    public $father;
    
    //框架绝对路径
    private $basePath;

    //模块控制器命名空间
    public $controllerNamespace;
    //view 路径 
    public $viewPath;

    //当前模块的命名空间
    public $namespace;

    //监听事件
    public $events=[];
    
    public function __construct($id,$module,array $config=[])
    {
        $this->id = $id;
        $this->father = $module;
        
        parent::__construct($config);
    }
    //初始化命令空间
    public function init(){
        $class = get_class($this);
        if( $this->namespace ===null && ($pos = strrpos($class,'\\'))!== false){
            $this->namespace = substr($class, 0, $pos);
        }
        if($this->controllerNamespace === null && $this->namespace !==null ){
            $this->controllerNamespace =  $this->namespace . '\\controllers';
        }

        $this->eventHandlerInit();
    }

    //项目绝对路径    
    public function getBasePath(){
        if($this->basePath === null){
            $reflection = new \ReflectionClass($this);
            return $this->basePath = dirname($reflection->getFileName());
        }
        return $this->basePath;
    }

    public function setBasePath($path)
    {
        $path = See::getAlias($path);
        $p = strncmp($path, 'phar://', 7) === 0 ? $path : realpath($path);
        if ($p !== false && is_dir($p)) {
            $this->basePath = $p;
        } else {
            throw new ErrorException("The directory does not exist: $path");
        }
    }
    //模块id
    public function getUniqueId(){
        return $this->father ? ltrim($this->father->getUniqueId() . '/'. $this->id) : $this->id;
    }
    
    public function getControllerPath(){
        return \See::getAlias('@' . str_replace('\\', '/', $this->controllerNamespace));
    }
    //模板路径
    public function getViewPath()
    {
        if ($this->viewPath === null) {
            $this->viewPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'views';
        }
        return $this->viewPath;
    }
    //是否设置了模块
    public function hasModule($id)
    {
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? false : $module->hasModule(substr($id, $pos + 1));
        } else {
            return isset($this->modules[$id]);
        }
    }
    //创建模块
    public function getModule($id)
    {
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? null : $module->getModule(substr($id, $pos + 1));
        }

        if (isset($this->modules[$id])) {
            if ($this->modules[$id] instanceof Module) {
                return $this->modules[$id];
            } else {
                /* @var $module Module */
                $module = See::createObject($this->modules[$id], [$id, $this]);
                return $this->modules[$id] = $module;
            }
        }

        return null;
    }
    //获取所有模块
    public function getModules($loadedOnly = false)
    {
        if ($loadedOnly) {
            $modules = [];
            foreach ($this->modules as $module) {
                if ($module instanceof Module) {
                    $modules[] = $module;
                }
            }

            return $modules;
        } else {
            return $this->modules;
        }
    }
    //run
    public function runAction($route, $params = [])
    {
        $event = new see\event\Event();
        $event->sender = $this;
        see\event\Event::trigger($this,'RunAction',$event);

        $parts = $this->createController($route);
        if (is_array($parts)) {
            /* @var $controller Controller */
            list($controller, $actionID) = $parts;
            See::$app->controller = $controller;
            $result = $controller->runAction($actionID, $params);
            return $result;
        } else {
            $id = $this->getUniqueId();
            throw new NotFoundException('module id: '.$this->id.', Controller error,Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
        }
    }
    //创建控制器
    public function createController($route)
    {
        if ($route === '') {
            $route = $this->defaultRoute;
        }

        // double slashes or leading/ending slashes may cause substr problem
        $route = trim($route, '/');
        if (strpos($route, '//') !== false) {
            return false;
        }

        if (strpos($route, '/') !== false) {
            list ($id, $route) = explode('/', $route, 2);
        } else {
            $id = $route;
            $route = '';
        }

        $module = $this->getModule($id);
        if ($module !== null) {
            return $module->createController($route);
        }

        if (($pos = strrpos($route, '/')) !== false) {
            $id .= '/' . substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        }
        $controller = $this->createControllerByID($id);
        if ($controller === null && $route !== '') {
            $controller = $this->createControllerByID($id . '/' . $route);
            $route = '';
        }
        return  $controller === null ? false : [$controller, $route];
    }
    //按ID创建控制器
    public function createControllerByID($id)
    {
        $pos = strrpos($id, '/');
        if ($pos === false) {
            $prefix = '';
            $className = $id;
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }

        if (!preg_match('%^[a-z][a-z0-9A-Z\\-_]*$%', $className)) {
            return null;
        }
        if ($prefix !== '' && !preg_match('%^[a-z0-9_/]+$%i', $prefix)) {
            return null;
        }

        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Controller';
        $className = ltrim($this->controllerNamespace . '\\' . str_replace('/', '\\', $prefix)  . $className, '\\');
        if (strpos($className, '-') !== false || !class_exists($className)) {
            return null;
        }

        if (is_subclass_of($className, 'see\base\Controller')) {
            $controller = See::createObject($className, [$id, $this]);
            return get_class($controller) === $className ? $controller : null;
        } else {
            throw new ErrorException("Controller class must extend from \\see\\base\\Controller.");
        }
    }

    //event eventHandler
    public function eventHandlerInit(){
        $events = $this->events;
        foreach($events as $k=>$v){
            if(!$this->has($k,true)){
                $eventHandler = $this->createObject($v);
                $this->set("see_event_".$k,$eventHandler);
            }
        }
    }
    //get event eventHandler
    public function getEventHandler($id){
        return $this->get('see_event_'.$id);
    }

}