<?php

namespace App\Http\Controllers;

use App\SiteSetting;
use Twilio\Jwt\AccessToken;
use Illuminate\Http\Request;
use Twilio\Jwt\Grants\VideoGrant;
use Twilio\Rest\Client;

class VideoCallController extends Controller
{
    public function createAccessToken(Request $request)
    {
        $accountSid = env('TWILIO_ACCOUNT_SID');
        $apiKeySid = env('TWILIO_API_KEY_SID');
        $apiKeySecret = env('TWILIO_API_KEY_SECRET');
        $identity = auth()->user()->email;
        $twillio_cache_key = 'twillio_access_token_' . $identity;
        $room_sid = $this->createTwillioRoom();
        if (!\Cache::has($twillio_cache_key)) {
            $token = new AccessToken(
                $accountSid,
                $apiKeySid,
                $apiKeySecret,
                3600,
                $identity
            );
            // Grant access to Video
            $grant = new VideoGrant();
            $grant->setRoom($room_sid);
            $token->addGrant($grant);
            \Cache::put($twillio_cache_key, strval($token->toJWT()), 3600);
        }
        return response()->json(['room_sid' => $room_sid, 'token' => \Cache::get($twillio_cache_key)]);
    }

    private function createTwillioRoom()
    {
        // $roomName = SiteSetting::where('key', 'twillio_room_name')->first();
        $roomName = hash_hmac('sha256', auth()->user()->email, config('app.key'));
        $roomRegion = SiteSetting::where('key', 'twillio_room_region')->first();
        if ($roomRegion) {
            $roomRegion = $roomRegion->value;
        } else {
            $roomRegion = 'gll';
        }
        $sid    = env('TWILLIO_ACCOUNT_ID', '');
        $token  = env('TWILLIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);
        $room = null;
        try {
            $room = $twilio->video->v1->rooms($roomName)->fetch();
        } catch (\Throwable $th) {
            $room = null;
        }
        if (!$room) {
            $room = $twilio->video->v1->rooms->create([
                "uniqueName" => $roomName,
                'maxParticipants' => 2,
                'type' => 'go',
                'recordParticipantsOnConnect' => false,
                'region' => $roomRegion,
                'enableTurn' => true
            ]);
        }
        return $room->uniqueName;
    }
}
