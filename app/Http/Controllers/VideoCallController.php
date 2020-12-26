<?php

namespace App\Http\Controllers;

use App\Classes\Helpers;
use App\User;
use App\SiteSetting;
use Twilio\Rest\Client;
use Twilio\Jwt\AccessToken;
use Illuminate\Http\Request;
use Twilio\Jwt\Grants\VideoGrant;
use App\Classes\TwillioVideoActions;
use App\VideoRoom;

class VideoCallController extends Controller
{
    public function createAccessToken(Request $request)
    {
        $identity = auth()->user()->email;
        $caller = User::find($request->caller_id);
        $recipient = User::find($request->recipient_id);
        $roomName = Helpers::getRoomName($caller->email, $recipient->email);
        $videoRoom = VideoRoom::getRoomByName($roomName);
        $accessToken = TwillioVideoActions::createAccessToken($identity, $videoRoom->external_id, 3600);
        return response()->json(['room_sid' => $videoRoom->external_id, 'token' => $accessToken]);
    }

    public function completeRoom(Request $request)
    {
        $status = false;
        $message = '';
        $videoRoom = VideoRoom::getRoomByExternalIDAndParticipantID($request->room, auth()->user()->id);
        if ($videoRoom) {
            try {
                TwillioVideoActions::completeRoom($videoRoom->external_id);
                $status = true;
                $message = 'success';
            } catch (\Throwable $th) {
                \Log::critical($th->getMessage());
                $message = 'Error while trying call twillio: ' . $th->getMessage();
            }
        } else {
            $message = 'No room found!';
        }
        return response()->json(['status' => $status, 'message' => $message]);
    }
}
