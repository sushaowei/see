<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/5
 * Time: 上午10:34
 */
namespace see\base;

use see\exception\ErrorException;
use see;

/**
 * Class Application
 * @package see\base
 */
abstract class Application extends Module
{

    public $namespace = "app";
    /**
     * @var string
     */
    public $controllerNamespace = 'app\\controllers';

    /**
     * @var
     */
    public $id;

    /**
     * @var bool
     */
    public $envDev=false;

    /**
     * @var string
     */
    public $version = '2.0';

    /**
     * @var string
     */
    public $charset = 'UTF-8';
    
    /**
     * @var see\web\Controller
     */
    public $controller;

    /**
     * @var
     */
    public $requestedRoute;

    /**
     * @var
     */
    public $requestedAction;

    /**
     * @var
     */
    public $requestedParams;

    /**
     * @var
     */
    public $runtimePath;

    /**
     * params
     */
    public $params=[];

    public static $e = 'e';

    public $_config;

    public $routeAll;

    public $debug=false;

    public $environment = 'pro';//dev, pre, pro

    public $componentsAlias = [];

    /**
     * Application constructor.
     * @param array $config
     */
    public function __construct(array $config=[])
    {
        $this->_config = $config;
        \See::$app = $this;
        $this->preInit($config);
        Object::__construct($config);
    }

    /**
     * @param $config
     * @throws ErrorException
     */
    public function preInit(&$config)
    {
        if (!isset($config['id'])) {
            throw new ErrorException('The "id" configuration for the Application is required.');
        }
        if (isset($config['basePath'])) {
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        } else {
            throw new ErrorException('The "basePath" configuration for the Application is required.');
        }

      
        if (isset($config['runtimePath'])) {
            $this->runtimePath = See::getAlias($config['runtimePath']);
            unset($config['runtimePath']);
        } else {
            // set "@runtime"
            $this->runtimePath = $this->getBasePath() . DIRECTORY_SEPARATOR . "runtime";
        }
        \See::setAlias('@runtime', $this->runtimePath);
        
        if (isset($config['timeZone'])) {
            date_default_timezone_set($config['timeZone']);
            unset($config['timeZone']);
        } elseif (!ini_get('date.timezone')) {
            date_default_timezone_set('PRC');
        }

        \See::setAlias('@app', $this->getBasePath());
        \See::setAlias('@view', '@app/views');

        $this->setComponents($config);
        unset($config['components']);
    }

    /**
     * @throws ErrorException
     */
    public function init(){
        \See::$log = $this->getLog();
        $errorHandler = $this->getErrorHandler();
        $errorHandler->register();
        parent::init();
    }


    /**
     * @return array
     */
    public function coreComponents()
    {
        return [
            'view' => ['class' => 'see\base\View'],
            'errorHandler' => ['class' => 'see\base\ErrorHandler'],
            'log' => ['class' => 'see\base\Logger'],
            'db' => ['class' => 'see\db\PdoMysql'],
            'cache' => ['class' => 'see\base\Memcached'],
            'fileCache' => ['class' => 'see\base\FileCache'],
        ];
    }

