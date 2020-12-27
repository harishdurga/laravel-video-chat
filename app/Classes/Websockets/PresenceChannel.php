<?php

namespace App\Classes\Websockets;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel as BasePresenceChannel;
use Illuminate\Support\Facades\Redis;
use stdClass;
use Ratchet\ConnectionInterface;

class PresenceChannel extends BasePresenceChannel
{

    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        parent::subscribe($connection, $payload);
        // \Log::debug('Subscribed Presence Channel Name' . $this->channelName);
        // \Log::debug(json_encode($payload));
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        if (isset($this->subscribedConnections[$connection->socketId])) {
            // \Log::debug('Unsunscribed Presence Channel Name' . $this->channelName);
        }

        parent::unsubscribe($connection);
    }
}
