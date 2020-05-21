<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/5/11
 * Time: 14:45
 */

use Psr\Log\LogLevel;
use sinri\ark\core\ArkLogger;
use sinri\ark\websocket\ArkWebSocketConnections;
use sinri\ark\websocket\ArkWebSocketDaemon;
use sinri\koutsu\implementation\KoutsuWorker;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

date_default_timezone_set("Asia/Shanghai");

$logger = new ArkLogger(__DIR__ . '/log', 'daemon');
$logger->setIgnoreLevel(LogLevel::DEBUG);
$logger->removeCurrentLogFile();

$connections = new ArkWebSocketConnections();

$worker = new KoutsuWorker($connections, $logger);
$koutsu = new ArkWebSocketDaemon($host, $port, $servicePath, $worker, $connections, $logger);
try {
    $koutsu->loop();
} catch (Exception $e) {
    $logger->error($e->getMessage());
}