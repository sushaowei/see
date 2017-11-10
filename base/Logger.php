<?php
namespace see\base;
/**
 * Class Logger
 * 日志类
 */
class Logger extends Object
{

    const L_ALL = 0;

    const L_DEBUG = 1;

    const L_TRACE = 2;

    const L_INFO = 3;

    const L_NOTICE = 4;

    const L_WARNING = 5;

    const L_FATAL = 6;

    private static $ARR_DESC = array(0 => 'ALL', 1 => 'DEBUG', 2 => 'TRACE', 3 => 'INFO',
        4 => 'NOTICE', 5 => 'WARNING', 6 => 'FATAL');

    public $level = self::L_DEBUG;

    private $basic = [];

    public $file;

    private $fileArr = [];

    public $path;

    public $foreFlush = false;
    
    public $suffix;

    public function addBasic($key, $value)
    {

        $this->basic [$key] = $value;
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
        
        if($this->suffix == 'date'){
            $this->file = $this->file.'_'.date("Ymd");
        }
        
        $fileName = rtrim($this->path, '/') . DIRECTORY_SEPARATOR . trim($this->file, '/') . '.log';
        if (!file_exists($this->path)) {
            if (!mkdir($this->path, 0777, true)) {
                trigger_error("create log file {$this->path} failed, no permmission");
                return;
            }
        }
        $this->fileArr [0] = fopen($fileName, 'a+');
        if (empty ($this->fileArr [0])) {
            trigger_error("create log file $fileName failed, no disk space for permission");
            $this->fileArr = array();
            return;
        }

        $this->fileArr [1] = fopen($fileName . '.wf', 'a+');
        if (empty ($this->fileArr [1])) {
            trigger_error("create log file $fileName.wf failed, no disk space for permission");
            $this->fileArr = array();
            return;
        }
        
        $this->addBasic('logId', $this->getLogId());
    }

    public function log($level, $arrArg)
    {

        if ($level < $this->level || empty ($this->fileArr) || empty ($arrArg)) {
            return;
        }

        $arrMicro = explode(" ", microtime());
        $content = '[' . date('Ymd H:i:s ');
        $content .= sprintf("%06d", intval(1000000 * $arrMicro [0]));
        $content .= '][';
        $content .= self::$ARR_DESC [$level];
        $content .= "]";
        $cost = round(microtime(true) - SEE_BEGIN_TIME, 2);
        $this->addBasic('cost', $cost);
        foreach ($this->basic as $key => $value) {
            $content .= "[$key:$value]";
        }

        $arrTrace = debug_backtrace();
        if (isset ($arrTrace [1])) {
            $line = $arrTrace [1] ['line'];
            $file = $arrTrace [1] ['file'];
            $file = substr($file, strlen(\See::$app->getBasePath()) + 1);
            $content .= "[$file:$line]";
        }

        foreach ($arrArg as $idx => $arg) {
            if (is_array($arg)) {
                array_walk_recursive($arg, array($this, 'checkPrintable'));
                $data = serialize($arg);
                $arrArg [$idx] = $data;
            }
        }
        $content .= call_user_func_array('sprintf', $arrArg);
        $content .= "\n";

        $file = $this->fileArr [0];
        fputs($file, $content);
        if ($this->foreFlush) {
            fflush($file);
        }

        if ($level <= self::L_NOTICE) {
            return;
        }

        $file = $this->fileArr [1];
        fputs($file, $content);
        if ($this->foreFlush) {
            fflush($file);
        }
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
    
    public function getLogId(){
        return round(microtime(true)*10000) . mt_rand(1000,9999);
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