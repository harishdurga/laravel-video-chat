<?php

namespace App\Classes;

class Helpers
{
    public static function getRoomName(string $callerEmail, string $recipientEmail): string
    {
        return hash_hmac('sha256', $callerEmail . ':' . $recipientEmail, config('app.key'));;
    }
}
