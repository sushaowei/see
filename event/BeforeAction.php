<?php
namespace see\event;
class BeforeAction extends EventHandler
{
	protected static $eventName = "BeforeAction";
	protected static $eventClass = 'see\\base\\Action';
}