<?php
namespace see\exception;
class EventException extends \Exception
{
	public function getName(){
        return "EventException";
    }
}