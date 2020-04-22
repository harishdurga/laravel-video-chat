<?php

namespace App\Http\Controllers;

use Pusher\Pusher;
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

    public function testTranslation(){
        // dd(base_path(env('GOOGLE_APPLICATION_CREDENTIALS')));
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.env('GOOGLE_APPLICATION_CREDENTIALS'));
        $translationClient = new TranslationServiceClient();
        $content = ["Пришлите мне ваши"];
        $targetLanguage = 'en';

        $response = $translationClient->translateText(
            $content,
            $targetLanguage,
            TranslationServiceClient::locationName(env('GOOGLE_PROJECT_ID',''), 'global'),
            [
                'sourceLanguageCode'=>'ru'
            ]
        );

        foreach ($response->getTranslations() as $key => $translation) {
            
            echo $translation->getTranslatedText();
        }
    }
}
