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
        return self::create([
            'room_name' => $roomName,
            'external_id' => $externalID,
            'caller_id' => $callerID,
            'recipient_id' => $recipientID
        ]);
    }

    public static function getRoomByName(string $roomName): ?VideoRoom
    {
        return self::where('room_name', $roomName)->first();
    }

    public static function updateRoomExternalID(string $roomName, string $externalID)
    {
        return self::where('room_name', $roomName)->update([
            'external_id' => $externalID
        ]);
    }

    public static function getRoomByExternalIDAndParticipantID(string $externalID, int $participantID)
    {
        return self::where('external_id', $externalID)->where(function ($query) use ($participantID) {
            $query->where('caller_id', $participantID)->orWhere('recipient_id', $participantID);
        })->first();
    }
}
