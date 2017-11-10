<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/10 0010
 * Time: 下午 11:11
 */

namespace see\base;
use see\exception\ErrorException;

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
        if(\See::$log){
            if(\See::$app->envDev){
                echo "<pre>";
                echo $exception->getMessage();
                echo $exception->getTraceAsString();
                echo "</pre>";
            }
            \See::$log->addBasic('httpStatus', 500);
            \See::$log->fatal("%s",$url.$exception->getMessage() . "\n" . $exception->getTraceAsString());
        }else{
            trigger_error($exception->getMessage());
        }
        exit(0);
    }
    
    public function handleError($code, $message, $file, $line){
        if($code<2){
            throw new ErrorException($message. ',file: '.$file. ':' . $line);
        }else{
            \See::$log->warning("%s",$message. ',file: '.$file. ':' . $line);
            if(\See::$app->envDev){
                echo "<pre>";
                echo "[warning]".$message. ',file: '.$file. ':' . $line;
                echo "</pre>";
            }
        }
    }
    
    
}