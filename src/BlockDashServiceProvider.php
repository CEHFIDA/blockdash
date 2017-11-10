<?php

namespace Selfreliance\BlockDash;
use Illuminate\Support\ServiceProvider;

class BlockDashServiceProvider extends ServiceProvider
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
        $this->app->make('Selfreliance\BlockDash\BlockDash');

        $this->publishes([
            __DIR__.'/config/blockdash.php' => config_path('blockdash.php'),
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