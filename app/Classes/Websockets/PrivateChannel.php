<?php

namespace App\Classes\Websockets;

use App\Classes\Helpers;
use stdClass;
use App\Jobs\UserOnlineStatus;
use Ratchet\ConnectionInterface;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PrivateChannel as BasePrivateChannel;

class PrivateChannel extends BasePrivateChannel
{

    public function subscribe(ConnectionInterface $connection, stdClass $payload)
    {
        parent::subscribe($connection, $payload);
        // \Log::debug('Subscribedd Private Channel Name: ' . $this->channelName);
        // \Log::debug(json_encode($payload));
        $parsedChannelName = Helpers::parseChannelName($this->channelName);
        if ($parsedChannelName['firstPrefix'] == 'App' && !empty($parsedChannelName['userID'])) {
            UserOnlineStatus::dispatch(intval($parsedChannelName['userID']), 'subscribe');
        }
    }

    public function unsubscribe(ConnectionInterface $connection)
    {
        if (isset($this->subscribedConnections[$connection->socketId])) {
            $parsedChannelName = Helpers::parseChannelName($this->channelName);
            if ($parsedChannelName['firstPrefix'] == 'App' && !empty($parsedChannelName['userID'])) {
                UserOnlineStatus::dispatch(intval($parsedChannelName['userID']), 'unsubscribe');
            }
        }

        parent::unsubscribe($connection);
    }
}
