<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/5/11
 * Time: 14:45
 */

use sinri\koutsu\library\Koutsu;
use sinri\koutsu\library\StandardKoutsuWorker;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/library/StandardKoutsuWorker.php';
require_once __DIR__ . '/library/Koutsu.php';
require_once __DIR__ . '/config.php';

date_default_timezone_set("Asia/Shanghai");

$worker = new StandardKoutsuWorker($logDir);
$koutsu = new Koutsu($host, $port, $worker, $servicePath);
$koutsu->run();