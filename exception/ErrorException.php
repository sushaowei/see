<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/4
 * Time: 下午5:27
 */
namespace see\exception;
class ErrorException extends \Exception
{
    public function getName(){
        return "ErrorException";
    }
}