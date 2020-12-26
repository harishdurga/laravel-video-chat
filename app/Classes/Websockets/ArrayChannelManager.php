<?php

namespace App\Classes\Websockets;


use Illuminate\Support\Str;
use App\Classes\Websockets\Channel;
use App\Classes\Websockets\PrivateChannel;
use App\Classes\Websockets\PresenceChannel;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager as BaseArrayChannelManager;

class ArrayChannelManager extends BaseArrayChannelManager
{

    protected function determineChannelClass(string $channelName): string
    {
        if (Str::startsWith($channelName, 'private-')) {
            return PrivateChannel::class;
        }

        if (Str::startsWith($channelName, 'presence-')) {
            return PresenceChannel::class;
        }

        return Channel::class;
    }
}
