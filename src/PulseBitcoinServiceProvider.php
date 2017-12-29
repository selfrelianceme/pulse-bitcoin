<?php

namespace Selfreliance\PulseBitcoin;
use Illuminate\Support\ServiceProvider;

class PulseBitcoinServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        include __DIR__ . '/routes.php';
        $this->app->make('Selfreliance\PulseBitcoin\PulseBitcoin');

        $this->publishes([
            __DIR__.'/config/pulsebitcoin.php' => config_path('pulsebitcoin.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}