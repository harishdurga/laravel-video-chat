<?php

namespace App\Http\Controllers;

use App\User;
use App\VideoRoom;
use Pusher\Pusher;
use App\Classes\Helpers;
use App\Classes\TwillioVideoActions;
use App\Events\NewMessage;
use App\Events\IncomingCall;
use Illuminate\Http\Request;
use App\Events\IncomingCallStatus;

class WebsocketsController extends Controller
{
    public function test()
    {
        // $pusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER')), 'videochat.test', 6001);
        // $response = $pusher->get('/channels');
        // if (is_array($response)) {
        //     if ($response['status'] == 200) {
        //         // convert to associative array for easier consumption
        //         $channels = json_decode($response['body'], true);
        //         dd($channels);
        //     }
        // }
        NewMessage::dispatch(['recipient_id' => 4, 'message' => 'Hello']);
    }

    public function incomingCall(Request $request)
    {
        $recipient = User::find($request->recipient_id);
        $roomName = Helpers::getRoomName(auth()->user()->email, $recipient->email);
        $videoRoom = VideoRoom::getRoomByName($roomName);
        if (!$videoRoom) {
            $twillioRoom = TwillioVideoActions::createRoom($roomName);
            if ($twillioRoom) {
                VideoRoom::createVideoRoom($roomName, $twillioRoom->sid, auth()->user()->id, $recipient->id);
            }
        } else {
            $twillioRoom = TwillioVideoActions::fetchRoom($videoRoom->external_id);
            if ($twillioRoom->status == 'completed') {
                $twillioRoom = TwillioVideoActions::createRoom($roomName, 'go');
                VideoRoom::updateRoomExternalID($roomName, $twillioRoom->sid);
            }
        }
        IncomingCall::dispatch(['recipient_id' => $request->recipient_id, 'caller' => ['name' => auth()->user()->name, 'id' => auth()->user()->id]]);
        return response()->json(['status' => true, 'message' => '']);
    }

    public function incomingCallStatus(Request $request)
    {

        IncomingCallStatus::dispatch(['recipient_id' => $request->caller_id, 'call_status' => $request->call_status]);
        return response()->json(['status' => true, 'message' => '']);
    }
}
