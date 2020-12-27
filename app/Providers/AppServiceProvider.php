<?php

namespace App\Providers;

use Pusher\Pusher;
use Twilio\Rest\Client;
use App\Classes\VideoClient;
use App\Classes\PusherClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(VideoClient::class, function ($app) {
            return new Client(env('TWILLIO_ACCOUNT_ID', ''), env('TWILLIO_AUTH_TOKEN'));
        });
        $this->app->singleton(PusherClient::class, function ($app) {
            return new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER')), 'videochat.test', 6001);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Schema::defaultStringLength(191);
    }
}
