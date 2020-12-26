<?php

namespace App;

use App\User;
use Illuminate\Database\Eloquent\Model;

class FriendRequest extends Model
{
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public static function getUserFriends(int $userID)
    {
        return self::where(function ($query) use ($userID) {
            $query->where('sender_id', $userID);
            $query->orWhere('recipient_id', $userID);
        })->where('accepted', 1)->with('sender')->with('recipient')->get();
    }
}
