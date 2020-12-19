<?php

namespace App\Listeners;

use App\Events\IncomingCallStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class IncomingCallStatusNotification
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
     * @param  IncomingCallStatus  $event
     * @return void
     */
    public function handle(IncomingCallStatus $event)
    {
        //
    }
}
