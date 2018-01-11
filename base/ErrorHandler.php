<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/10 0010
 * Time: 下午 11:11
 */

namespace see\base;
use see\exception\ErrorException;
use see\exception\NotFoundException;

class ErrorHandler extends Object
{
    public function register(){
        if(\See::$app->envDev){
            ini_set('display_errors', true);
        }
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        
    }

    /**
     * @param \Exception $exception
     */
    public function handleException($exception){
        $url = isset($_SERVER['REQUEST_URI'])? "url:".$_SERVER['REQUEST_URI']."\n":"";
        if(!\See::$log){
            trigger_error($exception->getMessage(),"url:".$url);
            exit;
        }
        $response = \See::$app->getResponse();
        if($exception instanceof NotFoundException){
            $response->notFoundSend($exception);
        }else{
            if(\See::$app->envDev){
                echo "<pre>";
                echo $exception->getMessage();
                echo $exception->getTraceAsString();
                echo "</pre>";
            }
            $response->setStatusCode(500);
            $response->send("");
            \See::$log->fatal("%s",$url.$exception->getMessage() . "\n" . $exception->getTraceAsString());
            exit;
        }
    }
    
    public function handleError($code, $message, $file, $line){
        if($code<2){
            throw new ErrorException($message. ',file: '.$file. ':' . $line);
        }else{
            \See::$log->warning($message. ',file: '.$file. ':' . $line);
            if(\See::$app->envDev){
                echo "<pre>";
                echo "[warning]".$message. ',file: '.$file. ':' . $line;
                echo "</pre>";
            }
        }
    }
    
    
}