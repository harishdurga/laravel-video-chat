<?php

namespace App\Http\Controllers;

use App\User;
use App\VideoRoom;
use App\SiteSetting;
use Twilio\Rest\Client;
use App\Classes\Helpers;
use Twilio\Jwt\AccessToken;
use App\Events\IncomingCall;
use Illuminate\Http\Request;
use Twilio\Jwt\Grants\VideoGrant;
use App\Events\IncomingCallStatus;
use App\Classes\TwillioVideoActions;

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
