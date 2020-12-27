<?php

namespace App\Listeners;

use App\Events\IncomingCall;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class IncomingCallNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  IncomingCall  $event
     * @return void
     */
    public function handle(IncomingCall $event)
    {
        //
    }
}
