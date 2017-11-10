<?php
namespace see\event;
class EventException extends EventHandler
{
	protected static $eventName = "EventException";
	protected static $eventClass = 'see\\web\\Application';
}