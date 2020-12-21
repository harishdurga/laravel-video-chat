<?php

namespace App\Classes;

use App\Classes\VideoClient;
use Twilio\Rest\Video\V1\RoomInstance;

class TwillioVideoActions
{
    public static function createRoom(string $roomName, string $roomType = 'go', string $roomRegion = 'gll'): ?RoomInstance
    {
        $videoClient = \App::make(VideoClient::class);
        try {
            return $videoClient->video->v1->rooms->create([
                "uniqueName" => $roomName,
                'maxParticipants' => 2,
                'type' => $roomType,
                'recordParticipantsOnConnect' => false,
                'region' => $roomRegion,
                'enableTurn' => true
            ]);
        } catch (\Throwable $th) {
            \Log::critical($th->getMessage());
            return null;
        }
    }

    public static function completeRoom(string $roomSid): ?RoomInstance
    {
        $videoClient = \App::make(VideoClient::class);
        try {
            return $videoClient->video->v1->rooms($roomSid)->update("completed");
        } catch (\Throwable $th) {
            \Log::critical($th->getMessage());
            return null;
        }
    }

    public static function fetchRoom(string $roomSid): ?RoomInstance
    {
        $videoClient = \App::make(VideoClient::class);
        try {
            return $videoClient->video->v1->rooms($roomSid)->fetch();
        } catch (\Throwable $th) {
            \Log::critical($th->getMessage());
            return null;
        }
    }
}
