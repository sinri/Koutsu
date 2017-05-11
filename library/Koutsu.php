<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/5/11
 * Time: 14:49
 */

namespace sinri\koutsu\library;


use sinri\enoch\helper\CommonHelper;

class Koutsu
{
    private $host;
    private $port;
    private $worker;
    private $servicePath;

    protected $socket;
    protected $clients;

    protected $helper;

    /**
     * Koutsu constructor.
     * @param string $host
     * @param string $port
     * @param StandardKoutsuWorker $worker
     */
    public function __construct($host, $port, $worker, $servicePath)
    {
        $this->host = $host;
        $this->port = $port;
        $this->worker = $worker;
        $this->servicePath = $servicePath;
        $this->helper = new CommonHelper();
    }

    public function reset()
    {
        //Create TCP/IP sream socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        //reuseable port
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        //bind socket to specified host
        socket_bind($this->socket, 0, $this->port);
        //listen to port
        socket_listen($this->socket);
        //create & add listning socket to the list
        $this->clients = array($this->socket);
    }

    public function run()
    {
        $this->reset();

        //start endless loop, so that our script doesn't stop
        while (true) {
            //manage multipal connections
            $changed = $this->clients;
            //returns the socket resources in $changed array
            socket_select($changed, $null, $null, 0, 10);

            //check for new socket
            if (in_array($this->socket, $changed)) {
                $socket_new = socket_accept($this->socket); //accpet new socket
                $this->clients[] = $socket_new; //add socket to client array

                $header = socket_read($socket_new, 1024); //read data sent by the socket
                $this->perform_handshaking($header, $socket_new, $this->host, $this->port); //perform websocket handshake

                socket_getpeername($socket_new, $ip); //get ip address of connected socket
                $response = json_encode(
                    array(
                        'time' => date('Y-m-d H:i:s'),
                        'type' => 'system',
                        'message' => $ip . ' connected',
                        'client_count' => count($this->clients)
                    )
                );

                //plugin
                $response = $this->worker->processNewSocket($ip, $header, $response);

                $response = $this->mask($response); //prepare json data
                $this->send_message($response); //notify all users about new connection

                //make room for new socket
                $found_socket = array_search($this->socket, $changed);
                unset($changed[$found_socket]);
            }

            //loop through all connected sockets
            foreach ($changed as $changed_socket) {

                //check for any incomming data
                while (socket_recv($changed_socket, $buf, 1024, 0) >= 1) {
                    $received_text = $this->unmask($buf); //unmask data
                    $tst_msg = json_decode($received_text, true); //json decode
                    $user_name = $this->helper->safeReadArray($tst_msg, 'name', '__UNKNOWN__');
                    $user_message = $this->helper->safeReadArray($tst_msg, 'message', '__EMPTY__');
                    $user_color = $this->helper->safeReadArray($tst_msg, 'color', '#222222');

                    socket_getpeername($changed_socket, $ip); //get ip address of connected socket

                    //prepare data to be sent to client
                    $response_text = json_encode(
                        array(
                            'time' => date('Y-m-d H:i:s'),
                            'type' => 'usermsg',
                            'name' => $user_name . '@' . $ip,
                            'message' => $user_message,
                            'escaped_message' => htmlentities($user_message),
                            'color' => $user_color,
                            'client_count' => count($this->clients)
                        )
                    );

                    // plugin
                    $response_text = $this->worker->processReadMessage($ip, $tst_msg, $response_text);

                    $response_text = $this->mask($response_text);
                    $this->send_message($response_text); //send data
                    break 2; //exist this loop
                }

                $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
                if ($buf === false) { // check disconnected client
                    // remove client for $this->clients array
                    $found_socket = array_search($changed_socket, $this->clients);
                    socket_getpeername($changed_socket, $ip);
                    unset($this->clients[$found_socket]);

                    //notify all users about disconnected connection
                    $response = json_encode(
                        array(
                            'time' => date('Y-m-d H:i:s'),
                            'type' => 'system',
                            'message' => $ip . ' disconnected',
                            'client_count' => count($this->clients)
                        )
                    );


                    //plugin
                    $response = $this->worker->processCloseSocket($ip, $response);

                    $response = $this->mask($response);
                    $this->send_message($response);
                }
            }
        }
        // close the listening socket
        socket_close($this->socket);
    }

    //handshake new client.
    protected function perform_handshaking($receved_header, $client_conn, $host, $port)
    {
        $headers = array();
        $lines = preg_split("/\r\n/", $receved_header);
        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }

        $secKey = $headers['Sec-WebSocket-Key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        //hand shaking header
        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $host\r\n" .
            "WebSocket-Location: ws://{$this->servicePath}\r\n" .
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
        socket_write($client_conn, $upgrade, strlen($upgrade));
    }

    //Encode message for transfer to client.
    protected function mask($text)
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);
        $header = '';
        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        elseif ($length >= 65536)
            $header = pack('CCNN', $b1, 127, $length);
        return $header . $text;
    }

    //Unmask incoming framed message
    protected function unmask($text)
    {
        $length = ord($text[1]) & 127;
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } elseif ($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }
        $text = "";
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }

    protected function send_message($msg)
    {
        foreach ($this->clients as $changed_socket) {
            @socket_write($changed_socket, $msg, strlen($msg));
        }
        return true;
    }
}