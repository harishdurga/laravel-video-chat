<?php

namespace App\Classes;

class Helpers
{
    public static function getRoomName(string $callerEmail, string $recipientEmail): string
    {
        return hash_hmac('sha256', $callerEmail . ':' . $recipientEmail, config('app.key'));;
    }

    /**
     * @return array ['channelType' => 'private','firstPrefix' => 'App','secondPrefix' => 'User',
     *       'userID' => '1'
     *  ]
     */
    public static function parseChannelName(string $channelName): array
    {
        $channelType = '';
        $channelNameArr = explode('-', $channelName);
        $channelType = $channelNameArr[0] ?? '';
        $channelNameArr = explode('.', $channelNameArr[1] ?? '');
        return [
            'channelType' => $channelType,
            'firstPrefix' => $channelNameArr[0] ?? '',
            'secondPrefix' => count($channelNameArr) == 2 ? '' : $channelNameArr[1] ?? '',
            'userID' => count($channelNameArr) == 2 ? $channelNameArr[1] ?? '' : $channelNameArr[2] ?? ''
        ];
    }
}
