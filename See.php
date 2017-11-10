<?php

/**
 * Created by PhpStorm.
 * User: ShaoweiSu
 * Date: 2016/6/8 0008
 * Time: 下午 7:28
 */
require 'BaseSee.php';
require (__DIR__).'/helper/functions.php';
class See extends see\BaseSee
{

}

spl_autoload_register(['See', 'autoload'], true, true);