    /**
     * @param $config
     * @throws ErrorException
     */
    protected function setComponents($config){
        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
        if(!empty($config['components'])){
            foreach($config['components'] as $id=>$component){
                $this->set($id,$component);
                if(!empty($component['alias'])){
                    if(is_array($component['alias'])){
                        foreach($component['alias'] as $v){
                            $this->componentsAlias[$v] = $id;
                        }
                    }elseif(is_string($component['alias'])){
                        $this->componentsAlias[$component['alias']] = $id;
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getUniqueId()
    {
        return '';
    }

    /**
     * @return int
     * @throws ErrorException
     */
    public function run()
    {
        $this->handleRequest($this->getRequest());
        $response = $this->getResponse();
        $response->send();
        See::$log->notice('request completed, url:%s', $this->getRequest()->getUrl());
        return $response->exitStatus;
    }

    /**
     * @param $request
     * @return \see\web\Response
     */
    abstract public function handleRequest($request);

    
   
    /**
    * @return ErrorHandler
    * @throws \ErrorException
    */
    public function getErrorHandler()
    {
       return $this->get('errorHandler');
    }

    /**
    * @return mixed|object
    * @throws \ErrorException
    */
    public function getLog()
    {
       return $this->get('log');
    }

    /**
     * @return see\web\Request | see\console\Request
     * @throws \ErrorException
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * @return \see\web\Response
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * @return View
     * @throws \ErrorException
     */
    public function getView()
    {
        return $this->get('view');
    }

    /**
     * @return see\db\PdoMysql
     * @throws \ErrorException
     */
    public function getDb(){
        return $this->get('db');
    }

    /**
     * @return mixed|object|Memcached
     * @throws \ErrorException
     */
    public function getCache(){
        return $this->get('cache');
    }

    /**
     * @return mixed|object|FileCache
     * @throws \ErrorException
     */
    public function getFileCache(){
        return $this->get('fileCache');
    }


    /**
     * 获取所有route
     * @return array|mixed
     */
    public function generateAllRoute(){
        if(!empty(\See::$app->routeAll)){
            return \See::$app->routeAll;
        }
        $cacheKey = \See::$app->id . "_route";
        $fileCache = \See::$app->get("fileCache");

        $basePath = \See::$app->getBasePath();//根路径
        $lastEditTime = filectime($basePath."/config/main.php");
        $namespace = $this->controllerNamespace;
        $files = $this->getControllersFile($namespace);
        $module = isset($this->_config['modules'])?$this->_config['modules']:[];
        $mFiles = $this->getModuleFiles($module);
        //获取所有控制器文件
        $files = array_merge($files,$mFiles);
        //获取最修改时间，用来控制缓存过期
        foreach($files as $row) {
            $ctime = filectime($row['file']);
            if ($ctime > $lastEditTime) {
                $lastEditTime = $ctime;
            }
        }

        $result = $fileCache->get($cacheKey);
        if($result !== false){
            $result = json_decode($result,true);
            if(!empty(isset($result['route'])) && isset($result['lastEditTime']) && $result['lastEditTime'] == $lastEditTime){
                \See::$app->routeAll = $result;
                return $result;
            }
        }
        $route = [];
        $moduleReflection = [];
        foreach($files as $row){
            $reflection = new \ReflectionClass($row['class']);
            $methods = $reflection->getMethods();
            $controllerName = $reflection->getName();
            $controllerId = substr($controllerName,strrpos($controllerName,"\\")+1,-strlen("Controller"));
            $controllerId = lcfirst($controllerId);
            $controllerPropers = $reflection->getDefaultProperties();
            $defaultAction = $controllerPropers['defaultAction'];
            if(empty($row['module'])){
                $prefix = "";
                $defaultController = \See::$app->defaultRoute;
            }else{
                $prefix = substr($row['module'],strpos($row['module'],"\\"));
                $prefix = substr($prefix, 0,strrpos($prefix,'\\'));
                $prefix = trim($prefix,'\\');
                $prefix = str_replace("\\","/",$prefix);
                if(!isset($moduleReflection[$row['moduleId']])){
                    $moduleReflection[$row['moduleId']] = new \ReflectionClass($row['module']);
                }
                $modulePropers = $moduleReflection[$row['moduleId']]->getDefaultProperties();
                $defaultController = $modulePropers['defaultRoute'];
            }
            foreach($methods as $method){
                if($method->isPublic()){
                    $methodName = $method->getName();
                    if(strncmp($methodName,'action',6) === 0){
                        $tmp = [];
                        $tmp['doc'] =$method->getDocComment();
                        $actionName = lcfirst(substr($methodName,6));
                        $tmp['route'][] = trim($prefix . '/' .$controllerId .'/'. $actionName,'/');
                        $tmp['_source'] = $row;
                        //默认路由
                        if($actionName ==$defaultAction ){
                            $tmp['route'][] =trim( $prefix . '/' .$controllerId,"/");
                        }
                        if($defaultController == $controllerId && $actionName ==$defaultAction ){
                            $tmp['route'][] = trim($prefix===""?"/":$prefix,'/');
                        }
                        $route[] = $tmp;
                    }
                }
            }
        }

        $result = ['route'=>$route,'lastEditTime'=>$lastEditTime];
        $fileCache->set($cacheKey,json_encode($result));
        \See::$app->routeAll = $result;
        return $result;
    }


    /**
     * 获取所有模块控制器文件
     * @param $moduleArray
     * @return array
     */
    public function getModuleFiles($moduleArray){
        $result = [];
        if(empty($moduleArray)){
            return $result;
        }
        foreach ($moduleArray as $id=>$moduleChild) {
            $class = $moduleChild['class'];
            $class= substr($class,0,1)=='\\'?substr($class,1):$class;//去除第一个\
            $namespace = substr($class,0,strrpos($class,"\\"))."\controllers";
            $result = array_merge($result,$this->getControllersFile($namespace,$class,$id));

            if(!empty($moduleChild['modules'])){
                $r = $this->getModuleFiles($moduleChild['modules']);
                $result = array_merge($result,$r);
            }
        }
        return $result;
    }

    /**
     * 获取所有控制器
     * @param $namespace
     * @param string $class
     * @param string $id
     * @return array
     */
    public function getControllersFile($namespace,$class="",$id=""){
        $result = [];
        $path = \See::getAlias("@" . str_replace('\\','/',$namespace));
        if(file_exists($path)){
            $fileArray = scandir($path);
            foreach($fileArray as $k=>$v){
                if(substr($v,-strlen("Controller.php")) == "Controller.php"){
                    $controller = [];
                    $controller['class'] = $namespace . "\\" . basename($v,".php");
                    $controller['file'] = $path."/".$v;
                    $controller['module'] = $class;
                    $controller['moduleId'] = $id;
                    $result[] = $controller;
                }
            }
        }
        return $result;
    }

    public function get($id)
    {
        if ($this->has($id,true) == false && $this->has($id,false) == false && isset($this->componentsAlias[$id])){
            $id = $this->componentsAlias[$id];
        }
        return parent::get($id); // TODO: Change the autogenerated stub
    }

}