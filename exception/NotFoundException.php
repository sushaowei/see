<?php
/**
 * Created by PhpStorm.
 * User: ch168mk
 * Date: 16/7/4
 * Time: 下午5:29
 */
namespace see\exception;
class NotFoundException extends \Exception
{
    public function getName(){
        return "NotFoundException";
    }
}