<?php
//重定向
function redirect($url){
	header("Location:{$url}");
	throw new \see\exception\NotFoundException("redirect", 301);
}

//过滤xss
function xss_clean($str){
	return \see\helper\Security::xss_clean($str);
}