<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserMessage extends Model
{
    public function setMessageAttribute($value)
    {
        $this->attributes['message'] = \Crypt::encryptString($value);
    }
    public function setTranslatedMessageAttribute($value)
    {
        $this->attributes['translated_message'] = \Crypt::encryptString($value);
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
    public function getMessageAttribute($value)
    {
        try {
            return \Crypt::decryptString($value);
        } catch (\Throwable $th) {
            \Log::error($th->getMessage());
            return '';
        }
    }
    public function getTranslatedMessageAttribute($value)
    {
        try {
            return \Crypt::decryptString($value);
        } catch (\Throwable $th) {
            \Log::error($th->getMessage());
            return '';
        }
    }

    public static function getUnreadMessageCount(int $senderID, int $recieverID): int
    {
        return self::where('sender_id', $senderID)->where('recipient_id', $recieverID)->where('status', 0)->count();
    }

    public static function markMessagesAsRead(int $senderID, int $recieverID): int
    {
        return self::where('sender_id', $senderID)->where('recipient_id', $recieverID)->update(['status' => 1]);
    }
}
