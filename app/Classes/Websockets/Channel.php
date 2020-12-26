<?php

namespace App\Classes\Websockets;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel as BaseChannel;
use stdClass;
use Ratchet\ConnectionInterface;

class Channel extends BaseChannel
{

    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        parent::subscribe($connection, $payload);
        \Log::debug('Subscribed Channel Name' . $this->channelName);
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
