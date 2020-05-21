<?php


namespace sinri\koutsu\implementation;


use sinri\ark\core\ArkHelper;
use sinri\ark\websocket\ArkWebSocketWorker;

class KoutsuWorker extends ArkWebSocketWorker
{

    public function processNewSocket($clientHash, $header)
    {
        $standard_response = array(
            'time' => date('Y-m-d H:i:s'),
            'type' => 'system',
            'message' => $clientHash . ' connected',
            'client_count' => $this->connections->getCountOfClients(),
        );

        $log_obj = array('hash' => $clientHash, 'header' => $header, 'standard_response' => $standard_response);
        $this->logger->debug(__METHOD__, $log_obj);

        $this->maskAndBroadcastToClients(json_encode($standard_response));
        return $this;
    }

    public function processReadMessage($clientHash, $buffer)
    {
        $receivedMessage = self::unmask($buffer);
        $tst_msg = json_decode($receivedMessage, true); //json decode
        $user_name = ArkHelper::readTarget($tst_msg, 'name', '__UNKNOWN__');
        $user_message = ArkHelper::readTarget($tst_msg, 'message', '__EMPTY__');
        $user_color = ArkHelper::readTarget($tst_msg, 'color', '#222222');

        $standard_response = (
        array(
            'time' => date('Y-m-d H:i:s'),
            'type' => 'usermsg',
            'name' => $user_name . '@' . $clientHash,
            'message' => $user_message,
            'escaped_message' => htmlentities($user_message),
            'color' => $user_color,
            'client_count' => $this->connections->getCountOfClients(),
        )
        );

        $log_obj = array('hash' => $clientHash, 'tst_msg' => $tst_msg, 'standard_response' => $standard_response);
        $this->logger->debug("processReadMessage", $log_obj);

        $this->maskAndBroadcastToClients(json_encode($standard_response));
        return $this;
    }

    public function processCloseSocket($clientHash)
    {
        $standard_response = (
        array(
            'time' => date('Y-m-d H:i:s'),
            'type' => 'system',
            'message' => $clientHash . ' disconnected',
            'client_count' => $this->connections->getCountOfClients(),
        )
        );

        $log_obj = array('hash' => $clientHash, 'standard_response' => $standard_response);
        $this->logger->debug('processCloseSocket', $log_obj);

        $this->maskAndBroadcastToClients(json_encode($standard_response));
        return $this;
    }
}