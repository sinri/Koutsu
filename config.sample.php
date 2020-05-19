<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/5/11
 * Time: 14:45
 */

$host = 'conoha.sinri.cc'; //host
$port = '10874'; //port

// this is mainly for the frontend to initialize the WebSocket instance
$servicePath = 'ws://' . $host . ':' . $port . '/koutsu/KoutsuHandler.php';
//$servicePath = 'wss://' . $host . '/koutsu/KoutsuHandler.php';

$logDir = '/var/log/sinri_koutsu';