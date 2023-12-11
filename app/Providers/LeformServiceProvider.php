<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LeformServiceProvider extends ServiceProvider
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
        $this->app->bind('App\Service\LeformService', function($app) {
            return new \App\Service\LeformService();
        });
    }
}
