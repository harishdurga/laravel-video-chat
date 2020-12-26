<?php

namespace App\Classes\Websockets;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\PrivateChannel as BasePrivateChannel;
use stdClass;
use Ratchet\ConnectionInterface;

class PrivateChannel extends BasePrivateChannel
{

    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        parent::subscribe($connection, $payload);
        \Log::debug('Subscribed Private Channel Name' . $this->channelName);
        \Log::debug(json_encode($payload));
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        if (isset($this->subscribedConnections[$connection->socketId])) {
            \Log::debug('Unsunscribed Channel Name' . $this->channelName);
        }

        parent::unsubscribe($connection);
    }
}
