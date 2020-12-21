<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoRoom extends Model
{
    use HasFactory;
    protected $fillable = ['room_name', 'external_id', 'caller_id', 'recipient_id', 'status'];

    public static function createVideoRoom(string $roomName, string $externalID, int $callerID, int $recipientID)
    {
        return VideoRoom::create([
            'room_name' => $roomName,
            'external_id' => $externalID,
            'caller_id' => $callerID,
            'recipient_id' => $recipientID
        ]);
    }

    public static function getRoomByName(string $roomName): ?VideoRoom
    {
        return VideoRoom::where('room_name', $roomName)->first();
    }
}
