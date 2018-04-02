<?php
namespace see\event;
class RunAction extends EventHandler
{
	protected static $eventName = "RunAction";
	protected static $eventClass = 'see\\base\\Module';
}