<?php
namespace see\base;
/**
 * Class Logger
 * 日志类
 */
class LoggerTrace extends Object
{

    const L_ALL = 0;

    const L_DEBUG = 1;

    const L_TRACE = 2;

    const L_INFO = 3;

    const L_NOTICE = 4;

    const L_WARNING = 5;

    const L_FATAL = 6;

    private static $ARR_DESC = array(
        0 => 'ALL', 1 => 'DEBUG', 2 => 'TRACE', 3 => 'INFO',
        4 => 'NOTICE', 5 => 'WARNING', 6 => 'FATAL'
    );

    public $level = self::L_DEBUG;

    private $basic = [];

    public $file;

    private $fileArr = [];

    public $path;

    public $foreFlush = false;

    public $suffix;

    public $stid;
    public $seq = "";

    public function addBasic($key, $value)
    {

        $this->basic[$key] = $value;
    }

    public function init()
    {
        if ($this->path === null) {
            $this->path = '@runtime/log';
        }
        $this->path = \See::getAlias($this->path);
        if ($this->file === null) {
            $this->file = \See::$app->id;
        }

        $fileName = rtrim($this->path, '/') . DIRECTORY_SEPARATOR . trim($this->file, '/') . '.log';
        if (!file_exists($this->path)) {
            if (!mkdir($this->path, 0777, true)) {
                trigger_error("create log file {$this->path} failed, no permmission");
                return;
            }
        }
        $this->fileArr[0] = fopen($fileName, 'a+');
        if (empty($this->fileArr[0])) {
            trigger_error("create log file $fileName failed, no disk space for permission");
            $this->fileArr = array();
            return;
        }

        $fileName1 = rtrim($this->path, '/') . DIRECTORY_SEPARATOR . trim($this->file, '/') . '-error.log';
        $this->fileArr[1] = fopen($fileName1, 'a+');
        if (empty($this->fileArr[1])) {
            trigger_error("create log file $fileName1 failed, no disk space for permission");
            $this->fileArr = array();
            return;
        }

        $fileName2 = rtrim($this->path, '/') . DIRECTORY_SEPARATOR . trim($this->file, '/') . '-fault.log';
        $this->fileArr[2] = fopen($fileName2, 'a+');
        if (empty($this->fileArr[2])) {
            trigger_error("create log file $fileName2 failed, no disk space for permission");
            $this->fileArr = array();
            return;
        }
        // $this->addBasic('logId', $this->getLogId());
    }

    public function randomkeys($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key = "";
        for ($i = 0; $i < $length; $i++) {

            $key .= $pattern {
                mt_rand(0, 35)};    //生成php随机数   
        }
        return $key;
    } 

    //get traceId
    public function getTraceId()
    {
        if (!isset($_SERVER['_tid'])) {
            $_SERVER['_tid'] = time() . "_" . $this->randomkeys(4);
        }
        return $_SERVER['_tid'];
    }

    //get seq
    public function getSeq()
    {
        if (!isset($_SERVER['_seq'])) {
            $_SERVER['_seq'] = "1.1";
        } else {
            $prefix = substr($_SERVER['_seq'], 0, strrpos($_SERVER['_seq'], "."));
            $num = substr($_SERVER['_seq'], strrpos($_SERVER['_seq'], ".") + 1);
            $_SERVER['_seq'] = $prefix . "." . ((int)$num + 1);
        }
        $this->seq = $_SERVER['_seq'];
        return $_SERVER['_seq'];
    }

