<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/4
 * Time: 下午5:29
 */
namespace see\exception;
use Throwable;

class NotFoundException extends \Exception
{
    public function __construct($message = "", $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getName(){
        return "NotFoundException";
    }
}