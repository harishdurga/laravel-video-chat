<?php

namespace App\Classes\Websockets;

use stdClass;
use App\Jobs\UserOnlineStatus;
use Ratchet\ConnectionInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PrivateChannel as BasePrivateChannel;

class PrivateChannel extends BasePrivateChannel
{

    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        parent::subscribe($connection, $payload);
        \Log::debug('Subscribed Private Channel Name' . $this->channelName);
        \Log::debug(json_encode($payload));
        UserOnlineStatus::dispatch($this->channelName, 'subscribe');
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        if (isset($this->subscribedConnections[$connection->socketId])) {
            UserOnlineStatus::dispatch($this->channelName, 'unsubscribe');
        }

        parent::unsubscribe($connection);
    }
}
