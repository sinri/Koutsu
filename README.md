# Koutsu
研究利用WebSocket通信来进行免客户端的各种有的没的交流功能。

## Sample Usage

1. run `KoutsuHandler.php` in CLI

```bash
php -q KoutsuHandler.php
```

2. run `index.php` in Browser

## WSS Notice

WSS is a WS over TLS. A sample architecture amongst Aliyun is as following: 

Client (javascript WebSocket instance) 
    --(wss://HOST/PATH or https://HOST/PATH)--> SLB(port 443) 
        --(Frontend)--> Web Page Render (Nginx port 80 or so)
        --(WebSocket)--> WebSocket Service (CLI Socket port 10874 or so)
