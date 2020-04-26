<?php

use App\Events\NewMessage;
use Illuminate\Support\Facades\Route;

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

Route::post('/pusher/auth','HomeController@authenticate');

Route::get('test-message', function () {
    event(new NewMessage("Hello world",2));
});
Route::get('test-translation','HomeController@testTranslation');
Route::post('send-new-message', 'HomeController@sendNewMessage');
Route::get('my-profile','HomeController@getMyProfile')->name('my-profile');
Route::post('my-profile', 'HomeController@saveMyProfile')->name('my-profile');
Route::get('get-init-data', 'HomeController@getInitData');
Route::get('previous-messages/{id}', 'HomeController@getPreviousMessages');
Route::get('search-users','HomeController@searchUsers');
Route::post('add-friend','HomeController@addFriend');
Route::get('friend-requests','HomeController@friendRequests');
Route::post('accept-reject-request','HomeController@acceptRejectPost');