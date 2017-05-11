<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/5/11
 * Time: 14:42
 */

namespace sinri\koutsu\library;


use sinri\enoch\core\LibLog;

class StandardKoutsuWorker
{
    protected $logDirPath;
    protected $logger;

    /**
     * StandardKoutsuWorker constructor.
     * @param null|string $logDirPath
     */
    function __construct($logDirPath = null)
    {
        $this->logDirPath = ($logDirPath ? $logDirPath : __DIR__ . '/log');
        if (!file_exists($this->logDirPath)) {
            @mkdir($this->logDirPath, 0777, true);
        }

        $this->logger = new LibLog($this->logDirPath, 'Koutsu');
    }

    public function processNewSocket($ip, $header, $standard_response)
    {
        $log_obj = array('ip' => $ip, 'header' => $header, 'standard_response' => $standard_response);
        $this->log("processNewSocket", $log_obj);
        return $standard_response;
    }

    public function processReadMessage($ip, $tst_msg, $standard_response)
    {
        $log_obj = array('ip' => $ip, 'tst_msg' => $tst_msg, 'standard_response' => $standard_response);
        $this->log("processReadMessage", $log_obj);
        return $standard_response;
    }

    public function processCloseSocket($ip, $standard_response)
    {
        $log_obj = array('ip' => $ip, 'standard_response' => $standard_response);
        $this->log('processCloseSocket', $log_obj);
        return $standard_response;
    }

    public function log($text, $obj = null)
    {
        //echo "[" . date('Y-m-d H:i:s') . "] " . $text . PHP_EOL;
        $this->logger->log(LibLog::LOG_INFO, $text, $obj);
    }
}