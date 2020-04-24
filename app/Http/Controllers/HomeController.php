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

    public function testTranslation($message='',$sender_lang="en",$recipient_lang="ru"){
        $translation = "";
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
                'translated_message'=>$translation
            ]
        ));
        return response()->json(['message'=>$request->message,'sender'=>auth()->user()->name,'recipient_id'=>$request->recipient_id,'sender_id'=>auth()->user()->id,'translated_message'=>$translation]);
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
            $query->where('sender_id', 'keyword');
            $query->orWhere('recipient_id', 'like', 'keyword');
        })->where('accepted',1)->with('sender')->with('recipient')->get();
        $friends = [];
        if($friendsQuery->count()){
            foreach ($friendsQuery as $key => $value) {
                
            }
        }
    }
}
