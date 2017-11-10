<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/11 0011
 * Time: 上午 12:36
 */

namespace see\base;


interface ViewRenderInterface
{
    public function render($view, $viewFile, $params);
}