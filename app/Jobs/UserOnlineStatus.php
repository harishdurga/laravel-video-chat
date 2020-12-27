<?php

namespace App\Jobs;

use App\FriendRequest;
use App\Classes\PusherClient;
use Illuminate\Bus\Queueable;
use App\Events\UserOnlineStatusUpdate;
use App\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UserOnlineStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $userID;
    protected $type;
    public function __construct(int $userID, string $type)
    {
        $this->userID = $userID;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $friends = FriendRequest::getUserFriends($this->userID);
        if ($friends->count()) {
            $friendsIds = [];
            foreach ($friends as $key => $value) {
                $friendsIds[] = $value->sender->id != $this->userID ? $value->sender->id : $value->recipient->id;
            }
            User::updateOnlineStatus($this->userID, $this->type == 'subscribe' ? true : false);
            $pusherClient = \App::make(PusherClient::class);
            foreach ($friendsIds as $key => $id) {
                $response = $pusherClient->get('/channels/private-App.User.' . $id);
                if (isset($response['status'])) {
                    if ($response['status'] == 200) {
                        // convert to associative array for easier consumption
                        $channel_info = json_decode($response['body'], true);
                        if ($channel_info['occupied']) {
                            //Trigger event
                            UserOnlineStatusUpdate::dispatch(['recipient_id' => $id, 'user_id' => $this->userID, 'is_online' => $this->type == 'subscribe' ? true : false]);
                        }
                    }
                }
            }
        }
    }
}
