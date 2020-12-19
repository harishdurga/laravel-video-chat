<?php

namespace App\Classes;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class MyCustomWebSocketHandler extends \BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler
{
    public function onMessage(ConnectionInterface $connection, MessageInterface $msg)
    {
        // TODO: Implement onMessage() method.
        \Log::debug('Incoming message');
        parent::onMessage($connection, $msg);
    }
}
