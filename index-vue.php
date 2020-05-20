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
<html lang="en">
<head>
    <meta charset='UTF-8'/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <title>Koutsu - Free Chat With Any One Here</title>
    <script src="https://unpkg.com/vue@2.5.17/dist/vue.js"></script>
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

        .system_error {
            color: #fa4242;
            font-style: italic;
        }

        .user_name {
            font-weight: bold;
        }

        .user_message {
            color: #88B6E0;
        }

        .message_box table {
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
            width: 100%;
        }

        .message_box table th, td {
            text-align: left;
            padding: 5px;
            border: 1px solid lightgrey;
        }

        -->
    </style>
</head>
<body>
<div id="app">
    <h1>Koutsu</h1>
    <p style="text-align: center;">
        Free Chat With Any One Here! Current Online Count:
        <span>{{client_count}}</span>
    </p>
    <!--    <div class="panel">-->
    <!--        <label>-->
    <!--            Your name:-->
    <!--            <input type="text" name="name" id="name" placeholder="Your Name" maxlength="10" style=""-->
    <!--                   v-model="name" :readonly="registered"/>-->
    <!--            <button id="enter-btn" style="" v-on:click="registerUserName">{{registered?'Unregister':'Register'}}</button>-->
    <!--        </label>-->
    <!--    </div>-->
    <div class="message_box" id="message_box">
        <table>
            <tr>
                <th>Time</th>
                <th>Side</th>
                <th>Content</th>
            </tr>
            <tr v-for="item in messageList">
                <td>{{item.time}}</td>
                <td>
                    <template v-if="item.type==='system'">system</template>
                    <template v-if="item.type==='error'">error</template>
                    <template v-if="item.type==='talk'">
                        <span class="user_name" :style="{color:'#' + item.content.color}">{{item.content.name}}</span>
                    </template>
                </td>
                <td>
                    <template v-if="item.type==='talk'">
                        <pre class="user_message">{{item.content.content}}</pre>
                    </template>
                    <template v-if="item.type==='system'">
                        <span class="system_msg">{{item.content}}</span>
                    </template>
                    <template v-if="item.type==='error'">
                        <span class="system_error">{{item.content}}</span>
                    </template>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <label>
                        Your name:
                        <input type="text" name="name" id="name" placeholder="Your Name" maxlength="10" style=""
                               v-model="name" :readonly="registered"/>
                        <button id="enter-btn" style="" v-on:click="registerUserName">
                            {{registered?'Unregister':'Register'}}
                        </button>
                    </label>
                </td>
                <td>
                    <label for="message" v-show="registered">
                        <textarea name="message" id="message" placeholder="Message" style="width: 100%"
                                  v-model="draft"></textarea>
                        <button id="send-btn" style="width:100%;height:30px;" v-on:click="sendDraft">Send</button>
                    </label>
                </td>
            </tr>
        </table>
    </div>
    <!--    <div v-show="registered" id="writing_area" style="width: 90%;margin: auto">-->
    <!--        <div class="panel">-->
    <!--            <label for="message">-->
    <!--                <textarea name="message" id="message" placeholder="Message" style="width: 100%" v-model="draft"></textarea>-->
    <!--            </label>-->
    <!--        </div>-->
    <!--        <div class="panel">-->
    <!--            <button id="send-btn" style="width:100%;height:30px;" v-on:click="sendDraft">Send</button>-->
    <!--        </div>-->
    <!--    </div>-->
    <div style="color: #0a86fb;text-align: center;">
        Copyright 2017 Sinri Edogawa. Licensed GPL-3.0. <a href="https://github.com/sinri/Koutsu">View on GitHub!</a>
    </div>
</div>
<script>
    const wsUri = '<?php echo $servicePath; ?>';
    let websocket = null;
    let vue = new Vue({
        el: '#app',
        data: {
            messageList: [],
            client_count: -1,
            registered: false,
            name: "<?php echo $name; ?>",
            draft: '',
        },
        methods: {
            pushMessage: function (type, time, content) {
                this.messageList.push({type: type, time: time, content: content});
            },
            initializeWebSocket: function () {
                let that = this;
                websocket = new WebSocket(wsUri);

                websocket.onopen = function (ev) { // connection is open
                    that.pushMessage('system', that.getMySQLFormatDateTimeExpression(), 'connected to websocket server');
                };

                //#### Message received from server?
                websocket.onmessage = function (ev) {
                    const msg = JSON.parse(ev.data); //PHP sends Json data
                    // var escaped_user_message = msg.escaped_message;

                    if (msg.type === 'usermsg') {
                        that.pushMessage('talk', msg.time, {color: msg.color, name: msg.name, content: msg.message});
                    }
                    if (msg.type === 'system') {
                        that.pushMessage('system', that.getMySQLFormatDateTimeExpression(), msg.message);
                    }

                    that.client_count = msg.client_count;
                    that.draft = '';
                };

                websocket.onerror = function (ev) {
                    that.pushMessage('error', that.getMySQLFormatDateTimeExpression(), 'Error Occurred: ' + ev.data);
                };
                websocket.onclose = function (ev) {
                    that.pushMessage('system', that.getMySQLFormatDateTimeExpression(), 'Disconnected to websocket server.');
                };
            },
            registerUserName: function () {
                if (!this.registered && this.name === "") { //empty name?
                    alert("Enter your Name please!");
                    return;
                }

                //prepare json data
                const msg = {
                    message: (this.registered ? '[EXIT CHAT]' : '[ENTER CHAT]'),
                    name: this.name,
                    color: '<?php echo $colours[$user_colour]; ?>',
                    // color: '#666666',
                };
                //convert and send data to server
                websocket.send(JSON.stringify(msg));

                this.registered = !this.registered;
            },
            sendDraft: function () {
                if (!this.registered) {
                    alert('Please Register!');
                    return false;
                }

                if (this.name === "") { //empty name?
                    alert("Enter your Name please!");
                    return;
                }
                if (this.draft === "") { //empty message?
                    alert("Enter Some message Please!");
                    return;
                }

                //prepare json data
                const msg = {
                    message: this.draft,
                    name: this.name,
                    color: '<?php echo $colours[$user_colour]; ?>'
                };
                const msg_json = JSON.stringify(msg);
                if (msg_json.length > 5120) {
                    alert('Too many chars to send!');
                    return;
                }
                //convert and send data to server
                websocket.send(msg_json);
            },
            getMySQLFormatDateTimeExpression: function () {
                let now = new Date();
                return new Date(now.getTime() - now.getTimezoneOffset() * 60 * 1000).toISOString().slice(0, 19).replace('T', ' ');
            }
        },
        mounted: function () {
            this.initializeWebSocket();
        }
    });
</script>
</body>
</html>
