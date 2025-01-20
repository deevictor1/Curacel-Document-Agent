<?php

namespace App\Providers;

use Google_Client;
use Illuminate\Support\ServiceProvider;

class GoogleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Google_Client::class, function ($app) {
            $client = new Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect'));
            $client->setScopes([
                'https://www.googleapis.com/auth/drive.readonly',
                'https://www.googleapis.com/auth/drive.metadata.readonly'
            ]);
            $client->setAccessType('offline');
            $client->setPrompt('consent');
            
            return $client;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
