<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/5/11
 * Time: 14:45
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/library/StandardKoutsuWorker.php';
require_once __DIR__ . '/library/Koutsu.php';
require_once __DIR__ . '/config.php';

date_default_timezone_set("Asia/Shanghai");

$worker = new \sinri\koutsu\library\StandardKoutsuWorker($logDir);
$koutsu = new \sinri\koutsu\library\Koutsu($host, $port, $worker, $servicePath);
$koutsu->run();