    //json
    public function formatArg($arrArg)
    {
        if (is_array($arrArg)) {
            foreach ($arrArg as $k => $v) {
                if ($k == 0) {
                    continue;
                }
                if (is_array($v)) {
                    $arrArg[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
                }
            }
        }
        return $arrArg;
    }

    public function log($level, $arrArg)
    {

        if ($level < $this->level || empty($this->fileArr) || empty($arrArg)) {
            return;
        }
        $arrMicro = explode(" ", microtime());
        foreach ($arrArg as $idx => $arg) {
            if (is_array($arg)) {
                array_walk_recursive($arg, array($this, 'checkPrintable'));
                $data = serialize($arg);
                $arrArg[$idx] = $data;
            }
        }

        $content = date('Ymd H:i:s');
        $content .= "." . intval($arrMicro[0] * 1000);
        $content .= " [" . \See::$app->id . "]";
        // $content .= self::$ARR_DESC[$level];

        $tid = $this->getTraceId();
        $seq = $this->getSeq();
        $content .= "\t_rid:" . ($tid) . "-" . $seq;
        $content .= "\t_tid:" . $tid;
        $content .= "\t_stid:" . $this->stid();
        $content .= "\t_seq:" . $seq;
        $content .= "\t_app:" . \See::$app->id;
        $content .= "\t_time:" . time();
        $content .= "\t_type:http";
        $content .= "\t_capp:";
        $content .= "\t_path:" . $_SERVER['REQUEST_URI'];
        $content .= "\t_cip:" . $this->getRealUserIp();
        $content .= "\t_sip:" . $_SERVER['SERVER_ADDR'];
        $extParams = ["get" => $_GET, "post" => $_POST];
        $content .= "\t_ext_params:" . str_replace("\t", " ", json_encode($extParams));

        $arrArg = $this->formatArg($arrArg);
        $msg = "";
        $msg .= "level:" . self::$ARR_DESC[$level];
        $arrTrace = debug_backtrace();
        if (isset($arrTrace[1])) {
            $line = $arrTrace[1]['line'];
            $file = $arrTrace[1]['file'];
            $file = substr($file, strlen(\See::$app->getBasePath()) + 1);
            $msg .= (" " . $file . ":" . $line);
        }
        $msg .= call_user_func_array('sprintf', $arrArg);

        $content .= "\t_params:" . str_replace("\t", " ", $msg);

        $cost = round(microtime(true) - SEE_BEGIN_TIME, 2);
        $content .= "\t_ext:false";
        $content .= "\t_cost:" . $cost;
        $content .= "\t_version:1";
        $content .= "\t_ua:" . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "");
        $content .= "\n";

        $file = $this->fileArr[0];
        fputs($file, $content);
        if ($this->foreFlush) {
            fflush($file);
        }

        if ($level <= self::L_NOTICE) {
            return;
        }
        if ($level == self::L_WARNING) {
            $file = $this->fileArr[1];
            $content .= "\t_err_tag:1";
            fputs($file, $content);
            if ($this->foreFlush) {
                fflush($file);
            }

        }
        if ($level == self::L_FATAL) {
            $content .= "\t_fatal:1";
            $file = $this->fileArr[2];
            fputs($file, $content);
            if ($this->foreFlush) {
                fflush($file);
            }

        }

    }

    public function stid()
    {
        if (!empty($this->stid)) {
            return $this->stid;
        }
        $str = $this->getTraceId();
        $arr = explode("_", $str);
        $str = $arr[1];
        $int = $arr[0];
        for ($i = 0; $i < strlen($str); $i++) {
            if (\is_numeric($str[$i])) {
                $int += $str[$i];
            } else {
                $int += ord($str[$i]);
            }
        }
        $int = (int)$int;
        $this->stid = \base_convert($int, 10, 36);
        return $this->stid;
    }
    public function getRealUserIp()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                if (preg_match("/,/i", $_SERVER["HTTP_X_FORWARDED_FOR"])) {//CDN特殊处理
                    $_tmpIps = explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"]);
                    $realip = $_tmpIps[0];
                } else {
                    $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                }
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                if (preg_match("/,/i", getenv("HTTP_X_FORWARDED_FOR"))) {//CDN特殊处理
                    $_tmpIps = explode(',', getenv("HTTP_X_FORWARDED_FOR"));
                    $realip = $_tmpIps[0];
                } else {
                    $realip = getenv("HTTP_X_FORWARDED_FOR");
                }
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }

    public function checkPrintable(&$data, $key)
    {

        if (!is_string($data)) {
            return;
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/', $data)) {
            $data = base64_encode($data);
        }
    }

    public function flush()
    {

        foreach ($this->fileArr as $file) {
            fflush($file);
        }
    }

    public function getLogId()
    {
        return round(microtime(true) * 10000) . mt_rand(1000, 9999);
    }

    public function debug()
    {

        $arrArg = func_get_args();
        $this->log(self::L_DEBUG, $arrArg);
    }

    public function trace()
    {

        $arrArg = func_get_args();
        $this->log(self::L_TRACE, $arrArg);
    }

    public function info()
    {

        $arrArg = func_get_args();
        $this->log(self::L_INFO, $arrArg);
    }

    public function notice()
    {

        $arrArg = func_get_args();
        $this->log(self::L_NOTICE, $arrArg);
    }

    public function warning()
    {

        $arrArg = func_get_args();
        $this->log(self::L_WARNING, $arrArg);
    }

    public function fatal()
    {

        $arrArg = func_get_args();
        $this->log(self::L_FATAL, $arrArg);
    }

}