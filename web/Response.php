<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/11 0011
 * Time: 下午 8:26
 */

namespace see\web;


use see\base\Object;
use see\exception\ErrorException;
use see\exception\NotFoundException;

class Response extends Object 
{

    public $data="";
    
    public $charset;
    
    public $version;
    
    private $isSend = false;
    
    public $exitStatus = 0;
    
    public $notFoundTpl;

    public $defaultNotFoundRoute = 'site/notFound';

    public static $httpStatus = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        118 => 'Connection timed out',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        210 => 'Content Different',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        310 => 'Too many Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range unsatisfiable',
        417 => 'Expectation failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable entity',
        423 => 'Locked',
        424 => 'Method failure',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway or Proxy Error',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        507 => 'Insufficient storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];
    
    private $_statusCode = 200;
    
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
    //set send data
    public function setData($data){
        $this->data = $data;
    }

    public function send($data=null)
    {
        if($this->isSend){
            return ;
        }
        $this->sendHeaders();
        $this->sendContent($data);
        $this->isSend = true;
    }
    
    public function getStatusCode(){
        return $this->_statusCode;
    }
    
    public function setStatusCode($value, $text=null){
        if($value === null){
            $value =200;
        }
        
        $this->_statusCode = (int)$value;
        if($text === null){
            $this->statusText = isset(static::$httpStatus[$value]) ? static::$httpStatus[$value] : '';
        }else{
            $this->statusText = $text;
        }
    }

    protected function sendHeaders(){
        if(headers_sent()){
            return;
        }
        $statusCode = $this->getStatusCode();
        header("HTTP/{$this->version} {$statusCode} {$this->statusText}");
    }

    protected function sendContent($data=null)
    {   
        if($data !== null){
            $this->data = $data;
        }
        if($this->data === null){
            return ;
        }
        if($this->data !== null && is_string($this->data)){
            echo $this->data;
        }else{
            throw new ErrorException("The Response data must be a string");
        }
    }

    public function setHeaderJson(){
        header('Content-type: application/json');
    }

    /**
     * @param NotFoundException $e
     */
    public function notFoundSend($e=null){
        if(\See::$app->has('notFound')){
            $notFound = \See::$app->get('notFound');
            $notFound($e);
        }elseif($this->notFoundTpl === null){
            $this->setStatusCode(404);
            $this->data = "404 not found";
        }else{
            $this->data = \See::$app->getView()->render($this->notFoundTpl);
        }
        $this->send();
    }
}