<?php

namespace App\Providers;

use Gemini\Client;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Vite; // This is the class we want to override
use Illuminate\Support\ServiceProvider; // We need to create a Guzzle client

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // This code overrides the default Gemini Client to include a longer timeout.
        // $this->app->singleton(Client::class, function ($app) {

        //     // 1. Get the API Key from your config file.
        //     $apiKey = $app['config']->get('gemini.api_key');

        //     // 2. Create a Guzzle HTTP client with a custom timeout of 120 seconds.
        //     $guzzleClient = new GuzzleClient([
        //         'timeout' => 120,
        //     ]);

        //     // 3. Build the Gemini client using its factory, passing in our custom HTTP client.
        //     return \Gemini::factory()
        //         ->withApiKey($apiKey)
        //         ->withHttpClient($guzzleClient)
        //         ->make();
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
