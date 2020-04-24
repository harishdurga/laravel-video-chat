<?php

namespace App;

use App\User;
use Illuminate\Database\Eloquent\Model;

class FriendRequest extends Model
{
    public function sender(){
        return $this->belongsTo(User::class,'sender_id');
    }
    public function recipient(){
        return $this->belongsTo(User::class,'recipient_id');
    }
}
