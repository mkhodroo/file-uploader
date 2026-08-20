<?php

namespace BehinFileControl;

use Illuminate\Support\ServiceProvider;

class BehinFileControlProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__. '/config.php' => config_path('file_control.php')
        ]);
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}
