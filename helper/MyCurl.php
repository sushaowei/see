<?php

/**
 * Created by ssw curl 1.0.
 * User: ch168mk
 * Date: 16/7/5
 * Time: 下午4:46
 */
namespace see\helper;

class MyCurl
{
    public $version = "1.0";

    public $handler;

    public $timeOut = 30;

    public $header = ['Expect:'];

    public $referer;

    public $postFields;

    public $url;

    public $ssl = false;

    public $type = "get";

    public $agent = "see curl/1.0";

    public $returnData;

    public function __construct($url)
    {
        $url = trim($url);
        $this->handler = curl_init();
        curl_setopt($this->handler, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($this->handler, CURLOPT_CONNECTTIMEOUT, $this->timeOut);
        curl_setopt($this->handler, CURLOPT_TIMEOUT, $this->timeOut);
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->handler, CURLOPT_URL, $url);
        //支持https
        $this->ssl = stripos($url, 'https://') === 0 ? true : false;
        if ($this->ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
    }

    public function setOpt($opt, $value)
    {
        curl_setopt($this->handler, $opt, $value);
    }

    public function setHeader($header)
    {
        $this->header = array_merge($this->header, $header);
    }

    public function setCookie($cookie)
    {
        curl_setopt($this->handler, CURLOPT_COOKIE, $cookie);
    }

    public function setReferer($referer)
    {
        $this->referer = $referer;
    }

    public function setPostFeilds($postFields)
    {
        $this->postFields = $postFields;
    }

    public function setAgent($agent){
        $this->agent = $agent;
    }

    public function exec()
    {
        if (!empty($this->referer)) {
            curl_setopt($this->handler, CURLOPT_REFERER, $this->referer);
        }else{
            curl_setopt($this->handler, CURLOPT_AUTOREFERER, true);
        }
        //set header
        $traceId = \See::$log->getTraceId();
        $seq = \See::$log->getSeq();
        \See::$log->setSeqNext(false);
        $this->setHeader(['_tid:'.$traceId]);
        $this->setHeader(['_seq:'.$seq]);
        $this->setHeader(['PHP-TID:'.$traceId]);
        $this->setHeader(['PHP-SEQ:'.$seq]);

        curl_setopt($this->handler,CURLOPT_HTTPHEADER,$this->header);
        curl_setopt($this->handler, CURLOPT_USERAGENT,$this->agent);
        curl_setopt($this->handler, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($this->handler, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        if($this->type=='post'){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postFields);
        }

        $this->returnData = curl_exec($this->handler);
        if ($errorNo = curl_errno($this->handler)) {
        //error message
            $errorMsg = curl_error($this->handler);
            \See::$log->warning("curl error, url:%s,type:%s,post:%s,errorNo:%s,errorMsg:%s,return:%s",$this->url,$this->type,$this->postFields,$errorNo,$errorMsg,$this->returnData);
        }else{
            \See::$log->trace("curl, url:%s,type:%s,post:%s,return:%s",$this->url,$this->type,$this->postFields,$this->returnData);
        }
        \See::$log->setSeqNext(true);
        curl_close($this->handler);
        return $this->returnData;
    }

    public function get(){
        $this->type = 'get';
        return $this->exec();
    }

    public function post(){
        $this->type ='post';
        return $this->exec();
    }
}