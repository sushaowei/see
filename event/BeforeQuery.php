<?php
namespace see\event;
class BeforeQuery extends EventHandler
{
	protected static $eventName = "BeforeQuery";
	protected static $eventClass = 'see\\db\\PdoMysql';
}