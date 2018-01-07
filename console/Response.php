<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/11 0011
 * Time: 下午 8:26
 */

namespace see\console;


use see\base\Object;
use see\exception\ErrorException;

class Response extends Object 
{
    public $data;
    
    public $charset;
    
    public $version;
    
    private $isSend = false;
    
    public $exitStatus = 0;
    
    public $notFoundTpl;

    public $statusText;
    
    public function init(){
        if( $this->charset === null){
            $this->charset = \See::$app->charset;
        }
        if ($this->version === null) {
            if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0') {
                $this->version = '1.0';
            } else {
                $this->version = '1.1';
            }
        }
    }
    public function send()
    {
        if($this->isSend){
            return ;
        }
        $this->sendContent();
        $this->isSend = true;
    }

    protected function sendContent()
    {
        if($this->data === null){
            return ;
        }
        if($this->data !== null && is_string($this->data)){
            echo $this->data;
        }else{
            throw new ErrorException("The Response data must be a string");
        }
    }

    public function notFoundSend($e=null){
        if(\See::$app->has('notFound')){
            $notFound = \See::$app->get('notFound');
            $notFound($e);
        }elseif($this->notFoundTpl === null){
            $this->data = "404 not found";
        }
        $this->send();
    }
}