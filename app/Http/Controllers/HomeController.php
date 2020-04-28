<?php

namespace App\Http\Controllers;

use App\User;
use Pusher\Pusher;
use App\UserMessage;
use App\FriendRequest;
use App\GoogleLanguage;
use App\Events\NewMessage;
use Illuminate\Http\Request;
use Google\Cloud\Translate\V3\TranslationServiceClient;
use Twilio\Rest\Client;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function authenticate(Request $request){
        $socketId = $request->socket_id;
        $channelName = $request->channel_name;
        $pusher = new Pusher(env('PUSHER_APP_KEY'),env('PUSHER_APP_SECRET'),env('PUSHER_APP_ID'),[
            'cluster'=>env('PUSHER_APP_CLUSTER'),
            'encrypted'=>true
        ]);
        $presence_data = ['name'=>auth()->user()->name];
        $key = $pusher->presence_auth($channelName,$socketId,auth()->id(),$presence_data);
        return response($key);
    }

    public function testTranslation($message='',$sender_lang="en",$recipient_lang="en"){
        $translation = "";
        if($sender_lang==$recipient_lang){
            return $message;
        }
        try {
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('GOOGLE_APPLICATION_CREDENTIALS'));
            $translationClient = new TranslationServiceClient();
            $content = [$message];
            $targetLanguage = $recipient_lang;
            $response = $translationClient->translateText(
                $content,
                $targetLanguage,
                TranslationServiceClient::locationName(env('GOOGLE_PROJECT_ID',''), 'global'),
                [
                    'sourceLanguageCode'=>$sender_lang
                ]
            );
            $translation = $response->getTranslations()[0]->getTranslatedText();
            // foreach ($response->getTranslations() as $key => $translation) {
                
            //     echo $translation->getTranslatedText();
            // }
        } catch (\Throwable $th) {
            \Log::error($th->getMessage());
        }
        return $translation;
    }

    public function sendNewMessage(Request $request){
        $recipient = User::find($request->recipient_id);
        $translation = $this->testTranslation($request->message,auth()->user()->preferred_language,$recipient->preferred_language);
        $user_message = new UserMessage();
        $user_message->sender_id = auth()->user()->id;
        $user_message->recipient_id = $recipient->id;
        $user_message->message = $request->message;
        $user_message->translated_message = $translation;
        $user_message->org_lang = auth()->user()->preferred_language;
        $user_message->trans_lang = $recipient->preferred_language;
        $user_message->status = 0;
        try {
            $user_message->save();
        } catch (\Throwable $th) {
            \Log::error($th->getMessage());
        }
        event(new NewMessage(
            [
                'message'=>$request->message,
                'sender'=>auth()->user()->name,
                'recipient_id'=>$request->recipient_id,
                'sender_id'=>auth()->user()->id,
                'translated_message'=>$translation,
                'time'=>date('d-m-Y H:i:s')
            ]
        ));
        return response()->json(
            [
            'message'=>$request->message,
            'sender'=>auth()->user()->name,
            'recipient_id'=>$request->recipient_id,
            'sender_id'=>auth()->user()->id,
            'translated_message'=>$translation,
            'time'=>date('d-m-Y H:i:s')
            ]);
    }

    public function getMyProfile(){
        $user = auth()->user();
        $languages = GoogleLanguage::where('enabled',1)->get();
        return view('my_profile',compact('user','languages'));
    }
    public function saveMyProfile(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:191',
            'preferred_language' => 'required|max:10',
        ]);
        $user = User::find(auth()->user()->id);
        if($user){
            $user->name = $request->name;
            $user->preferred_language = $request->preferred_language;
            if($user->save()){
                flash("User information successfully updated!")->success();
            }else{
                flash("Unable to save the user information!")->error();
            }
        }else{
            flash("Unable to find the user!")->error();
        }
        return redirect()->back();
    }

    public function getInitData(){
        $friendsQuery = FriendRequest::where(function($query)
        {
            $query->where('sender_id', auth()->user()->id);
            $query->orWhere('recipient_id', auth()->user()->id);
        })->where('accepted',1)->with('sender')->with('recipient')->get();
        $friends = [];
        if($friendsQuery->count()){
            foreach ($friendsQuery as $key => $value) {
                if($value->sender->id != auth()->user()->id){
                    $friends[] = ['id'=>$value->sender->id,'name'=>$value->sender->name,'is_selected'=>false,'is_online'=>false,'new_message'=>false];
                }else{
                    $friends[] = ['id'=>$value->recipient->id,'name'=>$value->recipient->name, 'is_selected'=>false,'is_online'=>false,'new_message'=>false];
                }
            }
        }
        return response()->json(['friends'=>$friends,'iceServers'=>$this->testTwillio()]);
    }

    public function getPreviousMessages($id){
        $previous_messages = [];
        $messagesQuery = UserMessage::where(function($query) use($id)
        {
            $query->where('sender_id', auth()->user()->id);
            $query->where('recipient_id', $id);
        })->orWhere(function($query) use($id)
        {
            $query->where('sender_id', $id);
            $query->where('recipient_id', auth()->user()->id);
        })->with('sender')->with('recipient')->orderBy('created_at','DESC')->limit(50)->get();
        if($messagesQuery->count() > 0){
            foreach ($messagesQuery as $key => $value) {
                $previous_messages[] = [
                    'message'=>$value->message,
                    'sender'=>$value->sender->name,
                    'recipient_id'=>$value->recipient->id,
                    'sender_id'=>$value->sender->id,
                    'translated_message'=>$value->translated_message,
                    'time'=>date('d-m-Y H:i:s',strtotime($value->created_at))
                ];
            }
        }
        $previous_messages = array_reverse($previous_messages);
        return \response()->json(['previous_messages'=>$previous_messages]);
    }

    public function searchUsers(Request $request){
        $users = [];
        if(!empty($request->keyword)){
            $userId = auth()->user()->id;
            $usersQuery = User::select('id','name','email')->where('email','like',"%$request->keyword%")->where('id','!=',$userId)->get();
            $friendsQuery = FriendRequest::select('sender_id','recipient_id')->where(function($query) use($userId)
            {
                $query->where('sender_id', $userId);
                $query->orWhere('recipient_id', $userId);
            })->where('accepted',1)->get();
            $friendIds = [];
            foreach ($friendsQuery as $key => $value) {
                if($value->sender_id != $userId){
                    $friendIds[] = $value->sender_id;
                }else{
                    $friendIds[] = $value->recipient_id;
                }
            }
            foreach ($usersQuery as $key => $value) {
                $users[] = [
                    'id'=>$value->id,
                    'name'=>$value->name,
                    'is_friend'=>in_array($value->id,$friendIds)
                ];
            }
        }
        
        return response()->json(['users'=>$users]);
    }

    public function addFriend(Request $request){
        $message = "";
        $status = true;
        $userId = auth()->user()->id;
        $recipientId = $request->user_id;
        $frinedRequest = FriendRequest::where('sender_id',$userId)->where('recipient_id',$recipientId)->first();
        if($frinedRequest){
            if($frinedRequest->accepted == 0){
                $status = false;
                $message = "You have already sent a freind request! Please wait for the user to accept it";
            }elseif($frinedRequest->accepted == 1){
                $status = false;
                $message = "You are already friends with the user!";
            }elseif($frinedRequest->accepted == -1){
                $status = false;
                $message = "User rejected your last request!";
            }
        }
        if($status){
            $frinedRequest = FriendRequest::where('sender_id',$recipientId)->where('recipient_id',$userId)->first();
            if($frinedRequest){
                if($frinedRequest->accepted == 0){
                    $status = false;
                    $message = "User has sent you a friend request! Please check in your friend requests list!";
                }elseif($frinedRequest->accepted == 1){
                    $status = false;
                    $message = "You are already friends with the user!";
                }
            }
        }
        if($status){
            $frinedRequest = new FriendRequest();
            $frinedRequest->sender_id = $userId;
            $frinedRequest->recipient_id = $recipientId;
            $frinedRequest->accepted = 0;
            if($frinedRequest->save()){
                $message = "Friend request sent to the user!";
            }else{
                $status = false;
                $message = "Unable to send friend request to user!";
            }
        }
        return response()->json(['status'=>$status,'message'=>$message]);
    }

    public function friendRequests(){
        $frinedRequestsQuery = FriendRequest::where(['recipient_id'=>auth()->user()->id,'accepted'=>0])->with('sender')->get();
        $frinedRequests = [];
        if($frinedRequestsQuery->count()){
            foreach ($frinedRequestsQuery as $key => $value) {
                $frinedRequests[] = ['id'=>$value->id,'name'=>$value->sender->name];
            }
        }
        return response()->json(['requests'=>$frinedRequests]);
    }

    public function acceptRejectPost(Request $request){
        $status = false;
        $message = "";
        $frinedRequest = FriendRequest::where(['id'=>$request->id,'recipient_id'=>auth()->user()->id])->first();
        if($frinedRequest){
            $frinedRequest->accepted = ($request->action == true)?1:-1;
            if($frinedRequest->save()){
                $status = true;
                $message = "Request successfully ".(($request->action == true)?'accepted':'rejected');
            }else{
                $message = "Unable to accept/reject the request!";
            }
        }else{
            $message = "Unable to find the friend request!";
        }
        return response()->json(['status'=>$status,'message'=>$message]);
    }

    public function testTwillio(){
        $cachedIceServersKey = 'twillio_ice_servers';
        $iceServers = [];
        if(!\Cache::has($cachedIceServersKey)){
            $sid    = env('TWILLIO_ACCOUNT_ID','');
            $token  = env('TWILLIO_AUTH_TOKEN','');
            $twilio = new Client($sid, $token);
            $token = $twilio->tokens->create();
            \Cache::put($cachedIceServersKey, $token->iceServers, (24*60*60));
        }
        $iceServers = \Cache::get($cachedIceServersKey, []);
        return $iceServers;
        
    }

    public function generateTwillioAccessToken(){
        $accountSid = env('TWILIO_ACCOUNT_SID');
        $apiKeySid = env('TWILIO_API_KEY_SID');
        $apiKeySecret = env('TWILIO_API_KEY_SECRET');
        $identity = auth()->user()->email;
        $token = new AccessToken(
            $accountSid,
            $apiKeySid,
            $apiKeySecret,
            3600,
            $identity
        );
        // Grant access to Video
        $grant = new VideoGrant();
        $room_sid = $this->createTwillioRoom();
        $grant->setRoom($room_sid);
        $token->addGrant($grant);

        // Serialize the token as a JWT
        echo $token->toJWT();
    }

    public function createTwillioRoom(){
        $sid    = env('TWILLIO_ACCOUNT_ID','');
        $token  = env('TWILLIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);
        $room = null;
        try {
            $room = $twilio->video->v1->rooms("OneToOne")->fetch();
        } catch (\Throwable $th) {
            $room = null;
        }
        if(!$room){
            $room = $twilio->video->v1->rooms->create([
                "uniqueName" => "OneToOne",
                'maxParticipants'=>2,
                'type'=>'peer-to-peer',
                'recordParticipantsOnConnect'=>false,
                'region'=>'in1',
                'enableTurn'=>true
                ]);
        }
        return $room->uniqueName;
    }

    public function completeTwillioRoom($room){
        $sid    = env('TWILLIO_ACCOUNT_ID','');
        $token  = env('TWILLIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);
        $room = $twilio->video->v1->rooms($room)->update("completed");
        dd($room->uniqueName);
    }
}
