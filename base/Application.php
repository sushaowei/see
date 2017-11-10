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

    /**
     * Application constructor.
     * @param array $config
     */
    public function __construct(array $config=[])
    {
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
}