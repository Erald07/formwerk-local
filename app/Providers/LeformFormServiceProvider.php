<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LeformFormServiceProvider extends ServiceProvider
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
        $this->app->bind('App\Service\LeformFormService', function($app) {
            return new \App\Service\LeformFormService(1);
        });
    }
}
