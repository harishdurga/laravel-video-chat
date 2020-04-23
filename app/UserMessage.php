<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserMessage extends Model
{
    public function setMessageAttribute($value)
    {
        $this->attributes['message'] = \Crypt::encryptString($value);
    }
    public function setTranslatedMessageAttribute($value){
        $this->attributes['translated_message'] = \Crypt::encryptString($value);
    }
}
