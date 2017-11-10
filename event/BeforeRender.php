<?php
namespace see\event;
class BeforeRender extends EventHandler
{
	protected static $eventName = "BeforeRender";
	protected static $eventClass = 'see\\base\\View';
}