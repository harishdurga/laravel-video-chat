<?php

use App\Events\NewMessage;
use Illuminate\Support\Facades\Route;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/new-home', 'HomeController@newHome')->name('new-home');

Route::post('/pusher/auth', 'HomeController@authenticate');


Route::post('send-new-message', 'HomeController@sendNewMessage');
Route::get('my-profile', 'HomeController@getMyProfile')->name('my-profile');
Route::post('my-profile', 'HomeController@saveMyProfile')->name('my-profile');
Route::get('get-init-data', 'HomeController@getInitData');
Route::get('previous-messages/{id}', 'HomeController@getPreviousMessages');
Route::get('search-users', 'HomeController@searchUsers');
Route::post('add-friend', 'HomeController@addFriend');
Route::get('friend-requests', 'HomeController@friendRequests');
Route::post('accept-reject-request', 'HomeController@acceptRejectPost');

Route::get('twillio_access_token', 'HomeController@generateTwillioAccessToken');
Route::get('twillio-create-room', 'HomeController@createTwillioRoom');
Route::get('twillio-complete-room/{room}', 'HomeController@completeTwillioRoom');
Route::get('twilio-init-data', 'HomeController@getTwilioInitData');

Route::group(['prefix' => 'video-call'], function () {
    Route::post('token', 'VideoCallController@createAccessToken');
});


WebSocketsRouter::webSocket('/my-websocket', \App\Classes\MyCustomWebSocketHandler::class);


Route::post('call-user', 'WebsocketsController@incomingCall');
Route::post('call-status', 'WebsocketsController@incomingCallStatus');

Route::post('mark-user-messages', 'HomeController@markUserMessagesAsRead');

Route::get('test-websockets', 'VideoCallController@test');
