<?php
namespace see\event;
class BeforeQuery extends EventHandler
{
	protected static $eventName = "BeforeAction";
	protected static $eventClass = 'see\\db\\PdoMysql';
}