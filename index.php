<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2017/5/11
 * Time: 14:40
 */

/**
 * Koutsu
 * Based on Hitsudan Client
 * ----
 * Original Project: https://github.com/sanwebe/Chat-Using-WebSocket-and-PHP-Socket
 * Original License: MIT
 */

require_once __DIR__ . '/config.php';

$colours = array('007AFF', 'FF7000', 'FF7000', '15E25F', 'CFC700', 'CFC700', 'CF1100', 'CF00BE', 'F00');
$user_colour = array_rand($colours);

if (isset($_REQUEST['name'])) {
    $name = $_REQUEST['name'];
} else {
    $name = '';
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <title>Koutsu - Free Chat With Any One Here</title>
    <script src="./vendor/components/jquery/jquery.min.js"></script>
    <script language="javascript" type="text/javascript">
        $(document).ready(function () {
            //create a new WebSocket object.
            var wsUri = "<?php echo $servicePath; ?>";
            websocket = new WebSocket(wsUri);

            websocket.onopen = function (ev) { // connection is open
                $('#message_box').append("<div class=\"system_msg\">Connected!</div>"); //notify user
            };

            var entered = false;
            $('#enter-btn').click(function () {
                var input_name = $("#name");
                var myname = input_name.val(); //get user name
                var mymessage = '';
                if (entered) {
                    mymessage = '[EXIT CHAT]';
                    input_name.attr('readonly', false);
                    $('#enter-btn').html('Register');
                    $('#writing_area').css('display', 'none');
                } else {
                    mymessage = '[ENTER CHAT]';
                    if (myname === "") { //empty name?
                        alert("Enter your Name please!");
                        return;
                    }
                    input_name.attr('readonly', 'readonly');
                    $('#enter-btn').html('Disconnect');
                    $('#writing_area').css('display', 'block');
                }


                //prepare json data
                var msg = {
                    message: mymessage,
                    name: myname,
                    color: '#666666'
                };
                //convert and send data to server
                websocket.send(JSON.stringify(msg));

                entered = !entered;
            });

            $('#send-btn').click(function () { //use clicks message send button
                if (!entered) {
                    alert('Please Register!');
                    return false;
                }
                var mymessage = $('#message').val(); //get message text
                var myname = $('#name').val(); //get user name

                if (myname === "") { //empty name?
                    alert("Enter your Name please!");
                    return;
                }
                if (mymessage === "") { //emtpy message?
                    alert("Enter Some message Please!");
                    return;
                }

                //prepare json data
                var msg = {
                    message: mymessage,
                    name: myname,
                    color: '<?php echo $colours[$user_colour]; ?>'
                };
                var msg_json = JSON.stringify(msg);
                if (msg_json.length > 5120) {
                    alert('Too many chars to send!');
                    return;
                }
                //convert and send data to server
                websocket.send(msg_json);
            });

            //#### Message received from server?
            websocket.onmessage = function (ev) {
                var msg = JSON.parse(ev.data); //PHP sends Json data
                var time = msg.time;
                var type = msg.type; //message type
                var umsg = msg.message; //message text
                var escaped_user_message = msg.escaped_message;
                var uname = msg.name; //user name
                var ucolor = msg.color; //color

                if (type === 'usermsg') {
                    $('#message_box').append(
                        "<div>[" + time + "] " +
                        "<span class=\"user_name\" style=\"color:#" + ucolor + "\">" + uname + "</span>" +
                        " : " +
                        "<pre class=\"user_message\">" + escaped_user_message + "</pre>" +
                        "</div>"
                    );
                }
                if (type === 'system') {
                    $('#message_box').append("<div class=\"system_msg\">" + umsg + "</div>");
                }

                $('#client_count_sapn').html(msg.client_count);

                $('#message').val(''); //reset text
            };

            websocket.onerror = function (ev) {
                $('#message_box').append("<div class=\"system_error\">Error Occurred - " + ev.data + "</div>");
            };
            websocket.onclose = function (ev) {
                $('#message_box').append("<div class=\"system_msg\">Connection Closed</div>");
            };
        });
    </script>
    <style type="text/css">
        <!--
        h1 {
            text-align: center;
        }

        textarea {
            height: 100px;
        }

        .chat_wrapper {
            width: 92%;
            margin-right: auto;
            margin-left: auto;
            background: #CCCCCC;
            border: 1px solid #999999;
            padding: 10px;
            font: 12px 'lucida grande', tahoma, verdana, arial, sans-serif;
        }

        .chat_wrapper .message_box {
            background: #FFFFFF;
            height: 350px;
            overflow: auto;
            padding: 10px;
            border: 1px solid #999999;
        }

        .chat_wrapper .panel {
            text-align: center;
            margin: 10px auto;
        }

        .chat_wrapper .panel input {
            padding: 2px 2px 2px 5px;
        }

        .system_msg {
            color: #BDBDBD;
            font-style: italic;
        }

        .user_name {
            font-weight: bold;
        }

        .user_message {
            color: #88B6E0;
        }

        -->
    </style>
</head>
<body>
<div class="chat_wrapper">
    <h1>Koutsu</h1>
    <p style="text-align: center;">Free Chat With Any One Here! Current Online Count: <span
                id="client_count_sapn">0</span></p>
    <div class="panel">
        Your name:
        <input type="text" name="name" id="name" placeholder="Your Name" maxlength="10" style=""
               value="<?php echo $name; ?>"/>
        <button id="enter-btn" style="">Register</button>
    </div>
    <div class="message_box" id="message_box"></div>
    <div id="writing_area" style="display:none;">
        <div class="panel">
            <!--            <input type="text" name="message" id="message" placeholder="Message" maxlength="80" style="width:90%" />-->
            <textarea name="message" id="message" placeholder="Message" style="width: 90%"></textarea>
        </div>
        <div class="panel">
            <button id="send-btn" style="width:80%;height:30px;">Send</button>
        </div>
    </div>
    <div style="color: #0a86fb;text-align: center;">
        <p>Copyright 2017-2020 Sinri Edogawa. Licensed GPL-3.0.</p>
        <p><a href="https://github.com/sinri/Koutsu">View on GitHub!</a></p>
        <p><a href="https://github.com/sinri/Ark-WebSocket">Powered by Ark and WebSocket Component</a></p>
    </div>
</div>
</body>
</html>