<?php

namespace App\Providers;

use Twilio\Rest\Client;
use App\Classes\VideoClient;
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
